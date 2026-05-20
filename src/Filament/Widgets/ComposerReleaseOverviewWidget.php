<?php

namespace MominAlZaraa\FilamentComposerReleaseNotifier\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use MominAlZaraa\FilamentComposerReleaseNotifier\Models\ComposerReleasePackageSnapshot;

class ComposerReleaseOverviewWidget extends StatsOverviewWidget
{
    protected int | string | array $columnSpan = 'full';

    protected function getHeading(): ?string
    {
        return __('Composer releases');
    }

    public static function canView(): bool
    {
        return auth()->check();
    }

    protected function getStats(): array
    {
        $tracked = ComposerReleasePackageSnapshot::query()->count();
        $outdated = ComposerReleasePackageSnapshot::query()->where('is_outdated', true)->count();

        return [
            Stat::make(__('Tracked packages'), (string) $tracked)
                ->description(__('From composer.json / composer.lock on GitHub'))
                ->color('primary'),
            Stat::make(__('Behind latest release'), (string) $outdated)
                ->description(__('Informational — no auto-updates'))
                ->color($outdated > 0 ? 'warning' : 'success'),
        ];
    }
}
