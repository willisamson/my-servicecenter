<?php

namespace App\Filament\Resources\Tarife;

use App\Filament\Resources\Tarife\Pages\CreateTarif;
use App\Filament\Resources\Tarife\Pages\EditTarif;
use App\Filament\Resources\Tarife\Pages\ListTarife;
use App\Models\Rechnung;
use App\Models\Tarif;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Pakete, die Betriebe buchen können (Preis netto pro Monat).
 */
class TarifResource extends Resource
{
    protected static ?string $model = Tarif::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Abrechnung';

    protected static ?string $modelLabel = 'Tarif';

    protected static ?string $pluralModelLabel = 'Tarife';

    protected static ?string $slug = 'tarife';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Name')
                ->placeholder('z. B. Start, Profi, Team')
                ->required()
                ->maxLength(255),
            TextInput::make('preis_netto')
                ->label('Preis netto pro Monat')
                ->numeric()
                ->minValue(0)
                ->step('0.01')
                ->suffix('€')
                ->required(),
            Textarea::make('beschreibung')
                ->label('Was ist enthalten?')
                ->rows(3)
                ->columnSpanFull(),
            Toggle::make('aktiv')
                ->label('Aktiv (für neue Abos auswählbar)')
                ->default(true),
            TextInput::make('sortierung')
                ->label('Reihenfolge')
                ->numeric()
                ->default(100),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Tarif')
                    ->searchable(),
                TextColumn::make('preis_netto')
                    ->label('Netto / Monat')
                    ->formatStateUsing(fn ($state): string => Rechnung::euro($state)),
                TextColumn::make('abos_count')
                    ->label('Abos')
                    ->counts('abos'),
                IconColumn::make('aktiv')
                    ->label('Aktiv')
                    ->boolean(),
            ])
            ->defaultSort('sortierung')
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTarife::route('/'),
            'create' => CreateTarif::route('/create'),
            'edit' => EditTarif::route('/{record}/edit'),
        ];
    }
}
