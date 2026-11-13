<?php

namespace App\Filament\Betrieb\Pages;

use App\Models\Abo;
use App\Models\Einstellung;
use App\Models\Rechnung;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

/**
 * Für den Inhaber: gebuchter Tarif und alle Rechnungen von my-servicecenter zum Herunterladen.
 */
class AboRechnungen extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static ?string $title = 'Abo & Rechnungen';

    protected static ?string $slug = 'abo';

    protected static ?int $navigationSort = 90;

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->istInhaber();
    }

    public function content(Schema $schema): Schema
    {
        $abo = Abo::query()->with('tarif')->where('tenant_id', tenant('id'))->first();
        $kontakt = Einstellung::wert('email');

        return $schema->components([
            Section::make('Ihr Tarif')
                ->columns(['default' => 1, 'md' => 3])
                ->schema([
                    TextEntry::make('tarif')
                        ->label('Tarif')
                        ->state($abo?->tarif?->name ?? 'Noch kein Abo hinterlegt'),
                    TextEntry::make('preis')
                        ->label('Preis netto pro Monat')
                        ->state($abo ? Rechnung::euro($abo->monatspreis()) : '–'),
                    TextEntry::make('naechste')
                        ->label('Nächste Rechnung')
                        ->state(match (true) {
                            $abo === null => '–',
                            $abo->gekuendigt_zum !== null && ($abo->naechste_rechnung_am === null || $abo->naechste_rechnung_am->gt($abo->gekuendigt_zum)) => 'keine (gekündigt zum '.$abo->gekuendigt_zum->format('d.m.Y').')',
                            default => $abo->naechste_rechnung_am?->format('d.m.Y') ?? '–',
                        }),
                    TextEntry::make('test')
                        ->label('Kostenlos bis')
                        ->state($abo?->kostenlos_bis?->format('d.m.Y'))
                        ->visible($abo?->kostenlos_bis !== null && $abo->kostenlos_bis->gte(today())),
                    TextEntry::make('kontakt')
                        ->label('Fragen zu Abo oder Rechnung')
                        ->state($kontakt)
                        ->visible(filled($kontakt))
                        ->columnSpanFull(),
                ]),
            EmbeddedTable::make(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Rechnungen')
            ->query(Rechnung::query()->where('tenant_id', tenant('id')))
            ->columns([
                TextColumn::make('nummer')
                    ->label('Nummer')
                    ->description(fn (Rechnung $record): ?string => $record->istStorno() ? 'Storno' : null),
                TextColumn::make('datum')->label('Datum')->date('d.m.Y'),
                TextColumn::make('zeitraum')
                    ->label('Zeitraum')
                    ->state(fn (Rechnung $record): string => $record->leistung_von && $record->leistung_bis
                        ? $record->leistung_von->format('d.m.Y').' – '.$record->leistung_bis->format('d.m.Y')
                        : '–'),
                TextColumn::make('brutto')
                    ->label('Betrag')
                    ->formatStateUsing(fn ($state): string => Rechnung::euro($state))
                    ->alignEnd(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->state(fn (Rechnung $record): string => $record->istUeberfaellig() ? 'überfällig' : (Rechnung::STATUS[$record->status] ?? $record->status))
                    ->color(fn (Rechnung $record): string => $record->istUeberfaellig() ? 'danger' : (Rechnung::STATUS_FARBEN[$record->status] ?? 'gray')),
            ])
            ->defaultSort('id', 'desc')
            ->emptyStateHeading('Noch keine Rechnungen')
            ->recordActions([
                Action::make('pdf')
                    ->label('PDF')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->visible(fn (Rechnung $record): bool => filled($record->pdf_pfad))
                    ->action(fn (Rechnung $record) => Storage::disk('rechnungen')->download($record->pdf_pfad, $record->dateiname())),
            ]);
    }
}
