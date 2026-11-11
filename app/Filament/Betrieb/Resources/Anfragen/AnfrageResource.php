<?php

namespace App\Filament\Betrieb\Resources\Anfragen;

use App\Filament\Betrieb\Resources\Anfragen\Pages\CreateAnfrage;
use App\Filament\Betrieb\Resources\Anfragen\Pages\EditAnfrage;
use App\Filament\Betrieb\Resources\Anfragen\Pages\ListAnfragen;
use App\Models\Anfrage;
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
                    DateTimePicker::make('termin_am')
                        ->label('Termin')
                        ->seconds(false)
                        ->timezone('Europe/Berlin'),
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
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(Anfrage::STATUS),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
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
