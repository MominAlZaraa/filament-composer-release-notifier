<?php

namespace MominAlZaraa\FilamentComposerReleaseNotifier\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use MominAlZaraa\FilamentComposerReleaseNotifier\Mail\ComposerReleaseReportMail;
use MominAlZaraa\FilamentComposerReleaseNotifier\Models\ComposerReleasePackageSnapshot;
use MominAlZaraa\FilamentComposerReleaseNotifier\Services\ComposerReleaseSyncService;

class SyncComposerReleaseSnapshotsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct()
    {
        $queue = config('filament-composer-release-notifier.sync.queue');
        if (is_string($queue) && $queue !== '') {
            $this->onQueue($queue);
        }

        $connection = config('filament-composer-release-notifier.sync.connection');
        if (is_string($connection) && $connection !== '') {
            $this->onConnection($connection);
        }
    }

    public function handle(ComposerReleaseSyncService $syncService): void
    {
        if (! (bool) config('filament-composer-release-notifier.enabled', true)) {
            return;
        }

        $stats = $syncService->sync();

        $this->maybeSendMail($stats);
    }

    /**
     * @param  array{tracked: int, outdated: int, skipped: int}  $stats
     */
    protected function maybeSendMail(array $stats): void
    {
        if (! (bool) config('filament-composer-release-notifier.features.mail_report', false)) {
            return;
        }

        $mode = (string) config('filament-composer-release-notifier.mail.recipient_mode', 'none');
        if ($mode === 'none') {
            return;
        }

        if (! $this->mailDriverAllowed()) {
            return;
        }

        $sendWhen = (string) config('filament-composer-release-notifier.mail.send_when', 'when_outdated');
        if ($sendWhen === 'when_outdated' && $stats['outdated'] === 0) {
            return;
        }

        $hours = max(1, (int) config('filament-composer-release-notifier.mail.throttle_hours', 24));
        $cacheKey = 'filament-composer-release-notifier:last-mail-sent';

        if (Cache::has($cacheKey)) {
            return;
        }

        $recipients = $this->resolveRecipients($mode);
        if ($recipients === []) {
            return;
        }

        $outdatedPackages = ComposerReleasePackageSnapshot::query()
            ->where('is_outdated', true)
            ->orderBy('package_name')
            ->get(['package_name', 'installed_version', 'latest_release_tag', 'compare_html_url'])
            ->map(fn (ComposerReleasePackageSnapshot $r) => $r->only([
                'package_name',
                'installed_version',
                'latest_release_tag',
                'compare_html_url',
            ]))
            ->values()
            ->all();

        foreach ($recipients as $email) {
            Mail::to($email)->send(new ComposerReleaseReportMail(
                tracked: $stats['tracked'],
                outdated: $stats['outdated'],
                skipped: $stats['skipped'],
                outdatedPackages: $outdatedPackages,
            ));
        }

        Cache::put($cacheKey, true, now()->addHours($hours));
    }

    protected function mailDriverAllowed(): bool
    {
        $mailer = (string) config('mail.default', 'log');
        $allowLog = (bool) config('filament-composer-release-notifier.mail.allow_log_driver', false);

        if (in_array($mailer, ['log', 'array'], true) && ! $allowLog) {
            return false;
        }

        return true;
    }

    /**
     * @return list<string>
     */
    protected function resolveRecipients(string $mode): array
    {
        if ($mode === 'specific_emails') {
            $emails = config('filament-composer-release-notifier.mail.specific_emails', []);

            return array_values(array_unique(array_filter(
                is_array($emails) ? $emails : [],
                fn (mixed $e): bool => is_string($e) && filter_var($e, FILTER_VALIDATE_EMAIL),
            )));
        }

        if ($mode === 'all_panel_users') {
            try {
                $class = config('filament-composer-release-notifier.mail.user_model', 'App\Models\User');
                if (! is_string($class) || ! class_exists($class)) {
                    return [];
                }

                /** @var \Illuminate\Database\Eloquent\Model $model */
                $model = new $class;
                $query = $model->newQuery();
                $table = $query->getModel()->getTable();
                if (! Schema::connection($model->getConnectionName())->hasColumn($table, 'email')) {
                    return [];
                }

                return $query->whereNotNull('email')->where('email', '!=', '')->pluck('email')->unique()->filter()->values()->all();
            } catch (\Throwable) {
                return [];
            }
        }

        return [];
    }
}
