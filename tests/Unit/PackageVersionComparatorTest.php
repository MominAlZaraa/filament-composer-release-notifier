<?php

use MominAlZaraa\FilamentComposerReleaseNotifier\Services\PackageVersionComparator;

it('detects semver outdated', function () {
    $c = new PackageVersionComparator;

    expect($c->isOutdated('1.0.0', 'v1.1.0'))->toBeTrue()
        ->and($c->isOutdated('v1.1.0', 'v1.1.0'))->toBeFalse()
        ->and($c->isOutdated('dev-main', 'v1.0.0'))->toBeFalse();
});
