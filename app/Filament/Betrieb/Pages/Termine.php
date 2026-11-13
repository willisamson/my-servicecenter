<?php

namespace App\Filament\Betrieb\Pages;

use App\Filament\Betrieb\Resources\Anfragen\AnfrageResource;
use App\Filament\Betrieb\Resources\Auftraege\AuftragResource;
use App\Models\Anfrage;
use App\Models\User;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Termine nach Tagen: Mitarbeiter sehen ihre eigenen, Büro und Inhaber alle.
 */
class Termine extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $title = 'Termine';

    protected static ?string $slug = 'termine';

    protected static ?int $navigationSort = 2;

    protected const WOCHENTAGE = ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'];

    public function content(Schema $schema): Schema
    {
        return $schema->components([EmbeddedTable::make()]);
    }

    public static function tagTitel(?Carbon $termin): string
    {
        if (! $termin) {
            return 'Ohne Termin';
        }

        $tag = $termin->copy()->timezone('Europe/Berlin');
        $heute = now('Europe/Berlin');
        $zusatz = match (true) {
            $tag->isSameDay($heute) => ' (heute)',
            $tag->isSameDay($heute->copy()->addDay()) => ' (morgen)',
            default => '',
        };

        return self::WOCHENTAGE[$tag->dayOfWeek].', '.$tag->format('d.m.Y').$zusatz;
    }

    public function table(Table $table): Table
    {
        $buero = (bool) auth()->user()?->istBueroOderInhaber();

        return $table
            ->query(function () use ($buero): Builder {
                $query = Anfrage::query()
                    ->with('mitarbeiter')
                    ->whereNotNull('termin_am')
                    ->where('termin_am', '>=', now('Europe/Berlin')->startOfDay()->utc())
                    ->whereNotIn('status', ['abgelehnt']);

                return $buero ? $query : $query->where('mitarbeiter_id', auth()->id());
            })
            ->defaultSort('termin_am')
            ->defaultGroup(
                Group::make('termin_am')
                    ->label('Tag')
                    ->date()
                    ->getTitleFromRecordUsing(fn (Anfrage $record): string => self::tagTitel($record->termin_am))
                    ->collapsible()
                    ->titlePrefixedWithLabel(false)
            )
            ->groupingSettingsHidden()
            ->columns([
                TextColumn::make('termin_am')
                    ->label('Uhrzeit')
                    ->state(fn (Anfrage $record): string => $record->termin_am->copy()->timezone('Europe/Berlin')->format('H:i')
                        .' – '.$record->termin_am->copy()->addMinutes($record->termin_dauer ?: 60)->timezone('Europe/Berlin')->format('H:i')),
                TextColumn::make('name')
                    ->label('Kunde')
                    ->description(fn (Anfrage $record): string => $record->anliegenLabel()),
                TextColumn::make('adresse')
                    ->label('Ort')
                    ->state(fn (Anfrage $record): string => trim(($record->adresse ?? '').' '.$record->plz))
                    ->url(fn (Anfrage $record): ?string => $record->routenLink())
                    ->openUrlInNewTab()
                    ->visibleFrom('md'),
                TextColumn::make('mitarbeiter.name')
                    ->label('Zuständig')
                    ->placeholder('niemand')
                    ->visible($buero),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => Anfrage::STATUS[$state] ?? (string) $state)
                    ->color(fn (?string $state): string => Anfrage::STATUS_FARBEN[$state] ?? 'gray'),
            ])
            ->filters([
                SelectFilter::make('zeitraum')
                    ->label('Zeitraum')
                    ->options(['7' => 'Nächste 7 Tage', '14' => 'Nächste 14 Tage', '31' => 'Nächster Monat', 'alle' => 'Alle kommenden'])
                    ->default('14')
                    ->selectablePlaceholder(false)
                    ->query(function (Builder $query, array $data): Builder {
                        $tage = $data['value'] ?? '14';

                        return is_numeric($tage)
                            ? $query->where('termin_am', '<', now('Europe/Berlin')->startOfDay()->addDays((int) $tage)->utc())
                            : $query;
                    }),
                SelectFilter::make('mitarbeiter_id')
                    ->label('Zuständig')
                    ->options(fn (): array => User::auswahl())
                    ->visible($buero),
            ])
            ->recordUrl(function (Anfrage $record) use ($buero): ?string {
                if ($record->mitarbeiter_id === auth()->id()) {
                    return AuftragResource::getUrl('view', ['record' => $record]);
                }

                return $buero ? AnfrageResource::getUrl('edit', ['record' => $record]) : null;
            })
            ->paginated([25, 50, 100])
            ->emptyStateHeading('Keine Termine in diesem Zeitraum');
    }
}
