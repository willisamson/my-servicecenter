<?php

namespace App\Filament\Betrieb\Pages;

use App\Models\Anfrage;
use App\Models\Arbeitszeit;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

/**
 * Stempeluhr für jeden Benutzer: einstempeln (optional auf einen Auftrag), wechseln, ausstempeln.
 */
class Stempeluhr extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?string $title = 'Stempeluhr';

    protected static ?string $slug = 'stempeluhr';

    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        return Arbeitszeit::laufend((int) auth()->id()) ? 'läuft' : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'success';
    }

    protected function auftragsAuswahl(): array
    {
        return Anfrage::query()
            ->where('mitarbeiter_id', auth()->id())
            ->whereIn('status', Anfrage::OFFEN)
            ->orderByRaw('termin_am IS NULL')
            ->orderBy('termin_am')
            ->get()
            ->mapWithKeys(fn (Anfrage $a) => [$a->id => $a->kurztitel()])
            ->all();
    }

    protected function stempelFormular(): array
    {
        return [
            Select::make('anfrage_id')
                ->label('Auftrag (optional)')
                ->options(fn (): array => $this->auftragsAuswahl())
                ->placeholder('Ohne Auftrag – z. B. Werkstatt, Büro, Lager'),
            TextInput::make('notiz')
                ->label('Notiz (optional)')
                ->maxLength(255),
        ];
    }

    protected function getHeaderActions(): array
    {
        $laeuft = fn (): bool => Arbeitszeit::laufend((int) auth()->id()) !== null;

        return [
            Action::make('einstempeln')
                ->label('Einstempeln')
                ->icon(Heroicon::OutlinedPlay)
                ->color('success')
                ->visible(fn (): bool => ! $laeuft())
                ->modalHeading('Einstempeln')
                ->modalSubmitActionLabel('Jetzt einstempeln')
                ->schema($this->stempelFormular())
                ->action(function (array $data) {
                    Arbeitszeit::einstempeln((int) auth()->id(), $data['anfrage_id'] ?? null, $data['notiz'] ?? null);
                    Notification::make()->title('Eingestempelt um '.now('Europe/Berlin')->format('H:i'))->success()->send();
                }),
            Action::make('wechseln')
                ->label('Auftrag wechseln')
                ->icon(Heroicon::OutlinedArrowsRightLeft)
                ->color('gray')
                ->visible($laeuft)
                ->modalHeading('Auf anderen Auftrag wechseln')
                ->modalDescription('Die bisherige Zeit wird beendet, eine neue beginnt.')
                ->modalSubmitActionLabel('Wechseln')
                ->schema($this->stempelFormular())
                ->action(function (array $data) {
                    Arbeitszeit::einstempeln((int) auth()->id(), $data['anfrage_id'] ?? null, $data['notiz'] ?? null);
                    Notification::make()->title('Gewechselt um '.now('Europe/Berlin')->format('H:i'))->success()->send();
                }),
            Action::make('ausstempeln')
                ->label('Ausstempeln / Pause')
                ->icon(Heroicon::OutlinedStop)
                ->color('danger')
                ->visible($laeuft)
                ->requiresConfirmation()
                ->modalHeading('Ausstempeln?')
                ->modalDescription('Für eine Pause einfach ausstempeln und danach wieder einstempeln.')
                ->action(function () {
                    $zeit = Arbeitszeit::ausstempeln((int) auth()->id());
                    Notification::make()
                        ->title('Ausgestempelt – '.Arbeitszeit::format($zeit?->minuten))
                        ->success()
                        ->send();
                }),
        ];
    }

    public function content(Schema $schema): Schema
    {
        $userId = (int) auth()->id();
        $laufend = Arbeitszeit::laufend($userId);
        $jetzt = now('Europe/Berlin');

        $heute = Arbeitszeit::minutenZwischen($userId, $jetzt->copy()->startOfDay()->utc(), $jetzt->copy()->endOfDay()->utc());
        $woche = Arbeitszeit::minutenZwischen($userId, $jetzt->copy()->startOfWeek()->utc(), $jetzt->copy()->endOfWeek()->utc());

        return $schema->components([
            Section::make()
                ->columns(['default' => 1, 'md' => 3])
                ->schema([
                    TextEntry::make('stand')
                        ->label('Stand')
                        ->state($laufend
                            ? 'Eingestempelt seit '.$laufend->beginn->copy()->timezone('Europe/Berlin')->format('H:i').' Uhr'
                            : 'Nicht eingestempelt')
                        ->badge()
                        ->color($laufend ? 'success' : 'gray'),
                    TextEntry::make('heute')
                        ->label('Heute')
                        ->state(Arbeitszeit::format($heute)),
                    TextEntry::make('woche')
                        ->label('Diese Woche')
                        ->state(Arbeitszeit::format($woche)),
                    TextEntry::make('auftrag')
                        ->label('Aktueller Auftrag')
                        ->state($laufend?->anfrage?->kurztitel())
                        ->visible($laufend?->anfrage !== null)
                        ->columnSpanFull(),
                ]),
            EmbeddedTable::make(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Meine Zeiten')
            ->query(Arbeitszeit::query()->with('anfrage')->where('user_id', auth()->id()))
            ->defaultSort('beginn', 'desc')
            ->columns([
                TextColumn::make('beginn')
                    ->label('Tag')
                    ->dateTime('d.m.Y', 'Europe/Berlin'),
                TextColumn::make('von_bis')
                    ->label('Von – bis')
                    ->state(fn (Arbeitszeit $record): string => $record->beginn->copy()->timezone('Europe/Berlin')->format('H:i')
                        .' – '.($record->ende ? $record->ende->copy()->timezone('Europe/Berlin')->format('H:i') : 'läuft')),
                TextColumn::make('minuten')
                    ->label('Dauer')
                    ->formatStateUsing(fn ($state): string => Arbeitszeit::format($state === null ? null : (int) $state))
                    ->placeholder('läuft'),
                TextColumn::make('anfrage_id')
                    ->label('Auftrag')
                    ->formatStateUsing(fn (Arbeitszeit $record): string => $record->anfrage?->kurztitel() ?? '–')
                    ->placeholder('–')
                    ->wrap(),
                TextColumn::make('notiz')
                    ->label('Notiz')
                    ->placeholder('–')
                    ->visibleFrom('md'),
            ])
            ->paginated([10, 25, 50])
            ->emptyStateHeading('Noch keine Zeiten')
            ->emptyStateDescription('Oben auf „Einstempeln“ tippen.');
    }
}
