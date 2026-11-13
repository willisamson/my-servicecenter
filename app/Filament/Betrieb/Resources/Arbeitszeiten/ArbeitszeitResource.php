<?php

namespace App\Filament\Betrieb\Resources\Arbeitszeiten;

use App\Filament\Betrieb\Resources\Arbeitszeiten\Pages\CreateArbeitszeit;
use App\Filament\Betrieb\Resources\Arbeitszeiten\Pages\EditArbeitszeit;
use App\Filament\Betrieb\Resources\Arbeitszeiten\Pages\ListArbeitszeiten;
use App\Models\Anfrage;
use App\Models\Arbeitszeit;
use App\Models\User;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Arbeitszeiten aller Mitarbeiter – für Büro und Inhaber: prüfen, korrigieren, exportieren.
 */
class ArbeitszeitResource extends Resource
{
    protected static ?string $model = Arbeitszeit::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTableCells;

    protected static ?string $modelLabel = 'Arbeitszeit';

    protected static ?string $pluralModelLabel = 'Arbeitszeiten';

    protected static ?string $slug = 'arbeitszeiten';

    protected static ?int $navigationSort = 4;

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->istBueroOderInhaber();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('user_id')
                ->label('Mitarbeiter')
                ->options(fn (): array => User::auswahl())
                ->searchable()
                ->required(),
            Select::make('anfrage_id')
                ->label('Auftrag')
                ->options(fn (): array => Anfrage::query()->latest()->limit(300)->get()->mapWithKeys(fn (Anfrage $a) => [$a->id => $a->kurztitel()])->all())
                ->searchable()
                ->placeholder('Ohne Auftrag'),
            DateTimePicker::make('beginn')
                ->label('Beginn')
                ->seconds(false)
                ->timezone('Europe/Berlin')
                ->required(),
            DateTimePicker::make('ende')
                ->label('Ende')
                ->seconds(false)
                ->timezone('Europe/Berlin')
                ->after('beginn')
                ->helperText('Leer = läuft noch.'),
            TextInput::make('notiz')
                ->label('Notiz')
                ->placeholder('z. B. Korrektur: vergessen auszustempeln')
                ->maxLength(255)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('user', 'anfrage'))
            ->defaultSort('beginn', 'desc')
            ->columns([
                TextColumn::make('user.name')
                    ->label('Mitarbeiter')
                    ->searchable(),
                TextColumn::make('beginn')
                    ->label('Tag')
                    ->dateTime('d.m.Y', 'Europe/Berlin')
                    ->sortable(),
                TextColumn::make('von_bis')
                    ->label('Von – bis')
                    ->state(fn (Arbeitszeit $record): string => $record->beginn->copy()->timezone('Europe/Berlin')->format('H:i')
                        .' – '.($record->ende ? $record->ende->copy()->timezone('Europe/Berlin')->format('H:i') : 'läuft')),
                TextColumn::make('minuten')
                    ->label('Dauer')
                    ->formatStateUsing(fn ($state): string => Arbeitszeit::format((int) $state))
                    ->placeholder('läuft')
                    ->summarize(Sum::make()->label('Summe')->formatStateUsing(fn ($state): string => Arbeitszeit::format((int) $state))),
                TextColumn::make('anfrage_id')
                    ->label('Auftrag')
                    ->formatStateUsing(fn (Arbeitszeit $record): string => $record->anfrage?->kurztitel() ?? '–')
                    ->placeholder('–')
                    ->wrap(),
                TextColumn::make('notiz')
                    ->label('Notiz')
                    ->placeholder('–')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('user_id')
                    ->label('Mitarbeiter')
                    ->options(fn (): array => User::auswahl()),
                Filter::make('zeitraum')
                    ->schema([
                        DatePicker::make('von')->label('Von')->native(false)->displayFormat('d.m.Y')->default(now('Europe/Berlin')->startOfMonth()),
                        DatePicker::make('bis')->label('Bis')->native(false)->displayFormat('d.m.Y'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (! empty($data['von'])) {
                            $query->where('beginn', '>=', Carbon::parse($data['von'], 'Europe/Berlin')->startOfDay()->utc());
                        }
                        if (! empty($data['bis'])) {
                            $query->where('beginn', '<=', Carbon::parse($data['bis'], 'Europe/Berlin')->endOfDay()->utc());
                        }

                        return $query;
                    })
                    ->indicateUsing(function (array $data): ?string {
                        $von = ! empty($data['von']) ? Carbon::parse($data['von'])->format('d.m.Y') : null;
                        $bis = ! empty($data['bis']) ? Carbon::parse($data['bis'])->format('d.m.Y') : null;

                        return $von || $bis ? 'Zeitraum: '.($von ?? '…').' – '.($bis ?? 'heute') : null;
                    }),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListArbeitszeiten::route('/'),
            'create' => CreateArbeitszeit::route('/create'),
            'edit' => EditArbeitszeit::route('/{record}/edit'),
        ];
    }
}
