<?php

namespace App\Filament\Betrieb\Resources\Auftraege;

use App\Filament\Betrieb\RelationManagers\MaterialRelationManager;
use App\Filament\Betrieb\RelationManagers\NotizenRelationManager;
use App\Filament\Betrieb\Resources\Auftraege\Pages\ListAuftraege;
use App\Filament\Betrieb\Resources\Auftraege\Pages\ViewAuftrag;
use App\Models\Anfrage;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * "Meine Aufträge": alles, was dem angemeldeten Mitarbeiter zugewiesen ist.
 * Fürs Handy gedacht – Kunde anrufen, Route starten, Status setzen, Notizen/Fotos/Material.
 */
class AuftragResource extends Resource
{
    protected static ?string $model = Anfrage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static ?string $modelLabel = 'Auftrag';

    protected static ?string $pluralModelLabel = 'Meine Aufträge';

    protected static ?string $navigationLabel = 'Meine Aufträge';

    protected static ?string $slug = 'auftraege';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 0;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('mitarbeiter_id', auth()->id());
    }

    public static function getNavigationBadge(): ?string
    {
        $anzahl = static::getEloquentQuery()->whereIn('status', Anfrage::OFFEN)->count();

        return $anzahl > 0 ? (string) $anzahl : null;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Auftrag')
                ->columns(['default' => 1, 'md' => 2])
                ->schema([
                    TextEntry::make('anliegen')
                        ->label('Anliegen')
                        ->formatStateUsing(fn (Anfrage $record): string => $record->anliegenLabel()),
                    TextEntry::make('status')
                        ->label('Status')
                        ->badge()
                        ->formatStateUsing(fn (?string $state): string => Anfrage::STATUS[$state] ?? (string) $state)
                        ->color(fn (?string $state): string => Anfrage::STATUS_FARBEN[$state] ?? 'gray'),
                    TextEntry::make('termin_am')
                        ->label('Termin')
                        ->dateTime('d.m.Y H:i', 'Europe/Berlin')
                        ->placeholder('noch kein Termin'),
                    TextEntry::make('wunschtermin')
                        ->label('Wunsch des Kunden')
                        ->placeholder('–'),
                    TextEntry::make('beschreibung')
                        ->label('Beschreibung')
                        ->columnSpanFull(),
                    TextEntry::make('notiz_intern')
                        ->label('Hinweis vom Büro')
                        ->placeholder('–')
                        ->columnSpanFull(),
                ]),
            Section::make('Kunde')
                ->columns(['default' => 1, 'md' => 2])
                ->schema([
                    TextEntry::make('name')->label('Name'),
                    TextEntry::make('telefon')
                        ->label('Telefon')
                        ->placeholder('–')
                        ->url(fn (Anfrage $record): ?string => $record->telefon ? 'tel:'.preg_replace('/[^0-9+]/', '', $record->telefon) : null)
                        ->icon(Heroicon::OutlinedPhone),
                    TextEntry::make('adresse')
                        ->label('Adresse')
                        ->state(fn (Anfrage $record): string => trim(($record->adresse ?? '').', '.$record->plz, ', '))
                        ->url(fn (Anfrage $record): ?string => $record->routenLink())
                        ->openUrlInNewTab()
                        ->icon(Heroicon::OutlinedMapPin),
                    TextEntry::make('email')
                        ->label('E-Mail')
                        ->placeholder('–')
                        ->url(fn (Anfrage $record): ?string => $record->email ? 'mailto:'.$record->email : null),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('termin_am')
                    ->label('Termin')
                    ->dateTime('d.m. H:i', 'Europe/Berlin')
                    ->placeholder('offen')
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Kunde')
                    ->description(fn (Anfrage $record): string => $record->anliegenLabel())
                    ->searchable(),
                TextColumn::make('adresse')
                    ->label('Ort')
                    ->state(fn (Anfrage $record): string => trim(($record->adresse ?? '').' '.$record->plz))
                    ->visibleFrom('md'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => Anfrage::STATUS[$state] ?? (string) $state)
                    ->color(fn (?string $state): string => Anfrage::STATUS_FARBEN[$state] ?? 'gray'),
            ])
            ->defaultSort(fn (Builder $query) => $query->orderByRaw('termin_am IS NULL')->orderBy('termin_am'))
            ->filters([
                TernaryFilter::make('offen')
                    ->label('Anzeigen')
                    ->placeholder('Alle')
                    ->trueLabel('Nur offene')
                    ->falseLabel('Nur erledigte')
                    ->default(true)
                    ->queries(
                        true: fn (Builder $query) => $query->whereIn('status', Anfrage::OFFEN),
                        false: fn (Builder $query) => $query->whereNotIn('status', Anfrage::OFFEN),
                        blank: fn (Builder $query) => $query,
                    ),
            ])
            ->recordActions([
                ViewAction::make()->label('Öffnen'),
            ])
            ->emptyStateHeading('Keine Aufträge')
            ->emptyStateDescription('Sobald das Büro Ihnen einen Auftrag zuweist, erscheint er hier.');
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
            'index' => ListAuftraege::route('/'),
            'view' => ViewAuftrag::route('/{record}'),
        ];
    }
}
