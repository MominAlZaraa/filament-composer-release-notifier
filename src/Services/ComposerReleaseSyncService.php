<?php

namespace MominAlZaraa\FilamentComposerReleaseNotifier\Services;

use MominAlZaraa\FilamentComposerReleaseNotifier\Models\ComposerReleasePackageSnapshot;

class ComposerReleaseSyncService
{
    public function __construct(
        protected ComposerLockReader $lockReader,
        protected GitHubRepositoryResolver $repositoryResolver,
        protected GitHubReleaseClient $githubClient,
        protected PackagistVersionClient $packagistClient,
        protected PackageVersionComparator $versionComparator,
    ) {}

    /**
     * @return array{tracked: int, outdated: int, skipped: int}
     */
    public function sync(): array
    {
        $versionSource = (string) config('filament-composer-release-notifier.version_source', 'packagist');

        return match ($versionSource) {
            'github' => $this->syncUsingGitHub(),
            default => $this->syncUsingPackagist(),
        };
    }

    /**
     * @return array{tracked: int, outdated: int, skipped: int}
     */
    protected function syncUsingGitHub(): array
    {
        $jsonPath = (string) config('filament-composer-release-notifier.composer_json_path');
        $lockPath = (string) config('filament-composer-release-notifier.composer_lock_path');
        $excluded = config('filament-composer-release-notifier.excluded_packages', []);
        $excluded = is_array($excluded) ? $excluded : [];
        $maxCommits = (int) config('filament-composer-release-notifier.compare.max_commits_stored', 50);

        $locked = $this->lockReader->readLockedPackages($jsonPath, $lockPath);

        $now = now();
        $seenNames = [];
        $outdated = 0;
        $skipped = 0;

        foreach ($locked as $name => $meta) {
            if (in_array($name, $excluded, true)) {
                $skipped++;

                continue;
            }

            $repo = $this->repositoryResolver->resolve($meta['source_url'] ?? null);
            if ($repo === null) {
                $skipped++;

                continue;
            }

            $seenNames[] = $name;
            $installed = $meta['version'];

            $release = $this->githubClient->getLatestRelease($repo['owner'], $repo['repo']);
            $lastError = null;
            if ($release === null) {
                $lastError = 'No published GitHub release found (404) or API error.';
            }

            $tag = is_array($release) && isset($release['tag_name']) && is_string($release['tag_name'])
                ? $release['tag_name']
                : null;
            $releaseNotes = is_array($release) && isset($release['body']) && is_string($release['body'])
                ? $release['body']
                : null;

            $isOutdated = $this->versionComparator->isOutdated($installed, $tag);
            if ($isOutdated) {
                $outdated++;
            }

            $compareUrl = null;
            $commitsPayload = null;

            if ($tag !== null && $tag !== '' && $installed !== '' && ! str_starts_with($installed, 'dev-')) {
                $compare = $this->compareWithRefFallbacks($repo['owner'], $repo['repo'], $installed, $tag);
                if ($compare !== null) {
                    $compareUrl = $compare['html_url'];
                    $commitsPayload = $this->truncateCommits($compare['commits'], $maxCommits);
                }
            }

            ComposerReleasePackageSnapshot::query()->updateOrCreate(
                ['package_name' => $name],
                [
                    'repository_owner' => $repo['owner'],
                    'repository_name' => $repo['repo'],
                    'installed_version' => $installed,
                    'latest_release_tag' => $tag,
                    'is_outdated' => $isOutdated,
                    'compare_html_url' => $compareUrl,
                    'release_notes' => $releaseNotes,
                    'commits_payload' => $commitsPayload,
                    'last_error' => $lastError,
                    'synced_at' => $now,
                ],
            );
        }

        $this->pruneSnapshots($seenNames);

        return [
            'tracked' => count($seenNames),
            'outdated' => $outdated,
            'skipped' => $skipped,
        ];
    }

