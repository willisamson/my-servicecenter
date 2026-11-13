<?php

namespace App\Filament\Betrieb\Resources\Anfragen;

use App\Filament\Betrieb\Resources\Anfragen\Pages\CreateAnfrage;
use App\Filament\Betrieb\Resources\Anfragen\Pages\EditAnfrage;
use App\Filament\Betrieb\Resources\Anfragen\Pages\ListAnfragen;
use App\Filament\Betrieb\RelationManagers\MaterialRelationManager;
use App\Filament\Betrieb\RelationManagers\NotizenRelationManager;
use App\Models\Anfrage;
use App\Models\User;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Anfragen von Endkunden im Betriebs-Dashboard (Inhaber und Büro).
 */
class AnfrageResource extends Resource
{
    protected static ?string $model = Anfrage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static ?string $modelLabel = 'Anfrage';

    protected static ?string $pluralModelLabel = 'Anfragen';

    protected static ?string $navigationLabel = 'Anfragen';

    protected static ?string $slug = 'anfragen';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->istBueroOderInhaber();
    }

    /** Zahl der neuen Anfragen in der Navigation. */
    public static function getNavigationBadge(): ?string
    {
        $anzahl = Anfrage::where('status', 'neu')->count();

        return $anzahl > 0 ? (string) $anzahl : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Status')
                ->columns(2)
                ->schema([
                    Select::make('status')
                        ->label('Status')
                        ->options(Anfrage::STATUS)
                        ->default('neu')
                        ->required(),
                    Select::make('mitarbeiter_id')
                        ->label('Zuständig')
                        ->helperText('Der Mitarbeiter sieht den Auftrag dann unter „Meine Aufträge“ und im Kalender.')
                        ->options(fn (): array => User::auswahl())
                        ->searchable()
                        ->placeholder('Noch niemand'),
                    DateTimePicker::make('termin_am')
                        ->label('Termin')
                        ->helperText('Mit Termin steht beim Kunden automatisch „Termin bestätigt“.')
                        ->seconds(false)
                        ->minutesStep(15)
                        ->timezone('Europe/Berlin'),
                    Select::make('termin_dauer')
                        ->label('Dauer')
                        ->options([30 => '30 Minuten', 60 => '1 Stunde', 90 => '1,5 Stunden', 120 => '2 Stunden', 180 => '3 Stunden', 240 => '4 Stunden', 480 => 'ganzer Tag'])
                        ->default(60)
                        ->selectablePlaceholder(false),
                    TextEntry::make('status_link')
                        ->label('Status-Link für den Kunden')
                        ->helperText('Diesen Link sieht der Kunde nach dem Absenden. Bei telefonischen Anfragen können Sie ihn per SMS oder E-Mail weitergeben.')
                        ->copyable()
                        ->visibleOn('edit')
                        ->columnSpanFull(),
                    Textarea::make('notiz_intern')
                        ->label('Interne Notiz')
                        ->helperText('Nur für den Betrieb sichtbar.')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
            Section::make('Anliegen')
                ->schema([
                    Select::make('anliegen')
                        ->label('Anliegen')
                        ->options(fn (): array => Anfrage::anliegenFuer(tenant('branche')))
                        ->required(),
                    Textarea::make('beschreibung')
                        ->label('Beschreibung')
                        ->rows(4)
                        ->required(),
                    TextInput::make('wunschtermin')
                        ->label('Wunschtermin des Kunden')
                        ->maxLength(255),
                ]),
            Section::make('Kunde')
                ->columns(2)
                ->schema([
                    TextInput::make('name')->label('Name')->required()->maxLength(255),
                    TextInput::make('telefon')->label('Telefon')->tel()->maxLength(50),
                    TextInput::make('email')->label('E-Mail')->email()->maxLength(255),
                    TextInput::make('plz')->label('PLZ')->required()->maxLength(10),
                    TextInput::make('adresse')->label('Straße und Hausnummer')->maxLength(255)->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Eingang')
                    ->dateTime('d.m.Y H:i', 'Europe/Berlin')
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Kunde')
                    ->searchable(),
                TextColumn::make('anliegen')
                    ->label('Anliegen')
                    ->formatStateUsing(fn (Anfrage $record): string => $record->anliegenLabel()),
                TextColumn::make('plz')
                    ->label('PLZ')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => Anfrage::STATUS[$state] ?? (string) $state)
                    ->color(fn (?string $state): string => Anfrage::STATUS_FARBEN[$state] ?? 'gray'),
                TextColumn::make('termin_am')
                    ->label('Termin')
                    ->dateTime('d.m.Y H:i', 'Europe/Berlin')
                    ->placeholder('–')
                    ->sortable(),
                TextColumn::make('mitarbeiter.name')
                    ->label('Zuständig')
                    ->placeholder('–'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(Anfrage::STATUS),
                SelectFilter::make('mitarbeiter_id')
                    ->label('Zuständig')
                    ->options(fn (): array => User::auswahl()),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            NotizenRelationManager::class,
            MaterialRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAnfragen::route('/'),
            'create' => CreateAnfrage::route('/create'),
            'edit' => EditAnfrage::route('/{record}/edit'),
        ];
    }
}
