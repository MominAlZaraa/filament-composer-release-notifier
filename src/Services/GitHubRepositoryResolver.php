<?php

namespace MominAlZaraa\FilamentComposerReleaseNotifier\Services;

class GitHubRepositoryResolver
{
    /**
     * @return array{owner: string, repo: string}|null
     */
    public function resolve(?string $sourceUrl): ?array
    {
        if ($sourceUrl === null || $sourceUrl === '') {
            return null;
        }

        if (str_starts_with($sourceUrl, 'git@github.com:')) {
            $path = substr($sourceUrl, strlen('git@github.com:'));
            $path = preg_replace('#\.git$#', '', $path) ?? $path;

            return $this->splitOwnerRepo($path);
        }

        if (str_contains($sourceUrl, 'github.com')) {
            $parts = parse_url($sourceUrl);
            $path = $parts['path'] ?? '';
            $path = trim($path, '/');
            $path = preg_replace('#\.git$#', '', $path) ?? $path;

            return $this->splitOwnerRepo($path);
        }

        return null;
    }

    /**
     * @return array{owner: string, repo: string}|null
     */
    protected function splitOwnerRepo(string $path): ?array
    {
        $segments = explode('/', $path);
        if (count($segments) < 2) {
            return null;
        }

        return [
            'owner' => $segments[0],
            'repo' => $segments[1],
        ];
    }
}
