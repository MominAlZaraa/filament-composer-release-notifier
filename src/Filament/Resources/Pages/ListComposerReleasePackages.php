<?php

namespace MominAlZaraa\FilamentComposerReleaseNotifier\Filament\Resources\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use MominAlZaraa\FilamentComposerReleaseNotifier\Filament\Resources\ComposerReleasePackageResource;
use MominAlZaraa\FilamentComposerReleaseNotifier\Jobs\SyncComposerReleaseSnapshotsJob;

class ListComposerReleasePackages extends ListRecords
{
    protected static string $resource = ComposerReleasePackageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('checkComposerReleasesAgain')
                ->label(__('Check again'))
                ->tooltip(__('Re-scan composer files and GitHub for the latest releases'))
                ->icon(Heroicon::OutlinedArrowPath)
                ->requiresConfirmation()
                ->modalHeading(__('Check for newer releases?'))
                ->modalDescription(__('This re-reads composer.json / composer.lock and queries GitHub. Large projects may take a little while.'))
                ->modalSubmitActionLabel(__('Run check'))
                ->action(function (): void {
                    session()->forget('filament_composer_release_notifier_synced');

                    try {
                        SyncComposerReleaseSnapshotsJob::dispatchSync();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title(__('Composer release check failed'))
                            ->body($e->getMessage())
                            ->danger()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title(__('Composer releases refreshed'))
                        ->body(__('Snapshot data was updated from GitHub.'))
                        ->success()
                        ->send();

                    $this->resetTable();
                }),
        ];
    }
}
