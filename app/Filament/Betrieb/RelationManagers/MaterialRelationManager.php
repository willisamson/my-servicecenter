<?php

namespace App\Filament\Betrieb\RelationManagers;

use App\Models\AuftragMaterial;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Verbrauchtes Material am Auftrag.
 */
class MaterialRelationManager extends RelationManager
{
    protected static string $relationship = 'material';

    protected static ?string $title = 'Material';

    protected static ?string $modelLabel = 'Material';

    protected static ?string $pluralModelLabel = 'Material';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(['default' => 2, 'md' => 4])
            ->components([
                TextInput::make('bezeichnung')
                    ->label('Material')
                    ->placeholder('z. B. Dichtung 3/4"')
                    ->required()
                    ->maxLength(255)
                    ->columnSpan(['default' => 2, 'md' => 2]),
                TextInput::make('menge')
                    ->label('Menge')
                    ->numeric()
                    ->minValue(0)
                    ->step('0.01')
                    ->default(1)
                    ->required(),
                Select::make('einheit')
                    ->label('Einheit')
                    ->options(AuftragMaterial::EINHEITEN)
                    ->default('Stk')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        $eigene = fn (AuftragMaterial $record): bool => $record->user_id === auth()->id() || (bool) auth()->user()?->istBueroOderInhaber();

        return $table
            ->recordTitleAttribute('bezeichnung')
            ->columns([
                TextColumn::make('bezeichnung')->label('Material')->wrap(),
                TextColumn::make('menge')
                    ->label('Menge')
                    ->formatStateUsing(fn ($state, AuftragMaterial $record): string => rtrim(rtrim(number_format((float) $state, 2, ',', '.'), '0'), ',').' '.$record->einheit),
                TextColumn::make('user.name')->label('Erfasst von')->placeholder('–'),
            ])
            ->headerActions([
                CreateAction::make()->label('Material erfassen'),
            ])
            ->recordActions([
                EditAction::make()->visible($eigene),
                DeleteAction::make()->visible($eigene),
            ])
            ->emptyStateHeading('Noch kein Material erfasst');
    }
}
