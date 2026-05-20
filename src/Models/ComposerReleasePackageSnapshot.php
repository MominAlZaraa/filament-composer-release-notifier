<?php

namespace MominAlZaraa\FilamentComposerReleaseNotifier\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $package_name
 * @property string $repository_owner
 * @property string $repository_name
 * @property string $installed_version
 * @property string|null $latest_release_tag
 * @property bool $is_outdated
 * @property string|null $compare_html_url
 * @property string|null $release_notes
 * @property array|null $commits_payload
 * @property string|null $last_error
 * @property \Illuminate\Support\Carbon|null $synced_at
 */
class ComposerReleasePackageSnapshot extends Model
{
    protected $table = 'composer_release_package_snapshots';

    protected $fillable = [
        'package_name',
        'repository_owner',
        'repository_name',
        'installed_version',
        'latest_release_tag',
        'is_outdated',
        'compare_html_url',
        'release_notes',
        'commits_payload',
        'last_error',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'is_outdated' => 'boolean',
            'commits_payload' => 'array',
            'synced_at' => 'datetime',
        ];
    }
}
