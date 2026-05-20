<?php

namespace MominAlZaraa\FilamentComposerReleaseNotifier\Filament\Resources;

use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use MominAlZaraa\FilamentComposerReleaseNotifier\Filament\Resources\Pages\ListComposerReleasePackages;
use MominAlZaraa\FilamentComposerReleaseNotifier\Models\ComposerReleasePackageSnapshot;

class ComposerReleasePackageResource extends Resource
{
    protected static ?string $model = ComposerReleasePackageSnapshot::class;

    protected static bool $shouldSkipAuthorization = true;

    public static function getNavigationIcon(): string|\BackedEnum|Htmlable|null
    {
        return Heroicon::OutlinedCube;
    }

    public static function getNavigationLabel(): string
    {
        return __('Composer packages');
    }

    public static function getModelLabel(): string
    {
        return __('Composer package');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Composer packages');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('System');
    }

    public static function getNavigationSort(): ?int
    {
        return 200;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->orderBy('package_name');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('package_name')
                    ->label(__('Package'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('installed_version')
                    ->label(__('Current version'))
                    ->sortable()
                    ->badge()
                    ->color('gray'),
                TextColumn::make('latest_release_tag')
                    ->label(__('Latest release'))
                    ->sortable()
                    ->badge()
                    ->color(fn ($state, ComposerReleasePackageSnapshot $record): string => $record->is_outdated ? 'warning' : 'success'),
                TextColumn::make('synced_at')
                    ->label(__('Last synced'))
                    ->since()
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('releaseDetails')
                    ->label(__('Details'))
                    ->icon(Heroicon::OutlinedInformationCircle)
                    ->modalHeading(fn (ComposerReleasePackageSnapshot $record): string => $record->package_name)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('Close'))
                    ->modalWidth(Width::SevenExtraLarge)
                    ->modalContent(fn (ComposerReleasePackageSnapshot $record): \Illuminate\Contracts\View\View => view(
                        'filament-composer-release-notifier::filament.modals.package-release-details',
                        ['record' => $record],
                    )),
            ])
            ->defaultSort('package_name');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListComposerReleasePackages::route('/'),
        ];
    }
}
