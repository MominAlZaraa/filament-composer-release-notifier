<?php

namespace MominAlZaraa\FilamentComposerReleaseNotifier\Services;

use Illuminate\Support\Facades\Http;

class PackagistVersionClient
{
    /**
     * Fetch latest stable semver from Packagist Composer 2 metadata (p2), no API key.
     *
     * @return array{version: string, release_notes: ?string}|null
     */
    public function getLatestStable(string $packageName): ?array
    {
        $packageName = strtolower(trim($packageName));
        if ($packageName === '' || ! str_contains($packageName, '/')) {
            return null;
        }

        $base = rtrim((string) config('filament-composer-release-notifier.packagist.base_url', 'https://repo.packagist.org'), '/');
        $url = $base.'/p2/'.$packageName.'.json';

        try {
            $response = Http::timeout((int) config('filament-composer-release-notifier.packagist.http_timeout', 15))
                ->withHeaders([
                    'User-Agent' => (string) config('filament-composer-release-notifier.packagist.user_agent', 'filament-composer-release-notifier'),
                    'Accept' => 'application/json',
                ])
                ->get($url);

            if ($response->status() === 404) {
                return null;
            }
            $response->throw();
            $data = $response->json();
        } catch (\Throwable) {
            return null;
        }

        if (! is_array($data)) {
            return null;
        }

        $packages = $data['packages'] ?? null;
        if (! is_array($packages)) {
            return null;
        }

        $versions = $packages[$packageName] ?? null;
        if (! is_array($versions) || $versions === []) {
            return null;
        }

        $bestDisplay = null;
        $bestComparable = null;
        $releaseNotes = null;

        foreach ($versions as $row) {
            if (! is_array($row)) {
                continue;
            }
            $v = $row['version'] ?? null;
            if (! is_string($v) || $v === '') {
                continue;
            }
            if ($this->shouldSkipVersion($v)) {
                continue;
            }

            $comparable = $this->toComparableSemver($v);
            if ($comparable === null) {
                continue;
            }

            if ($bestComparable === null || version_compare($comparable, $bestComparable, '>')) {
                $bestComparable = $comparable;
                $bestDisplay = $v;
                $readme = $row['readme'] ?? null;
                $desc = $row['description'] ?? null;
                if (is_string($readme) && $readme !== '') {
                    $releaseNotes = mb_substr($readme, 0, 8000);
                } elseif (is_string($desc) && $desc !== '') {
                    $releaseNotes = $desc;
                } else {
                    $releaseNotes = null;
                }
            }
        }

        if ($bestDisplay === null) {
            return null;
        }

        return [
            'version' => $bestDisplay,
            'release_notes' => $releaseNotes,
        ];
    }

    protected function shouldSkipVersion(string $version): bool
    {
        if (str_starts_with($version, 'dev-') || str_contains($version, 'dev-')) {
            return true;
        }

        return (bool) preg_match('/-(alpha|beta|rc|preview)\d*$/i', $version);
    }

    protected function toComparableSemver(string $version): ?string
    {
        $v = ltrim(trim($version), 'vV');

        if (preg_match('/^(\d+(?:\.\d+)*)/', $v, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
