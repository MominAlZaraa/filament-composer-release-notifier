<?php

namespace MominAlZaraa\FilamentComposerReleaseNotifier\Services;

class ComposerLockReader
{
    /**
     * @return array<string, array{version: string, source_url: ?string}>
     */
    public function readLockedPackages(string $composerJsonPath, string $composerLockPath): array
    {
        if (! is_file($composerJsonPath) || ! is_file($composerLockPath)) {
            return [];
        }

        try {
            $jsonRaw = file_get_contents($composerJsonPath);
            $lockRaw = file_get_contents($composerLockPath);
            if ($jsonRaw === false || $lockRaw === false) {
                return [];
            }
            $json = json_decode($jsonRaw, true, 512, JSON_THROW_ON_ERROR);
            $lock = json_decode($lockRaw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return [];
        }

        if (! is_array($json) || ! is_array($lock)) {
            return [];
        }

        $required = array_merge(
            array_keys($json['require'] ?? []),
            array_keys($json['require-dev'] ?? []),
        );

        $required = array_values(array_filter($required, fn (string $name) => $name !== 'php' && ! str_starts_with($name, 'ext-')));

        $lockedByName = [];
        foreach ($lock['packages'] ?? [] as $pkg) {
            if (isset($pkg['name'])) {
                $lockedByName[$pkg['name']] = $pkg;
            }
        }

        $result = [];
        foreach ($required as $name) {
            if (! isset($lockedByName[$name])) {
                continue;
            }
            $pkg = $lockedByName[$name];
            $sourceUrl = $pkg['source']['url'] ?? null;
            $result[$name] = [
                'version' => (string) ($pkg['version'] ?? ''),
                'source_url' => is_string($sourceUrl) ? $sourceUrl : null,
            ];
        }

        return $result;
    }
}
