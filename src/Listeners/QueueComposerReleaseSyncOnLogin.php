<?php

namespace MominAlZaraa\FilamentComposerReleaseNotifier\Listeners;

use Filament\Models\Contracts\FilamentUser;
use Illuminate\Auth\Events\Login;
use MominAlZaraa\FilamentComposerReleaseNotifier\Jobs\SyncComposerReleaseSnapshotsJob;

class QueueComposerReleaseSyncOnLogin
{
    public function handle(Login $event): void
    {
        if (! (bool) config('filament-composer-release-notifier.enabled', true)) {
            return;
        }

        $user = $event->user;
        if (! $user instanceof FilamentUser) {
            return;
        }

        if (session()->get('filament_composer_release_notifier_synced')) {
            return;
        }

        session()->put('filament_composer_release_notifier_synced', true);

        SyncComposerReleaseSnapshotsJob::dispatch();
    }
}
