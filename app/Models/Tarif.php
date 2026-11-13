<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * Paket, das ein Betrieb bucht, z. B. "Start" für 49 € netto im Monat.
 */
class Tarif extends Model
{
    use CentralConnection;

    protected $table = 'tarife';

    protected $fillable = ['name', 'beschreibung', 'preis_netto', 'aktiv', 'sortierung'];

    protected function casts(): array
    {
        return [
            'preis_netto' => 'decimal:2',
            'aktiv' => 'boolean',
        ];
    }

    public function abos(): HasMany
    {
        return $this->hasMany(Abo::class);
    }
}
