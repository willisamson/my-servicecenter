<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\CentralConnection;
use Throwable;

/**
 * Einstellungen der Plattform (Firmendaten, Logo, Rechtliches) – genau ein Datensatz.
 */
class Einstellung extends Model
{
    use CentralConnection;

    protected $table = 'einstellungen';

    protected $fillable = ['daten'];

    protected static ?self $aktuell = null;

    protected function casts(): array
    {
        return ['daten' => 'array'];
    }

    public static function aktuell(): self
    {
        return static::$aktuell ??= static::query()->firstOrCreate(['id' => 1], ['daten' => []]);
    }

    /** Einzelner Wert, z. B. Einstellung::wert('firmenname'). Fehlt die Tabelle noch: Standardwert. */
    public static function wert(string $schluessel, mixed $standard = null): mixed
    {
        try {
            $wert = static::aktuell()->daten[$schluessel] ?? null;
        } catch (Throwable) {
            return $standard;
        }

        return filled($wert) ? $wert : $standard;
    }

    public static function speichern(array $daten): void
    {
        $eintrag = static::aktuell();
        $eintrag->update(['daten' => $daten]);
        static::$aktuell = $eintrag;
    }

    public static function logoUrl(): ?string
    {
        $logo = static::wert('logo');

        return $logo ? asset('storage/'.$logo) : null;
    }
}
