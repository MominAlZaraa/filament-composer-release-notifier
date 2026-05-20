<?php

namespace MominAlZaraa\FilamentComposerReleaseNotifier;

use Illuminate\Support\Facades\Event;
use MominAlZaraa\FilamentComposerReleaseNotifier\Listeners\ClearComposerReleaseNotifierSessionOnLogout;
use MominAlZaraa\FilamentComposerReleaseNotifier\Listeners\QueueComposerReleaseSyncOnLogin;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentComposerReleaseNotifierServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('filament-composer-release-notifier')
            ->hasConfigFile()
            ->discoversMigrations(true, 'database/migrations')
            ->hasViews();
    }

    public function packageBooted(): void
    {
        Event::listen(
            \Illuminate\Auth\Events\Login::class,
            QueueComposerReleaseSyncOnLogin::class,
        );

        Event::listen(
            \Illuminate\Auth\Events\Logout::class,
            ClearComposerReleaseNotifierSessionOnLogout::class,
        );
    }
}
