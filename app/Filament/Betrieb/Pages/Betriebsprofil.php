<?php

namespace App\Filament\Betrieb\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Stammdaten und Auftritt des Betriebs. Daraus entsteht später die Mini-Website.
 * Gespeichert wird im Betriebs-Datensatz der zentralen Datenbank (für die Portal-Suche).
 */
class Betriebsprofil extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static ?string $title = 'Betriebsprofil';

    protected static ?string $slug = 'profil';

    protected static ?int $navigationSort = 10;

    /** Felder, die hier bearbeitet werden dürfen. */
    protected const FELDER = [
        'name', 'ort', 'email', 'telefon', 'strasse', 'plz',
        'beschreibung', 'leistungen', 'oeffnungszeiten', 'notdienst',
        'impressum', 'datenschutz',
    ];

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->istBueroOderInhaber();
    }

    public function mount(): void
    {
        $this->form->fill(tenant()->only(self::FELDER));
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Kontakt')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')->label('Name des Betriebs')->required()->maxLength(255)->columnSpanFull(),
                        TextInput::make('strasse')->label('Straße und Hausnummer')->maxLength(255),
                        TextInput::make('plz')->label('PLZ')->maxLength(10),
                        TextInput::make('ort')->label('Ort')->maxLength(255),
                        TextInput::make('telefon')->label('Telefon')->tel()->maxLength(50),
                        TextInput::make('email')
                            ->label('E-Mail')
                            ->email()
                            ->maxLength(255)
                            ->helperText('An diese Adresse schicken wir neue Anfragen. Leer = an alle Inhaber.'),
                    ]),
                Section::make('Auftritt')
                    ->schema([
                        Textarea::make('beschreibung')
                            ->label('Über uns')
                            ->helperText('Kurzer Text für die Mini-Website: wer ihr seid, was ihr gut könnt, wo ihr arbeitet.')
                            ->rows(4)
                            ->maxLength(2000),
                        TagsInput::make('leistungen')
                            ->label('Leistungen')
                            ->placeholder('z. B. Wartung, Notdienst, Wärmepumpe – mit Enter bestätigen'),
                        Textarea::make('oeffnungszeiten')
                            ->label('Öffnungszeiten')
                            ->rows(3)
                            ->maxLength(500),
                        Toggle::make('notdienst')
                            ->label('Notdienst verfügbar'),
                    ]),
                Section::make('Rechtliches')
                    ->description('Pflichtangaben für die Mini-Website. Ohne Impressum und Datenschutzerklärung sollte die Seite nicht für Kunden freigegeben werden.')
                    ->collapsible()
                    ->schema([
                        Textarea::make('impressum')
                            ->label('Impressum')
                            ->helperText('Typisch für Handwerksbetriebe: Name und Rechtsform, Anschrift, Vertretungsberechtigte, Telefon und E-Mail, Registereintrag, USt-IdNr., zuständige Handwerkskammer und Berufsbezeichnung.')
                            ->rows(10)
                            ->maxLength(20000),
                        Textarea::make('datenschutz')
                            ->label('Datenschutzerklärung')
                            ->helperText('Muss u. a. das Anfrageformular und my-servicecenter als technischen Dienstleister abdecken. Am sichersten mit einem Generator oder Anwalt erstellen.')
                            ->rows(14)
                            ->maxLength(60000),
                    ]),
            ])
            ->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')
                            ->label('Speichern')
                            ->submit('save'),
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        $daten = $this->form->getState();

        tenant()->update(array_intersect_key($daten, array_flip(self::FELDER)));

        Notification::make()
            ->title('Betriebsprofil gespeichert')
            ->success()
            ->send();
    }
}
