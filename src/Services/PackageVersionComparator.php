<?php

namespace MominAlZaraa\FilamentComposerReleaseNotifier\Services;

class PackageVersionComparator
{
    public function isOutdated(string $installedVersion, ?string $latestTag): bool
    {
        if ($latestTag === null || $latestTag === '') {
            return false;
        }

        if ($installedVersion === '' || str_starts_with($installedVersion, 'dev-')) {
            return false;
        }

        $installed = $this->normalize($installedVersion);
        $latest = $this->normalize($latestTag);

        if ($installed === null || $latest === null) {
            return trim($installedVersion, "v \t\n\r\0\x0B") !== trim($latestTag, "v \t\n\r\0\x0B");
        }

        return version_compare($installed, $latest, '<');
    }

    protected function normalize(string $version): ?string
    {
        $v = trim($version);
        $v = preg_replace('/^v+/i', '', $v) ?? $v;
        // Strip stability suffix for comparison (e.g. 1.0.0-patch1)
        $v = preg_replace('/[\-_].*$/', '', $v) ?? $v;

        if (! preg_match('/^\d+(\.\d+)*$/', $v)) {
            return null;
        }

        return $v;
    }
}
