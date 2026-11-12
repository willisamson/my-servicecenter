<?php

namespace App\Filament\Resources\Branchen;

use App\Filament\Resources\Branchen\Pages\CreateBranche;
use App\Filament\Resources\Branchen\Pages\EditBranche;
use App\Filament\Resources\Branchen\Pages\ListBranchen;
use App\Models\Branche;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Branchen (Kategorien) der Plattform, z. B. Heizung, Friseur, Gärtnerei, Blumenladen.
 */
class BrancheResource extends Resource
{
    protected static ?string $model = Branche::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?string $modelLabel = 'Branche';

    protected static ?string $pluralModelLabel = 'Branchen';

    protected static ?string $slug = 'branchen';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Name')
                ->placeholder('z. B. Friseur')
                ->required()
                ->maxLength(255),
            TextInput::make('slug')
                ->label('Kürzel')
                ->helperText('Nur Kleinbuchstaben, Zahlen und Bindestriche, z. B. "friseur". Kann später nicht geändert werden.')
                ->required()
                ->maxLength(40)
                ->regex('/^[a-z0-9]+(-[a-z0-9]+)*$/')
                ->unique(ignoreRecord: true)
                ->disabledOn('edit'),
            KeyValue::make('anliegen')
                ->label('Anliegen im Anfrageformular')
                ->helperText('Diese Auswahl sehen Kunden auf der Website eines Betriebs dieser Branche. Kürzel: kurz, ohne Leerzeichen (z. B. "schnitt").')
                ->keyLabel('Kürzel')
                ->valueLabel('Bezeichnung für Kunden')
                ->addActionLabel('Anliegen hinzufügen')
                ->default(['sonstiges' => 'Sonstiges'])
                ->columnSpanFull(),
            Toggle::make('aktiv')
                ->label('Aktiv (beim Anlegen von Betrieben auswählbar)')
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
                    ->label('Branche')
                    ->searchable(),
                TextColumn::make('slug')
                    ->label('Kürzel'),
                TextColumn::make('betriebe')
                    ->label('Betriebe')
                    ->state(fn (Branche $record): int => $record->anzahlBetriebe()),
                IconColumn::make('aktiv')
                    ->label('Aktiv')
                    ->boolean(),
                TextColumn::make('sortierung')
                    ->label('Reihenfolge')
                    ->sortable(),
            ])
            ->defaultSort('sortierung')
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBranchen::route('/'),
            'create' => CreateBranche::route('/create'),
            'edit' => EditBranche::route('/{record}/edit'),
        ];
    }
}
