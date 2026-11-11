<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'rolle'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public const ROLLEN = [
        'inhaber' => 'Inhaber',
        'buero' => 'Büro',
        'mitarbeiter' => 'Mitarbeiter',
    ];

    /**
     * Wer darf in welches Filament-Panel?
     * - admin:   nur auf der zentralen Domain (Benutzer der zentralen Datenbank)
     * - betrieb: nur auf der Subdomain eines Betriebs (Benutzer aus dessen Datenbank)
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'admin' => ! tenancy()->initialized,
            'betrieb' => tenancy()->initialized,
            default => false,
        };
    }

    public function istInhaber(): bool
    {
        return $this->rolle === 'inhaber';
    }

    public function istBueroOderInhaber(): bool
    {
        return in_array($this->rolle, ['inhaber', 'buero'], true);
    }
}
