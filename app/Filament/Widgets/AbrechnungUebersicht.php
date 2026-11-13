<?php

namespace App\Filament\Widgets;

use App\Models\Abo;
use App\Models\Einstellung;
use App\Models\Rechnung;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Kennzahlen im Admin-Dashboard: Monatsumsatz aus Abos, Ziel, offene Rechnungen.
 */
class AbrechnungUebersicht extends StatsOverviewWidget
{
    protected static ?int $sort = -10;

    protected function getStats(): array
    {
        $heute = today();
        $abos = Abo::query()->with('tarif')->get()
            ->filter(fn (Abo $a) => $a->gekuendigt_zum === null || $a->gekuendigt_zum->gte($heute));

        $zahlend = $abos->filter(fn (Abo $a) => $a->kostenlos_bis === null || $a->kostenlos_bis->lt($heute));
        $test = $abos->count() - $zahlend->count();

        $umsatz = $zahlend->sum(fn (Abo $a) => $a->monatspreis());
        $ziel = (float) Einstellung::wert('umsatz_ziel', 11000);
        $prozent = $ziel > 0 ? min(100, round($umsatz / $ziel * 100)) : 0;

        $offen = Rechnung::query()->where('status', 'offen')->where('art', 'rechnung');
        $ueberfaellig = (clone $offen)->whereDate('faellig_am', '<', $heute);

        return [
            Stat::make('Monatsumsatz netto (Abos)', Rechnung::euro($umsatz))
                ->description($ziel > 0 ? $prozent.' % vom Ziel '.Rechnung::euro($ziel) : 'Kein Ziel hinterlegt')
                ->color($prozent >= 100 ? 'success' : 'primary'),
            Stat::make('Zahlende Betriebe', (string) $zahlend->count())
                ->description($test > 0 ? $test.' in der Testphase' : 'keiner in der Testphase'),
            Stat::make('Offene Rechnungen', Rechnung::euro((clone $offen)->sum('brutto')))
                ->description($offen->count().' offen · '.$ueberfaellig->count().' überfällig')
                ->color($ueberfaellig->count() > 0 ? 'danger' : 'gray'),
        ];
    }
}
