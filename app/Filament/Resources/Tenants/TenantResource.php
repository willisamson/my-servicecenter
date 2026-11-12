<?php

namespace App\Filament\Resources\Tenants;

use App\Filament\Resources\Tenants\Pages\CreateTenant;
use App\Filament\Resources\Tenants\Pages\EditTenant;
use App\Filament\Resources\Tenants\Pages\ListTenants;
use App\Models\Branche;
use App\Models\Tenant;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Verwaltung der Betriebe im Admin-Bereich.
 * Beim Anlegen entsteht automatisch die eigene Datenbank des Betriebs.
 */
class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static ?string $modelLabel = 'Betrieb';

    protected static ?string $pluralModelLabel = 'Betriebe';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Name des Betriebs')
                ->required()
                ->maxLength(255),
            TextInput::make('id')
                ->label('Subdomain')
                ->helperText('Nur Kleinbuchstaben, Zahlen und Bindestriche. Wird zur Adresse und zum Datenbanknamen und kann später nicht mehr geändert werden.')
                ->required()
                ->maxLength(40)
                ->regex('/^[a-z0-9]+(-[a-z0-9]+)*$/')
                ->unique(ignoreRecord: true)
                ->disabledOn('edit')
                ->suffix('.'.config('tenancy.central_domains')[0]),
            Select::make('branche')
                ->label('Branche')
                ->options(fn (): array => Branche::optionen())
                ->default('heizung')
                ->required(),
            TextInput::make('ort')
                ->label('Ort')
                ->maxLength(255),
            TextInput::make('email')
                ->label('E-Mail')
                ->email()
                ->maxLength(255),
            Toggle::make('aktiv')
                ->label('Aktiv')
                ->default(true),
            Toggle::make('im_portal')
                ->label('In der Portal-Suche')
                ->helperText('Nur einschalten, wenn Impressum und Datenschutz des Betriebs fertig sind.')
                ->default(false),
            Section::make('Inhaber-Zugang')
                ->description('Damit meldet sich der Inhaber unter <subdomain>/dashboard an. Wird nur beim Anlegen abgefragt.')
                ->visibleOn('create')
                ->columnSpanFull()
                ->schema([
                    TextInput::make('inhaber_name')
                        ->label('Name des Inhabers')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('inhaber_email')
                        ->label('E-Mail des Inhabers')
                        ->email()
                        ->required()
                        ->maxLength(255),
                    TextInput::make('inhaber_passwort')
                        ->label('Start-Passwort')
                        ->password()
                        ->revealable()
                        ->required()
                        ->minLength(8),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Betrieb')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('id')
                    ->label('Adresse')
                    ->formatStateUsing(fn (string $state): string => $state.'.'.config('tenancy.central_domains')[0])
                    ->url(fn (Tenant $record): string => $record->subdomainUrl())
                    ->openUrlInNewTab(),
                TextColumn::make('branche')
                    ->label('Branche')
                    ->formatStateUsing(fn (?string $state): string => Branche::label($state)),
                TextColumn::make('ort')
                    ->label('Ort')
                    ->searchable(),
                IconColumn::make('aktiv')
                    ->label('Aktiv')
                    ->boolean(),
                IconColumn::make('im_portal')
                    ->label('In Suche')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Angelegt')
                    ->dateTime('d.m.Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTenants::route('/'),
            'create' => CreateTenant::route('/create'),
            'edit' => EditTenant::route('/{record}/edit'),
        ];
    }
}
