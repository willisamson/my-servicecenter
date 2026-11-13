<?php

namespace App\Filament\Resources\Rechnungen;

use App\Filament\Resources\Rechnungen\Pages\ListRechnungen;
use App\Models\Rechnung;
use App\Services\Rechnungsstellung;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Throwable;
use UnitEnum;

/**
 * Alle Rechnungen an Betriebe. Rechnungen werden nie bearbeitet oder gelöscht –
 * Korrekturen laufen über eine Stornorechnung.
 */
class RechnungResource extends Resource
{
    protected static ?string $model = Rechnung::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Abrechnung';

    protected static ?string $modelLabel = 'Rechnung';

    protected static ?string $pluralModelLabel = 'Rechnungen';

    protected static ?string $slug = 'rechnungen';

    protected static ?string $recordTitleAttribute = 'nummer';

    protected static ?int $navigationSort = 20;

    public static function getNavigationBadge(): ?string
    {
        $anzahl = Rechnung::query()->where('status', 'offen')->where('art', 'rechnung')->whereDate('faellig_am', '<', today())->count();

        return $anzahl > 0 ? (string) $anzahl : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'danger';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Überfällige Rechnungen';
    }

    public static function canCreate(): bool
    {
        return false; // neue Rechnungen über "Einzelrechnung" oder die Abos
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nummer')
                    ->label('Nummer')
                    ->searchable()
                    ->description(fn (Rechnung $record): ?string => $record->istStorno() ? 'Storno' : null),
                TextColumn::make('datum')
                    ->label('Datum')
                    ->date('d.m.Y')
                    ->sortable(),
                TextColumn::make('empfaenger_name')
                    ->label('Betrieb')
                    ->state(fn (Rechnung $record): string => $record->empfaenger['zeilen'][0] ?? (string) $record->tenant_id)
                    ->searchable(query: fn (Builder $query, string $search) => $query->where('empfaenger', 'like', '%'.$search.'%')),
                TextColumn::make('brutto')
                    ->label('Brutto')
                    ->formatStateUsing(fn ($state): string => Rechnung::euro($state))
                    ->alignEnd()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->state(fn (Rechnung $record): string => $record->istUeberfaellig() ? 'überfällig' : (Rechnung::STATUS[$record->status] ?? $record->status))
                    ->color(fn (Rechnung $record): string => $record->istUeberfaellig() ? 'danger' : (Rechnung::STATUS_FARBEN[$record->status] ?? 'gray')),
                TextColumn::make('faellig_am')
                    ->label('Fällig')
                    ->date('d.m.Y')
                    ->placeholder('–'),
                TextColumn::make('bezahlt_am')
                    ->label('Bezahlt am')
                    ->date('d.m.Y')
                    ->placeholder('–')
                    ->toggleable(),
                TextColumn::make('versendet_am')
                    ->label('Gesendet')
                    ->dateTime('d.m.Y H:i', 'Europe/Berlin')
                    ->placeholder('nicht gesendet')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(Rechnung::STATUS),
                Filter::make('ueberfaellig')
                    ->label('Nur überfällige')
                    ->query(fn (Builder $query) => $query->where('status', 'offen')->where('art', 'rechnung')->whereDate('faellig_am', '<', today())),
            ])
            ->recordActions([
                Action::make('pdf')
                    ->label('PDF')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->color('gray')
                    ->visible(fn (Rechnung $record): bool => filled($record->pdf_pfad))
                    ->action(fn (Rechnung $record) => Storage::disk('rechnungen')->download($record->pdf_pfad, $record->dateiname())),
                Action::make('bezahlt')
                    ->label('Bezahlt')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->visible(fn (Rechnung $record): bool => $record->status === 'offen' && ! $record->istStorno())
                    ->modalHeading('Zahlungseingang erfassen')
                    ->schema([
                        DatePicker::make('bezahlt_am')->label('Geld eingegangen am')->native(false)->displayFormat('d.m.Y')->default(today())->required(),
                    ])
                    ->action(function (Rechnung $record, array $data) {
                        $record->update(['status' => 'bezahlt', 'bezahlt_am' => $data['bezahlt_am']]);
                        Notification::make()->title('Rechnung '.$record->nummer.' als bezahlt markiert')->success()->send();
                    }),
                ActionGroup::make([
                    Action::make('offen')
                        ->label('Zahlung zurücknehmen')
                        ->icon(Heroicon::OutlinedArrowUturnLeft)
                        ->visible(fn (Rechnung $record): bool => $record->status === 'bezahlt' && ! $record->istStorno())
                        ->requiresConfirmation()
                        ->action(fn (Rechnung $record) => $record->update(['status' => 'offen', 'bezahlt_am' => null])),
                    Action::make('senden')
                        ->label('Per E-Mail senden')
                        ->icon(Heroicon::OutlinedEnvelope)
                        ->visible(fn (Rechnung $record): bool => filled($record->empfaenger_email))
                        ->requiresConfirmation()
                        ->modalDescription(fn (Rechnung $record): string => 'An '.$record->empfaenger_email)
                        ->action(function (Rechnung $record) {
                            try {
                                app(Rechnungsstellung::class)->versenden($record);
                                Notification::make()->title('Gesendet an '.$record->empfaenger_email)->success()->send();
                            } catch (Throwable $e) {
                                Notification::make()->title('Senden fehlgeschlagen')->body($e->getMessage())->danger()->persistent()->send();
                            }
                        }),
                    Action::make('stornieren')
                        ->label('Stornieren')
                        ->icon(Heroicon::OutlinedXCircle)
                        ->color('danger')
                        ->visible(fn (Rechnung $record): bool => ! $record->istStorno() && $record->status !== 'storniert')
                        ->modalHeading('Rechnung stornieren?')
                        ->modalDescription('Es wird eine Stornorechnung mit negativem Betrag erstellt. Die Rechnung selbst bleibt erhalten (Aufbewahrungspflicht). Das Abo wird nicht zurückgesetzt.')
                        ->schema([
                            Toggle::make('senden')->label('Stornorechnung per E-Mail senden')->default(true),
                        ])
                        ->action(function (Rechnung $record, array $data) {
                            try {
                                $dienst = app(Rechnungsstellung::class);
                                $storno = $dienst->stornieren($record);
                                if (! empty($data['senden'])) {
                                    $dienst->versenden($storno);
                                }
                            } catch (Throwable $e) {
                                Notification::make()->title('Storno fehlgeschlagen')->body($e->getMessage())->danger()->persistent()->send();

                                return;
                            }

                            Notification::make()->title('Stornorechnung '.$storno->nummer.' erstellt')->success()->send();
                        }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRechnungen::route('/'),
        ];
    }
}
