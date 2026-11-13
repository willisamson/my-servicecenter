<?php

namespace App\Filament\Resources\Abos;

use App\Filament\Resources\Abos\Pages\CreateAbo;
use App\Filament\Resources\Abos\Pages\EditAbo;
use App\Filament\Resources\Abos\Pages\ListAbos;
use App\Models\Abo;
use App\Models\Rechnung;
use App\Models\Tarif;
use App\Models\Tenant;
use App\Services\Rechnungsstellung;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Throwable;
use UnitEnum;

/**
 * Abos der Betriebe. Abgerechnet wird monatlich im Voraus.
 */
class AboResource extends Resource
{
    protected static ?string $model = Abo::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPath;

    protected static string|UnitEnum|null $navigationGroup = 'Abrechnung';

    protected static ?string $modelLabel = 'Abo';

    protected static ?string $pluralModelLabel = 'Abos';

    protected static ?string $slug = 'abos';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Abo')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    Select::make('tenant_id')
                        ->label('Betrieb')
                        ->options(fn (): array => Tenant::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->validationMessages(['unique' => 'Dieser Betrieb hat schon ein Abo.'])
                        ->disabledOn('edit'),
                    Select::make('tarif_id')
                        ->label('Tarif')
                        ->options(fn (): array => Tarif::query()->orderBy('sortierung')->get()
                            ->mapWithKeys(fn (Tarif $t) => [$t->id => $t->name.' – '.Rechnung::euro($t->preis_netto).' netto/Monat'.($t->aktiv ? '' : ' (inaktiv)')])
                            ->all())
                        ->required(),
                    TextInput::make('preis_netto')
                        ->label('Eigener Preis netto / Monat')
                        ->helperText('Leer lassen = Preis des Tarifs. Für Sonderkonditionen, z. B. Pilotbetriebe.')
                        ->numeric()
                        ->minValue(0)
                        ->step('0.01')
                        ->suffix('€'),
                    DatePicker::make('start_am')
                        ->label('Start')
                        ->native(false)
                        ->displayFormat('d.m.Y')
                        ->default(today())
                        ->required(),
                    DatePicker::make('kostenlos_bis')
                        ->label('Kostenlos bis')
                        ->helperText('Pilot- oder Testphase. Bis dahin wird nichts berechnet.')
                        ->native(false)
                        ->displayFormat('d.m.Y'),
                    DatePicker::make('naechste_rechnung_am')
                        ->label('Nächste Rechnung am')
                        ->helperText('Leer lassen = automatisch (Start bzw. Tag nach der Testphase).')
                        ->native(false)
                        ->displayFormat('d.m.Y'),
                    DatePicker::make('gekuendigt_zum')
                        ->label('Gekündigt zum')
                        ->helperText('Letzter Tag, der noch berechnet wird. Danach keine Rechnungen mehr.')
                        ->native(false)
                        ->displayFormat('d.m.Y'),
                ]),
            Section::make('Rechnungsempfänger')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('rechnung_email')
                        ->label('Rechnungen an E-Mail')
                        ->helperText('Leer = E-Mail des Betriebs.')
                        ->email()
                        ->maxLength(255),
                    Textarea::make('rechnung_adresse')
                        ->label('Abweichende Rechnungsanschrift')
                        ->helperText('Eine Zeile pro Adresszeile. Leer = Name und Anschrift aus dem Betriebsprofil.')
                        ->rows(4),
                    Textarea::make('notiz')
                        ->label('Interne Notiz')
                        ->rows(2)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('betrieb', 'tarif'))
            ->columns([
                TextColumn::make('betrieb.name')
                    ->label('Betrieb')
                    ->searchable(),
                TextColumn::make('tarif.name')
                    ->label('Tarif'),
                TextColumn::make('preis')
                    ->label('Netto / Monat')
                    ->state(fn (Abo $record): string => Rechnung::euro($record->monatspreis())),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->state(fn (Abo $record): string => $record->statusLabel())
                    ->color(fn (string $state): string => match ($state) {
                        'aktiv' => 'success',
                        'Testphase' => 'info',
                        'gekündigt' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('naechste_rechnung_am')
                    ->label('Nächste Rechnung')
                    ->date('d.m.Y')
                    ->placeholder('–')
                    ->sortable(),
            ])
            ->defaultSort('naechste_rechnung_am')
            ->recordActions([
                Action::make('abrechnen')
                    ->label('Jetzt abrechnen')
                    ->icon(Heroicon::OutlinedBanknotes)
                    ->color('gray')
                    ->visible(fn (Abo $record): bool => $record->naechste_rechnung_am !== null && $record->laeuftAm($record->naechste_rechnung_am))
                    ->modalHeading('Rechnung jetzt erstellen?')
                    ->modalDescription(fn (Abo $record): string => 'Rechnung für den Zeitraum ab '.$record->naechste_rechnung_am?->format('d.m.Y').' über '.Rechnung::euro($record->monatspreis()).' netto. Danach springt „Nächste Rechnung“ einen Monat weiter.')
                    ->schema([
                        Toggle::make('senden')->label('Per E-Mail an den Betrieb senden')->default(true),
                    ])
                    ->action(function (Abo $record, array $data) {
                        try {
                            $dienst = app(Rechnungsstellung::class);
                            $rechnung = $dienst->fuerAbo($record);
                            $gesendet = ! empty($data['senden']) && $dienst->versenden($rechnung);
                        } catch (Throwable $e) {
                            Notification::make()->title('Rechnung nicht erstellt')->body($e->getMessage())->danger()->persistent()->send();

                            return;
                        }

                        Notification::make()
                            ->title('Rechnung '.$rechnung->nummer.' erstellt')
                            ->body($gesendet ? 'Per E-Mail an '.$rechnung->empfaenger_email.' gesendet.' : 'Nicht per E-Mail gesendet.')
                            ->success()
                            ->send();
                    }),
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAbos::route('/'),
            'create' => CreateAbo::route('/create'),
            'edit' => EditAbo::route('/{record}/edit'),
        ];
    }
}
