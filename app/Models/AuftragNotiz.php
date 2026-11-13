<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Notiz mit Fotos zu einem Auftrag (z. B. "Ventil getauscht", Foto der Anlage).
 * Fotos liegen privat unter <betrieb>/fotos/… und sind nur über signierte Links abrufbar.
 */
class AuftragNotiz extends Model
{
    protected $table = 'auftrag_notizen';

    protected $fillable = ['anfrage_id', 'user_id', 'text', 'fotos'];

    protected function casts(): array
    {
        return ['fotos' => 'array'];
    }

    protected static function booted(): void
    {
        static::creating(fn (AuftragNotiz $n) => $n->user_id ??= auth()->id());

        // Fotos mitlöschen
        static::deleted(function (AuftragNotiz $n) {
            if (! empty($n->fotos)) {
                Storage::disk('betriebe_privat')->delete($n->fotos);
            }
        });

        // Beim Bearbeiten entfernte Fotos löschen
        static::updated(function (AuftragNotiz $n) {
            $alt = (array) ($n->getOriginal('fotos') ?? []);
            $weg = array_diff($alt, (array) ($n->fotos ?? []));
            if ($weg) {
                Storage::disk('betriebe_privat')->delete(array_values($weg));
            }
        });
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
