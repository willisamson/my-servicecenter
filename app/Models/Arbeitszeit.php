<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Ein Stück Arbeitszeit: von Einstempeln bis Ausstempeln, optional zu einem Auftrag.
 * Pausen = ausstempeln und später wieder einstempeln.
 */
class Arbeitszeit extends Model
{
    protected $table = 'arbeitszeiten';

    protected $fillable = ['user_id', 'anfrage_id', 'beginn', 'ende', 'notiz'];

    protected function casts(): array
    {
        return [
            'beginn' => 'datetime',
            'ende' => 'datetime',
            'minuten' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Arbeitszeit $z) {
            $z->minuten = $z->ende && $z->beginn
                ? max(0, (int) floor($z->beginn->diffInMinutes($z->ende, false)))
                : null;
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function anfrage(): BelongsTo
    {
        return $this->belongsTo(Anfrage::class);
    }

    /** Laufender Eintrag des Benutzers (eingestempelt, noch nicht ausgestempelt). */
    public static function laufend(int $userId): ?self
    {
        return static::query()->where('user_id', $userId)->whereNull('ende')->latest('beginn')->first();
    }

    /** Einstempeln – ein noch laufender Eintrag wird vorher beendet (Auftrag wechseln). */
    public static function einstempeln(int $userId, ?int $anfrageId = null, ?string $notiz = null): self
    {
        static::ausstempeln($userId);

        return static::create([
            'user_id' => $userId,
            'anfrage_id' => $anfrageId,
            'beginn' => now(),
            'notiz' => $notiz,
        ]);
    }

    public static function ausstempeln(int $userId): ?self
    {
        $laufend = static::laufend($userId);
        $laufend?->update(['ende' => now()]);

        return $laufend;
    }

    /** Minuten eines Benutzers in einem Zeitraum (laufender Eintrag zählt bis jetzt). */
    public static function minutenZwischen(int $userId, Carbon $von, Carbon $bis): int
    {
        return (int) static::query()
            ->where('user_id', $userId)
            ->where('beginn', '>=', $von)
            ->where('beginn', '<', $bis)
            ->get()
            ->sum(fn (Arbeitszeit $z) => $z->minuten ?? max(0, (int) floor($z->beginn->diffInMinutes(now(), false))));
    }

    /** 135 → "2:15 h" */
    public static function format(?int $minuten): string
    {
        if ($minuten === null) {
            return 'läuft';
        }

        return intdiv($minuten, 60).':'.str_pad((string) ($minuten % 60), 2, '0', STR_PAD_LEFT).' h';
    }
}
