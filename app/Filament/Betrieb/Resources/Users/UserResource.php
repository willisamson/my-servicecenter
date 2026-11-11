<?php

namespace App\Filament\Betrieb\Resources\Users;

use App\Filament\Betrieb\Resources\Users\Pages\CreateUser;
use App\Filament\Betrieb\Resources\Users\Pages\EditUser;
use App\Filament\Betrieb\Resources\Users\Pages\ListUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Mitarbeiter des Betriebs – nur für den Inhaber sichtbar.
 */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $modelLabel = 'Mitarbeiter';

    protected static ?string $pluralModelLabel = 'Mitarbeiter';

    protected static ?string $navigationLabel = 'Mitarbeiter';

    protected static ?string $slug = 'mitarbeiter';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 20;

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->istInhaber();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Name')
                ->required()
                ->maxLength(255),
            TextInput::make('email')
                ->label('E-Mail')
                ->email()
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true),
            Select::make('rolle')
                ->label('Rolle')
                ->options(User::ROLLEN)
                ->default('mitarbeiter')
                ->required(),
            TextInput::make('password')
                ->label('Passwort')
                ->password()
                ->revealable()
                ->minLength(8)
                ->required(fn (string $operation): bool => $operation === 'create')
                ->dehydrated(fn (?string $state): bool => filled($state))
                ->helperText(fn (string $operation): ?string => $operation === 'edit' ? 'Leer lassen, um das Passwort nicht zu ändern.' : null),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('E-Mail')
                    ->searchable(),
                TextColumn::make('rolle')
                    ->label('Rolle')
                    ->formatStateUsing(fn (?string $state): string => User::ROLLEN[$state] ?? (string) $state),
                TextColumn::make('created_at')
                    ->label('Angelegt')
                    ->dateTime('d.m.Y')
                    ->sortable(),
            ])
            ->defaultSort('name')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn (User $record): bool => $record->is(auth()->user())),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
