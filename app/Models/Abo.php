<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * Abo eines Betriebs. Abgerechnet wird im Voraus, jeweils für einen Monat
 * ab "naechste_rechnung_am".
 */
class Abo extends Model
{
    use CentralConnection;

    protected $table = 'abos';

    protected $fillable = [
        'tenant_id', 'tarif_id', 'preis_netto', 'intervall_monate', 'start_am', 'kostenlos_bis',
        'naechste_rechnung_am', 'gekuendigt_zum', 'rechnung_email', 'rechnung_adresse', 'notiz',
    ];

    protected function casts(): array
    {
        return [
            'preis_netto' => 'decimal:2',
            'start_am' => 'date',
            'kostenlos_bis' => 'date',
            'naechste_rechnung_am' => 'date',
            'gekuendigt_zum' => 'date',
        ];
    }

    protected static function booted(): void
    {
        // Ohne Angabe: erste Rechnung am Tag nach der Testphase, sonst ab Start.
        static::saving(function (Abo $abo) {
            $abo->intervall_monate = max(1, (int) ($abo->intervall_monate ?: 1));

            if ($abo->naechste_rechnung_am === null && $abo->start_am) {
                $abo->naechste_rechnung_am = $abo->kostenlos_bis && $abo->kostenlos_bis->gte($abo->start_am)
                    ? $abo->kostenlos_bis->copy()->addDay()
                    : $abo->start_am->copy();
            }
        });
    }

    public function betrieb(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function tarif(): BelongsTo
    {
        return $this->belongsTo(Tarif::class);
    }

    public function rechnungen(): HasMany
    {
        return $this->hasMany(Rechnung::class);
    }

    /** Monatspreis netto: eigener Preis oder Tarifpreis. */
    public function monatspreis(): float
    {
        return (float) ($this->preis_netto ?? $this->tarif?->preis_netto ?? 0);
    }

    /** Ende des Zeitraums, der ab $von abgerechnet wird. */
    public function zeitraumEnde(Carbon $von): Carbon
    {
        return $von->copy()->addMonthsNoOverflow($this->intervall_monate ?: 1)->subDay();
    }

    /** Wird zum Termin $tag noch abgerechnet? */
    public function laeuftAm(Carbon $tag): bool
    {
        return $this->gekuendigt_zum === null || $tag->lte($this->gekuendigt_zum);
    }

    public function statusLabel(): string
    {
        $heute = today();

        return match (true) {
            $this->gekuendigt_zum !== null && $this->gekuendigt_zum->lt($heute) => 'beendet',
            $this->gekuendigt_zum !== null => 'gekündigt',
            $this->kostenlos_bis !== null && $this->kostenlos_bis->gte($heute) => 'Testphase',
            default => 'aktiv',
        };
    }

    /** Abos, für die heute (oder früher) eine Rechnung fällig ist. */
    public function scopeFaellig(Builder $query, ?Carbon $stichtag = null): Builder
    {
        $stichtag ??= today();

        return $query
            ->whereNotNull('naechste_rechnung_am')
            ->whereDate('naechste_rechnung_am', '<=', $stichtag)
            ->where(fn (Builder $q) => $q->whereNull('gekuendigt_zum')->orWhereColumn('naechste_rechnung_am', '<=', 'gekuendigt_zum'));
    }
}
