<?php

namespace MominAlZaraa\FilamentComposerReleaseNotifier\Tests\Support;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * @property string $email
 * @property string $name
 * @property string $password
 */
class FilamentTestUser extends Authenticatable implements FilamentUser
{
    protected $fillable = ['name', 'email', 'password'];

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }
}
