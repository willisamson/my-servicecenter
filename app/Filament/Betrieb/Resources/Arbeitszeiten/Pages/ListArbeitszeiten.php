<?php

namespace App\Filament\Betrieb\Resources\Arbeitszeiten\Pages;

use App\Filament\Betrieb\Resources\Arbeitszeiten\ArbeitszeitResource;
use App\Models\Arbeitszeit;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;

class ListArbeitszeiten extends ListRecords
{
    protected static string $resource = ArbeitszeitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Export (CSV)')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->modalHeading('Arbeitszeiten exportieren')
                ->modalDescription('CSV-Datei für Excel oder die Lohnabrechnung.')
                ->modalSubmitActionLabel('Herunterladen')
                ->schema([
                    Select::make('monat')
                        ->label('Monat')
                        ->options(function (): array {
                            $monate = [];
                            $m = now('Europe/Berlin')->startOfMonth();
                            for ($i = 0; $i < 13; $i++) {
                                $monate[$m->format('Y-m')] = $m->format('m/Y');
                                $m = $m->copy()->subMonthNoOverflow();
                            }

                            return $monate;
                        })
                        ->default(now('Europe/Berlin')->format('Y-m'))
                        ->required(),
                    Select::make('user_id')
                        ->label('Mitarbeiter')
                        ->options(fn (): array => User::auswahl())
                        ->placeholder('Alle'),
                ])
                ->action(function (array $data) {
                    $von = Carbon::createFromFormat('Y-m-d', $data['monat'].'-01', 'Europe/Berlin')->startOfDay();
                    $bis = $von->copy()->addMonthNoOverflow();

                    $zeiten = Arbeitszeit::query()
                        ->with('user', 'anfrage')
                        ->where('beginn', '>=', $von->copy()->utc())
                        ->where('beginn', '<', $bis->copy()->utc())
                        ->when($data['user_id'] ?? null, fn ($q, $id) => $q->where('user_id', $id))
                        ->orderBy('user_id')
                        ->orderBy('beginn')
                        ->get();

                    $dateiname = 'Arbeitszeiten-'.$data['monat'].'.csv';

                    return response()->streamDownload(function () use ($zeiten) {
                        $out = fopen('php://output', 'w');
                        fwrite($out, "\xEF\xBB\xBF"); // damit Excel die Umlaute richtig zeigt
                        fputcsv($out, ['Mitarbeiter', 'Datum', 'Beginn', 'Ende', 'Minuten', 'Stunden', 'Auftrag', 'Notiz'], ';');

                        foreach ($zeiten as $z) {
                            fputcsv($out, [
                                $z->user?->name,
                                $z->beginn->copy()->timezone('Europe/Berlin')->format('d.m.Y'),
                                $z->beginn->copy()->timezone('Europe/Berlin')->format('H:i'),
                                $z->ende?->copy()->timezone('Europe/Berlin')->format('H:i') ?? 'läuft',
                                $z->minuten,
                                $z->minuten !== null ? number_format($z->minuten / 60, 2, ',', '') : '',
                                $z->anfrage?->kurztitel(),
                                $z->notiz,
                            ], ';');
                        }

                        fclose($out);
                    }, $dateiname, ['Content-Type' => 'text/csv; charset=UTF-8']);
                }),
            CreateAction::make()->label('Zeit nachtragen'),
        ];
    }
}
