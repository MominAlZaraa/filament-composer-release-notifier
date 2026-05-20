<?php

use MominAlZaraa\FilamentComposerReleaseNotifier\Services\ComposerLockReader;

it('reads intersected packages from composer files', function () {
    $reader = new ComposerLockReader;
    $base = dirname(__DIR__).'/fixtures';

    $packages = $reader->readLockedPackages($base.'/composer.json', $base.'/composer.lock');

    expect($packages)->toHaveKey('vendor-a/pkg-one')
        ->and($packages['vendor-a/pkg-one']['version'])->toBe('1.0.0')
        ->and($packages['vendor-a/pkg-one']['source_url'])->toBe('https://github.com/vendor-a/pkg-one.git');
});
