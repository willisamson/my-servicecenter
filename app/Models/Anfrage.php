<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Anfrage eines Endkunden an einen Betrieb (Datenbank des Betriebs).
 */
class Anfrage extends Model
{
    protected $table = 'anfragen';

    protected $fillable = [
        'status', 'anliegen', 'beschreibung', 'name', 'email', 'telefon',
        'plz', 'adresse', 'wunschtermin', 'termin_am', 'notiz_intern',
        'mitarbeiter_id', 'termin_dauer', 'erledigt_am',
    ];

    /** Status, die ein Mitarbeiter unterwegs selbst setzen darf. */
    public const STATUS_MITARBEITER = ['termin', 'unterwegs', 'erledigt'];

    /** Noch nicht abgeschlossen. */
    public const OFFEN = ['neu', 'angenommen', 'termin', 'unterwegs'];

    /** Reihenfolge = Fortschritt auf der Status-Seite. "abgelehnt" ist ein Sonderfall. */
    public const STATUS = [
        'neu' => 'Neu',
        'angenommen' => 'Angenommen',
        'termin' => 'Termin bestätigt',
        'unterwegs' => 'Monteur unterwegs',
        'erledigt' => 'Erledigt',
        'abgelehnt' => 'Abgelehnt',
    ];

    public const STATUS_FARBEN = [
        'neu' => 'warning',
        'angenommen' => 'info',
        'termin' => 'info',
        'unterwegs' => 'primary',
        'erledigt' => 'success',
        'abgelehnt' => 'gray',
    ];

    public static function anliegenFuer(?string $branche): array
    {
        return Branche::anliegenFuer($branche);
    }

    protected static function booted(): void
    {
        static::creating(function (Anfrage $anfrage) {
            $anfrage->status_token ??= Str::random(40);
            $anfrage->status ??= 'neu';
        });

        static::saving(function (Anfrage $anfrage) {
            // Termin eingetragen → Kunde sieht "Termin bestätigt".
            if ($anfrage->isDirty('termin_am') && $anfrage->termin_am && in_array($anfrage->status, ['neu', 'angenommen'], true)) {
                $anfrage->status = 'termin';
            }

            if ($anfrage->isDirty('status')) {
                $anfrage->erledigt_am = $anfrage->status === 'erledigt' ? ($anfrage->erledigt_am ?? now()) : null;
            }
        });
    }

    protected function casts(): array
    {
        return [
            'termin_am' => 'datetime',
            'erledigt_am' => 'datetime',
            'termin_dauer' => 'integer',
        ];
    }

    public function mitarbeiter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mitarbeiter_id');
    }

    public function notizen(): HasMany
    {
        return $this->hasMany(AuftragNotiz::class)->latest();
    }

    public function material(): HasMany
    {
        return $this->hasMany(AuftragMaterial::class);
    }

    public function arbeitszeiten(): HasMany
    {
        return $this->hasMany(Arbeitszeit::class);
    }

    /** Kurzbezeichnung für Auswahllisten, z. B. "Müller · Heizung ausgefallen · 21.11. 08:00". */
    public function kurztitel(): string
    {
        return collect([
            $this->name,
            $this->anliegenLabel(),
            $this->termin_am?->timezone('Europe/Berlin')->format('d.m. H:i'),
        ])->filter()->implode(' · ');
    }

    /** Google-Maps-Link zur Adresse des Kunden. */
    public function routenLink(): ?string
    {
        $ziel = trim(($this->adresse ?? '').' '.($this->plz ?? ''));

        return $ziel !== '' ? 'https://www.google.com/maps/dir/?api=1&destination='.urlencode($ziel) : null;
    }

    public function statusLabel(): string
    {
        return self::STATUS[$this->status] ?? (string) $this->status;
    }

    public function anliegenLabel(): string
    {
        return self::anliegenFuer(tenant('branche'))[$this->anliegen] ?? (string) $this->anliegen;
    }

    /** Öffentlicher Link, über den der Kunde den Stand verfolgt. */
    protected function statusLink(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->status_token
            ? route('betrieb.status', $this->status_token)
            : null);
    }
}
