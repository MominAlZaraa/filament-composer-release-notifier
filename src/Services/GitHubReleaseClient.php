<?php

namespace MominAlZaraa\FilamentComposerReleaseNotifier\Services;

use Illuminate\Support\Facades\Http;

class GitHubReleaseClient
{
    /**
     * Public GitHub **website** compare URL (no API token). May 404 if refs are invalid.
     */
    public static function compareWebUrl(string $owner, string $repo, string $base, string $head): string
    {
        return 'https://github.com/'.rawurlencode($owner).'/'.rawurlencode($repo).'/compare/'
            .rawurlencode($base).'...'.rawurlencode($head);
    }

    public function getLatestRelease(string $owner, string $repo): ?array
    {
        $url = "https://api.github.com/repos/{$owner}/{$repo}/releases/latest";

        try {
            $response = $this->http()->get($url);
            if ($response->status() === 404) {
                return null;
            }
            $response->throw();

            return $response->json();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array{html_url: ?string, commits: array<int, array<string, mixed>>}|null
     */
    public function compare(string $owner, string $repo, string $base, string $head): ?array
    {
        $basehead = rawurlencode($base).'...'.rawurlencode($head);
        $url = "https://api.github.com/repos/{$owner}/{$repo}/compare/{$basehead}";

        try {
            $response = $this->http()->get($url);
            if ($response->status() === 404) {
                return null;
            }
            $response->throw();
            $data = $response->json();

            return [
                'html_url' => is_string($data['html_url'] ?? null) ? $data['html_url'] : null,
                'commits' => is_array($data['commits'] ?? null) ? $data['commits'] : [],
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    protected function http(): \Illuminate\Http\Client\PendingRequest
    {
        $token = config('filament-composer-release-notifier.github.token');
        $request = Http::timeout((int) config('filament-composer-release-notifier.github.http_timeout', 15))
            ->withHeaders([
                'Accept' => 'application/vnd.github+json',
                'User-Agent' => (string) config('filament-composer-release-notifier.github.user_agent'),
            ]);

        if (is_string($token) && $token !== '') {
            $request = $request->withToken($token);
        }

        return $request;
    }
}
