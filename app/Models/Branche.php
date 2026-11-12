<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * Branche (Kategorie) der Plattform, z. B. Heizung, Friseur, Floristik.
 * Liegt immer in der zentralen Datenbank – auch wenn gerade ein Betrieb aktiv ist.
 */
class Branche extends Model
{
    use CentralConnection;

    protected $table = 'branchen';

    protected $fillable = ['slug', 'name', 'anliegen', 'aktiv', 'sortierung'];

    /** Zwischenspeicher pro Anfrage, damit nicht jede Zeile eine Abfrage auslöst. */
    protected static ?array $alle = null;

    protected function casts(): array
    {
        return [
            'anliegen' => 'array',
            'aktiv' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => static::$alle = null);
        static::deleted(fn () => static::$alle = null);
    }

    protected static function alle(): array
    {
        return static::$alle ??= static::query()
            ->orderBy('sortierung')
            ->orderBy('name')
            ->get()
            ->keyBy('slug')
            ->all();
    }

    /** slug => Name, für Auswahlfelder. */
    public static function optionen(bool $nurAktive = true): array
    {
        $optionen = [];

        foreach (static::alle() as $slug => $branche) {
            if ($nurAktive && ! $branche->aktiv) {
                continue;
            }
            $optionen[$slug] = $branche->name;
        }

        return $optionen;
    }

    public static function finde(?string $slug): ?self
    {
        return $slug !== null ? (static::alle()[$slug] ?? null) : null;
    }

    public static function label(?string $slug): string
    {
        return isset(static::alle()[$slug]) ? static::alle()[$slug]->name : (string) $slug;
    }

    /** Anliegen für das Anfrageformular einer Branche (Kürzel => Bezeichnung). */
    public static function anliegenFuer(?string $slug): array
    {
        $anliegen = static::alle()[$slug]->anliegen ?? null;

        return ! empty($anliegen) ? $anliegen : ['sonstiges' => 'Anfrage'];
    }

    public function anzahlBetriebe(): int
    {
        return Tenant::where('branche', $this->slug)->count();
    }
}
