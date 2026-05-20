<?php

namespace MominAlZaraa\FilamentComposerReleaseNotifier\Listeners;

use Illuminate\Auth\Events\Logout;

class ClearComposerReleaseNotifierSessionOnLogout
{
    public function handle(Logout $event): void
    {
        session()->forget('filament_composer_release_notifier_synced');
    }
}
