<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Verbrauchtes Material an einem Auftrag – Grundlage für die spätere Rechnung des Betriebs.
 */
class AuftragMaterial extends Model
{
    protected $table = 'auftrag_material';

    protected $fillable = ['anfrage_id', 'user_id', 'bezeichnung', 'menge', 'einheit'];

    public const EINHEITEN = [
        'Stk' => 'Stück',
        'm' => 'Meter',
        'm²' => 'Quadratmeter',
        'kg' => 'Kilogramm',
        'l' => 'Liter',
        'Pkg' => 'Packung',
        'Std' => 'Stunden',
    ];

    protected function casts(): array
    {
        return ['menge' => 'decimal:2'];
    }

    protected static function booted(): void
    {
        static::creating(fn (AuftragMaterial $m) => $m->user_id ??= auth()->id());
    }

    public function anfrage(): BelongsTo
    {
        return $this->belongsTo(Anfrage::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
