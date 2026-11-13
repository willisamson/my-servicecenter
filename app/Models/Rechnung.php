<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * Rechnung an einen Betrieb. Nach dem Erstellen unveränderlich:
 * Korrekturen nur über eine Stornorechnung (GoBD).
 */
class Rechnung extends Model
{
    use CentralConnection;

    protected $table = 'rechnungen';

    protected $guarded = ['id'];

    public const STATUS = [
        'offen' => 'Offen',
        'bezahlt' => 'Bezahlt',
        'storniert' => 'Storniert',
    ];

    public const STATUS_FARBEN = [
        'offen' => 'warning',
        'bezahlt' => 'success',
        'storniert' => 'gray',
    ];

    protected function casts(): array
    {
        return [
            'datum' => 'date',
            'leistung_von' => 'date',
            'leistung_bis' => 'date',
            'faellig_am' => 'date',
            'bezahlt_am' => 'date',
            'versendet_am' => 'datetime',
            'absender' => 'array',
            'empfaenger' => 'array',
            'positionen' => 'array',
            'netto' => 'decimal:2',
            'ust_satz' => 'decimal:2',
            'ust' => 'decimal:2',
            'brutto' => 'decimal:2',
        ];
    }

    public function abo(): BelongsTo
    {
        return $this->belongsTo(Abo::class);
    }

    public function betrieb(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function stornoVon(): BelongsTo
    {
        return $this->belongsTo(self::class, 'storno_von_id');
    }

    public function istStorno(): bool
    {
        return $this->art === 'storno';
    }

    public function istUeberfaellig(): bool
    {
        return $this->status === 'offen' && ! $this->istStorno() && $this->faellig_am?->isPast() && ! $this->faellig_am->isToday();
    }

    public function titel(): string
    {
        return $this->istStorno() ? 'Stornorechnung' : 'Rechnung';
    }

    public function dateiname(): string
    {
        return $this->titel().'-'.$this->nummer.'.pdf';
    }

    public static function euro(float|string|null $betrag): string
    {
        return number_format((float) $betrag, 2, ',', '.').' €';
    }
}
