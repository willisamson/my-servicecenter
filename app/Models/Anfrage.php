<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
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
    ];

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

    public const ANLIEGEN = [
        'heizung' => [
            'stoerung' => 'Heizung heizt nicht / Störung',
            'wartung' => 'Wartung',
            'tausch' => 'Heizungstausch',
            'waermepumpe' => 'Wärmepumpe',
            'sonstiges' => 'Sonstiges',
        ],
        'garten' => [
            'pflege' => 'Gartenpflege',
            'neuanlage' => 'Neuanlage / Umgestaltung',
            'schnitt' => 'Baum- und Heckenschnitt',
            'sonstiges' => 'Sonstiges',
        ],
        'floristik' => [
            'strauss' => 'Blumenstrauß',
            'anlass' => 'Hochzeit / Feier',
            'trauer' => 'Trauerfloristik',
            'sonstiges' => 'Sonstiges',
        ],
    ];

    public static function anliegenFuer(?string $branche): array
    {
        return self::ANLIEGEN[$branche] ?? ['sonstiges' => 'Anfrage'];
    }

    protected static function booted(): void
    {
        static::creating(function (Anfrage $anfrage) {
            $anfrage->status_token ??= Str::random(40);
            $anfrage->status ??= 'neu';
        });
    }

    protected function casts(): array
    {
        return [
            'termin_am' => 'datetime',
        ];
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
