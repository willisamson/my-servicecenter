<?php

namespace App\Filament\Pages;

use App\Models\Einstellung;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Firmendaten, Logo und Rechtliches der Plattform.
 * Daraus entstehen Impressum, Datenschutz, Kontakt auf der Startseite und das Logo.
 */
class Einstellungen extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $title = 'Meine Firma & Einstellungen';

    protected static ?string $navigationLabel = 'Einstellungen';

    protected static ?string $slug = 'einstellungen';

    protected static ?int $navigationSort = 90;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(Einstellung::aktuell()->daten ?? []);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Firma')
                    ->description('Erscheint im Impressum der Plattform.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('firmenname')->label('Firmenname und Rechtsform')->placeholder('z. B. Muster GmbH oder Vorname Nachname')->maxLength(255)->columnSpanFull(),
                        TextInput::make('vertreten_durch')->label('Vertreten durch / Inhaber')->maxLength(255),
                        TextInput::make('ust_id')->label('USt-IdNr.')->maxLength(50),
                        TextInput::make('strasse')->label('Straße und Hausnummer')->maxLength(255),
                        TextInput::make('plz')->label('PLZ')->maxLength(10),
                        TextInput::make('ort')->label('Ort')->maxLength(255),
                        TextInput::make('register')->label('Registereintrag (falls vorhanden)')->placeholder('z. B. Amtsgericht …, HRB …')->maxLength(255),
                        TextInput::make('telefon')->label('Telefon')->tel()->maxLength(50),
                        TextInput::make('email')->label('E-Mail')->email()->maxLength(255)->helperText('Auch Kontakt für „Pilotbetrieb werden“ auf der Startseite.'),
                    ]),
                Section::make('Logo')
                    ->schema([
                        FileUpload::make('logo')
                            ->label('Logo')
                            ->helperText('PNG, JPG, SVG oder WebP, max. 2 MB. Erscheint auf der Startseite und im Admin-Bereich.')
                            ->image()
                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/svg+xml', 'image/webp'])
                            ->disk('public')
                            ->directory('plattform')
                            ->visibility('public')
                            ->maxSize(2048),
                    ]),
                Section::make('Rechtliches')
                    ->collapsible()
                    ->schema([
                        Textarea::make('impressum_zusatz')
                            ->label('Zusätzliche Angaben im Impressum')
                            ->helperText('z. B. „Verantwortlich für den Inhalt nach § 18 Abs. 2 MStV: …“ oder Hinweise zur Streitbeilegung.')
                            ->rows(5)
                            ->maxLength(10000),
                        Textarea::make('datenschutz')
                            ->label('Datenschutzerklärung der Plattform')
                            ->helperText('Am sichersten mit einem Generator oder anwaltlich erstellen.')
                            ->rows(16)
                            ->maxLength(80000),
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
        Einstellung::speichern($this->form->getState());

        Notification::make()
            ->title('Einstellungen gespeichert')
            ->success()
            ->send();
    }
}
