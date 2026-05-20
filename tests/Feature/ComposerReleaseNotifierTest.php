<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use MominAlZaraa\FilamentComposerReleaseNotifier\Jobs\SyncComposerReleaseSnapshotsJob;
use MominAlZaraa\FilamentComposerReleaseNotifier\Mail\ComposerReleaseReportMail;
use MominAlZaraa\FilamentComposerReleaseNotifier\Models\ComposerReleasePackageSnapshot;
use MominAlZaraa\FilamentComposerReleaseNotifier\Services\ComposerReleaseSyncService;
use MominAlZaraa\FilamentComposerReleaseNotifier\Tests\Support\FilamentTestUser;

beforeEach(function () {
    Cache::flush();
});

it('syncs snapshots using github api', function () {
    Http::fake(function (\Illuminate\Http\Client\Request $request) {
        $url = $request->url();
        if (str_contains($url, '/releases/latest')) {
            return Http::response([
                'tag_name' => 'v1.1.0',
                'body' => '## Notes',
            ], 200);
        }
        if (str_contains($url, '/compare/')) {
            return Http::response([
                'html_url' => 'https://github.com/vendor-a/pkg-one/compare/1.0.0...v1.1.0',
                'commits' => [
                    [
                        'sha' => 'abcdef1234567890',
                        'commit' => [
                            'message' => "feat: example\n\nbody",
                            'author' => ['date' => '2026-01-01T00:00:00Z'],
                        ],
                        'html_url' => 'https://github.com/vendor-a/pkg-one/commit/abcdef1',
                    ],
                ],
            ], 200);
        }

        return Http::response([], 404);
    });

    $fixture = dirname(__DIR__).'/fixtures';
    config([
        'filament-composer-release-notifier.version_source' => 'github',
        'filament-composer-release-notifier.composer_json_path' => $fixture.'/composer.json',
        'filament-composer-release-notifier.composer_lock_path' => $fixture.'/composer.lock',
    ]);

    $stats = app(ComposerReleaseSyncService::class)->sync();

    expect($stats['tracked'])->toBe(1)
        ->and($stats['outdated'])->toBe(1);

    $row = ComposerReleasePackageSnapshot::query()->first();
    expect($row)->not->toBeNull()
        ->and($row->package_name)->toBe('vendor-a/pkg-one')
        ->and($row->is_outdated)->toBeTrue()
        ->and($row->latest_release_tag)->toBe('v1.1.0')
        ->and($row->compare_html_url)->toContain('compare');
});

it('syncs snapshots using packagist p2 metadata without github api', function () {
    Http::fake(function (\Illuminate\Http\Client\Request $request) {
        $url = $request->url();
        if (str_contains($url, 'repo.packagist.org/p2/vendor-a/pkg-one.json')) {
            return Http::response([
                'packages' => [
                    'vendor-a/pkg-one' => [
                        ['version' => '1.0.0', 'description' => 'stable'],
                        ['version' => '1.1.0', 'readme' => "## Packagist notes\n\nHello"],
                    ],
                ],
            ], 200);
        }

        return Http::response([], 404);
    });

    $fixture = dirname(__DIR__).'/fixtures';
    config([
        'filament-composer-release-notifier.version_source' => 'packagist',
        'filament-composer-release-notifier.composer_json_path' => $fixture.'/composer.json',
        'filament-composer-release-notifier.composer_lock_path' => $fixture.'/composer.lock',
    ]);

    $stats = app(ComposerReleaseSyncService::class)->sync();

    expect($stats['tracked'])->toBe(1)
        ->and($stats['outdated'])->toBe(1);

    $row = ComposerReleasePackageSnapshot::query()->first();
    expect($row)->not->toBeNull()
        ->and($row->latest_release_tag)->toBe('1.1.0')
        ->and($row->compare_html_url)->toBe('https://github.com/vendor-a/pkg-one/compare/1.0.0...1.1.0')
        ->and($row->commits_payload)->toBeNull()
        ->and($row->release_notes)->toContain('Packagist notes');
});

it('dispatches sync job once per session on login', function () {
    Queue::fake();

    $user = new FilamentTestUser;
    $user->email = 'test@example.com';

    session()->flush();

    auth()->login($user);

    Queue::assertPushed(SyncComposerReleaseSnapshotsJob::class, 1);

    event(new \Illuminate\Auth\Events\Login('web', $user, false));

    Queue::assertPushed(SyncComposerReleaseSnapshotsJob::class, 1);
});

it('does not dispatch for users that are not filament users', function () {
    Queue::fake();

    $user = new \Illuminate\Foundation\Auth\User;
    $user->email = 'plain@example.com';

    session()->flush();

    auth()->login($user);
    event(new \Illuminate\Auth\Events\Login('web', $user, false));

    Queue::assertNothingPushed();
});

it('sends mail when configured', function () {
    Mail::fake();
    Http::fake(function (\Illuminate\Http\Client\Request $request) {
        if (str_contains($request->url(), '/releases/latest')) {
            return Http::response(['tag_name' => 'v1.1.0', 'body' => ''], 200);
        }
        if (str_contains($request->url(), '/compare/')) {
            return Http::response(['html_url' => 'https://example.test/compare', 'commits' => []], 200);
        }

        return Http::response([], 404);
    });

    $fixture = dirname(__DIR__).'/fixtures';
    config([
        'filament-composer-release-notifier.version_source' => 'github',
        'filament-composer-release-notifier.composer_json_path' => $fixture.'/composer.json',
        'filament-composer-release-notifier.composer_lock_path' => $fixture.'/composer.lock',
        'filament-composer-release-notifier.features.mail_report' => true,
        'filament-composer-release-notifier.mail.recipient_mode' => 'specific_emails',
        'filament-composer-release-notifier.mail.specific_emails' => ['ops@example.com'],
        'filament-composer-release-notifier.mail.send_when' => 'when_outdated',
        'filament-composer-release-notifier.mail.allow_log_driver' => true,
        'mail.default' => 'array',
    ]);

    $job = new SyncComposerReleaseSnapshotsJob;
    $job->handle(app(ComposerReleaseSyncService::class));

    Mail::assertSent(ComposerReleaseReportMail::class, function (ComposerReleaseReportMail $mail): bool {
        return $mail->hasTo('ops@example.com');
    });
});
