<?php

namespace MominAlZaraa\FilamentComposerReleaseNotifier\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ComposerReleaseReportMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * @param  array<int, array<string, mixed>>  $outdatedPackages
     */
    public function __construct(
        public int $tracked,
        public int $outdated,
        public int $skipped,
        public array $outdatedPackages,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Composer dependency updates: '.$this->outdated.' package(s) behind latest GitHub release',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'filament-composer-release-notifier::mail.composer-release-report',
        );
    }
}
