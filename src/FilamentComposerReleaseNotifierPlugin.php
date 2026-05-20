<?php

namespace MominAlZaraa\FilamentComposerReleaseNotifier;

use Filament\Contracts\Plugin;
use Filament\Panel;

class FilamentComposerReleaseNotifierPlugin implements Plugin
{
    protected ?bool $resourceEnabled = null;

    protected ?bool $widgetEnabled = null;

    protected ?bool $mailReportsEnabled = null;

    public static function make(): static
    {
        return new static;
    }

    public function getId(): string
    {
        return 'filament-composer-release-notifier';
    }

    public function resource(bool $enabled = true): static
    {
        $this->resourceEnabled = $enabled;

        return $this;
    }

    public function widget(bool $enabled = true): static
    {
        $this->widgetEnabled = $enabled;

        return $this;
    }

    public function mailReports(bool $enabled = true): static
    {
        $this->mailReportsEnabled = $enabled;

        return $this;
    }

    public function register(Panel $panel): void
    {
        if ($this->resourceEnabled !== null) {
            config(['filament-composer-release-notifier.features.resource' => $this->resourceEnabled]);
        }
        if ($this->widgetEnabled !== null) {
            config(['filament-composer-release-notifier.features.widget' => $this->widgetEnabled]);
        }
        if ($this->mailReportsEnabled !== null) {
            config(['filament-composer-release-notifier.features.mail_report' => $this->mailReportsEnabled]);
        }

        if ($this->isFeatureEnabled('resource')) {
            $panel->discoverResources(
                in: __DIR__.'/Filament/Resources',
                for: 'MominAlZaraa\\FilamentComposerReleaseNotifier\\Filament\\Resources',
            );
        }

        if ($this->isFeatureEnabled('widget')) {
            $panel->discoverWidgets(
                in: __DIR__.'/Filament/Widgets',
                for: 'MominAlZaraa\\FilamentComposerReleaseNotifier\\Filament\\Widgets',
            );
        }
    }

    public function boot(Panel $panel): void
    {
        //
    }

    protected function isFeatureEnabled(string $key): bool
    {
        return (bool) config("filament-composer-release-notifier.features.{$key}", false);
    }
}
