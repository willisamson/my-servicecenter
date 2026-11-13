<?php

namespace App\Filament\Betrieb\RelationManagers;

use App\Models\AuftragNotiz;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Notizen und Fotos am Auftrag – vom Handy aus, direkt beim Kunden.
 */
class NotizenRelationManager extends RelationManager
{
    protected static string $relationship = 'notizen';

    protected static ?string $title = 'Notizen & Fotos';

    protected static ?string $modelLabel = 'Notiz';

    protected static ?string $pluralModelLabel = 'Notizen';

    /** Auch auf der Ansichtsseite "Meine Aufträge" bearbeitbar. */
    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Textarea::make('text')
                ->label('Notiz')
                ->placeholder('z. B. Brenner gereinigt, Druck 1,5 bar, Kunde wünscht Angebot für neue Pumpe')
                ->rows(4)
                ->maxLength(5000)
                ->requiredWithout('fotos')
                ->columnSpanFull(),
            FileUpload::make('fotos')
                ->label('Fotos')
                ->helperText('Bis zu 10 Fotos, werden automatisch verkleinert. Nur für Ihren Betrieb sichtbar.')
                ->multiple()
                ->image()
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                ->disk('betriebe_privat')
                ->directory(fn (): string => tenant('id').'/fotos/'.now()->format('Y-m'))
                ->visibility('private')
                ->maxFiles(10)
                ->maxSize(10240)
                ->imageResizeMode('contain')
                ->imageResizeTargetWidth('1920')
                ->imageResizeTargetHeight('1920')
                ->imageResizeUpscale(false)
                ->panelLayout('grid')
                ->openable()
                ->downloadable()
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        $eigene = fn (AuftragNotiz $record): bool => $record->user_id === auth()->id() || (bool) auth()->user()?->istBueroOderInhaber();

        return $table
            ->recordTitleAttribute('text')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Wann')
                    ->dateTime('d.m.Y H:i', 'Europe/Berlin')
                    ->description(fn (AuftragNotiz $record): ?string => $record->user?->name),
                TextColumn::make('text')
                    ->label('Notiz')
                    ->wrap()
                    ->limit(200)
                    ->placeholder('–'),
                ImageColumn::make('fotos')
                    ->label('Fotos')
                    ->disk('betriebe_privat')
                    ->visibility('private')
                    ->stacked()
                    ->limit(4)
                    ->limitedRemainingText(),
            ])
            ->headerActions([
                CreateAction::make()->label('Notiz / Fotos hinzufügen'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->visible($eigene),
                DeleteAction::make()->visible($eigene),
            ])
            ->emptyStateHeading('Noch keine Notizen')
            ->emptyStateDescription('Halten Sie fest, was gemacht wurde – mit Fotos vorher/nachher.');
    }
}