    /**
     * @return array{tracked: int, outdated: int, skipped: int}
     */
    protected function syncUsingPackagist(): array
    {
        $jsonPath = (string) config('filament-composer-release-notifier.composer_json_path');
        $lockPath = (string) config('filament-composer-release-notifier.composer_lock_path');
        $excluded = config('filament-composer-release-notifier.excluded_packages', []);
        $excluded = is_array($excluded) ? $excluded : [];
        $fetchGithubCommits = (bool) config('filament-composer-release-notifier.compare.fetch_github_commits_with_packagist', false);
        $maxCommits = (int) config('filament-composer-release-notifier.compare.max_commits_stored', 50);

        $locked = $this->lockReader->readLockedPackages($jsonPath, $lockPath);

        $now = now();
        $seenNames = [];
        $outdated = 0;
        $skipped = 0;

        foreach ($locked as $name => $meta) {
            if (in_array($name, $excluded, true)) {
                $skipped++;

                continue;
            }

            $parts = explode('/', $name, 2);
            $displayOwner = $parts[0] ?? '';
            $displayRepo = $parts[1] ?? '';

            $latest = $this->packagistClient->getLatestStable($name);
            $lastError = null;
            if ($latest === null) {
                $lastError = 'No stable version found on Packagist (package missing or only dev branches).';
            }

            $latestVersion = is_array($latest) && isset($latest['version']) && is_string($latest['version'])
                ? $latest['version']
                : null;
            $releaseNotes = is_array($latest) && isset($latest['release_notes']) && is_string($latest['release_notes'])
                ? $latest['release_notes']
                : null;

            $installed = $meta['version'];
            $isOutdated = $this->versionComparator->isOutdated($installed, $latestVersion);
            if ($isOutdated) {
                $outdated++;
            }

            $ghRepo = $this->repositoryResolver->resolve($meta['source_url'] ?? null);

            $compareUrl = null;
            $commitsPayload = null;

            if (
                $ghRepo !== null
                && $latestVersion !== null
                && $latestVersion !== ''
                && $installed !== ''
                && ! str_starts_with($installed, 'dev-')
            ) {
                $compareUrl = GitHubReleaseClient::compareWebUrl(
                    $ghRepo['owner'],
                    $ghRepo['repo'],
                    $installed,
                    $latestVersion,
                );

                if ($fetchGithubCommits) {
                    $compare = $this->compareWithRefFallbacks($ghRepo['owner'], $ghRepo['repo'], $installed, $latestVersion);
                    if ($compare !== null) {
                        $compareUrl = $compare['html_url'] ?? $compareUrl;
                        $commitsPayload = $this->truncateCommits($compare['commits'], $maxCommits);
                    }
                }
            } elseif ($latestVersion !== null && $latestVersion !== '') {
                $compareUrl = 'https://packagist.org/packages/'.rawurlencode(strtolower($name));
            }

            $seenNames[] = $name;

            ComposerReleasePackageSnapshot::query()->updateOrCreate(
                ['package_name' => $name],
                [
                    'repository_owner' => $ghRepo['owner'] ?? $displayOwner,
                    'repository_name' => $ghRepo['repo'] ?? $displayRepo,
                    'installed_version' => $installed,
                    'latest_release_tag' => $latestVersion,
                    'is_outdated' => $isOutdated,
                    'compare_html_url' => $compareUrl,
                    'release_notes' => $releaseNotes,
                    'commits_payload' => $commitsPayload,
                    'last_error' => $lastError,
                    'synced_at' => $now,
                ],
            );
        }

        $this->pruneSnapshots($seenNames);

        return [
            'tracked' => count($seenNames),
            'outdated' => $outdated,
            'skipped' => $skipped,
        ];
    }

    /**
     * @param  array<int, string>  $seenNames
     */
    protected function pruneSnapshots(array $seenNames): void
    {
        if ($seenNames !== []) {
            ComposerReleasePackageSnapshot::query()
                ->whereNotIn('package_name', $seenNames)
                ->delete();
        } else {
            ComposerReleasePackageSnapshot::query()->delete();
        }
    }

    /**
     * @param  array<int, mixed>  $commits
     * @return array<int, array<string, mixed>>
     */
    protected function truncateCommits(array $commits, int $max): array
    {
        $out = [];
        foreach (array_slice($commits, 0, $max) as $commit) {
            if (! is_array($commit)) {
                continue;
            }
            $sha = $commit['sha'] ?? '';
            $commitNode = is_array($commit['commit'] ?? null) ? $commit['commit'] : [];
            $message = is_string($commitNode['message'] ?? null) ? $commitNode['message'] : '';
            $firstLine = explode("\n", $message, 2)[0];
            $author = is_array($commitNode['author'] ?? null) ? $commitNode['author'] : [];
            $date = is_string($author['date'] ?? null) ? $author['date'] : null;

            $out[] = [
                'sha' => is_string($sha) ? substr($sha, 0, 7) : '',
                'message' => $firstLine,
                'date' => $date,
                'html_url' => is_string($commit['html_url'] ?? null) ? $commit['html_url'] : null,
            ];
        }

        return $out;
    }

    /**
     * @return array{html_url: ?string, commits: array<int, array<string, mixed>>}|null
     */
    protected function compareWithRefFallbacks(string $owner, string $repo, string $installed, string $tag): ?array
    {
        $bases = array_values(array_unique(array_filter([
            $installed,
            str_starts_with($installed, 'v') ? null : 'v'.$installed,
            str_starts_with($installed, 'v') ? substr($installed, 1) : null,
        ])));

        foreach ($bases as $base) {
            $result = $this->githubClient->compare($owner, $repo, (string) $base, $tag);
            if ($result !== null) {
                return $result;
            }
        }

        return null;
    }
}
