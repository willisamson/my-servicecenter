<?php

namespace App\Filament\Pages;

use App\Models\Einstellung;
use App\Services\Rechnungsstellung;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Throwable;
use UnitEnum;

/**
 * Aussehen und Pflichtangaben deiner Rechnungen an die Betriebe.
 * Firmenname und Anschrift kommen aus "Einstellungen".
 */
class Rechnungslayout extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaintBrush;

    protected static string|UnitEnum|null $navigationGroup = 'Abrechnung';

    protected static ?string $title = 'Rechnungslayout & Bankdaten';

    protected static ?string $navigationLabel = 'Rechnungslayout';

    protected static ?string $slug = 'rechnungslayout';

    protected static ?int $navigationSort = 40;

    /** Felder dieser Seite (der Rest der Einstellungen bleibt unberührt). */
    protected const FELDER = [
        'rechnung_logo', 'rechnung_farbe', 'rechnung_einleitung', 'rechnung_schluss', 'rechnung_mailtext',
        'rechnung_praefix', 'zahlungsziel_tage', 'kleinunternehmer', 'ust_satz', 'steuernummer', 'ust_id',
        'bank_inhaber', 'bank_name', 'iban', 'bic', 'umsatz_ziel',
    ];

    public ?array $data = [];

    public function mount(): void
    {
        $daten = Einstellung::aktuell()->daten ?? [];

        $this->form->fill(array_merge([
            'rechnung_farbe' => '#C2410C',
            'rechnung_praefix' => 'RE',
            'zahlungsziel_tage' => 14,
            'ust_satz' => 19,
            'umsatz_ziel' => 11000,
        ], array_intersect_key($daten, array_flip(self::FELDER))));
    }

    public function form(Schema $schema): Schema
    {
        $fehlt = Rechnungsstellung::fehlendeAngaben();

        return $schema
            ->components([
                Section::make('Noch nicht startklar')
                    ->description('Rechnungen werden erst erstellt, wenn diese Angaben da sind: '.implode(', ', $fehlt).'. Firmenname und Anschrift trägst du unter „Einstellungen“ ein.')
                    ->icon(Heroicon::OutlinedExclamationTriangle)
                    ->iconColor('warning')
                    ->visible($fehlt !== [])
                    ->schema([]),
                Section::make('Aussehen')
                    ->columns(2)
                    ->schema([
                        FileUpload::make('rechnung_logo')
                            ->label('Logo auf der Rechnung')
                            ->helperText('Leer = Logo aus den Einstellungen. PNG oder JPG, am besten quer.')
                            ->image()
                            ->acceptedFileTypes(['image/png', 'image/jpeg'])
                            ->disk('public')
                            ->directory('plattform')
                            ->visibility('public')
                            ->maxSize(2048),
                        ColorPicker::make('rechnung_farbe')
                            ->label('Akzentfarbe')
                            ->helperText('Für Tabellenkopf und Linie unter der Überschrift.')
                            ->regex('/^#[0-9a-fA-F]{6}$/'),
                        Textarea::make('rechnung_einleitung')
                            ->label('Text über den Positionen')
                            ->placeholder('vielen Dank für Ihr Vertrauen. Für unsere Leistungen berechnen wir Ihnen:')
                            ->rows(3)
                            ->maxLength(1000),
                        Textarea::make('rechnung_schluss')
                            ->label('Text unter den Summen')
                            ->placeholder('Bei Fragen zur Rechnung erreichen Sie uns jederzeit per E-Mail.')
                            ->rows(3)
                            ->maxLength(1000),
                        Textarea::make('rechnung_mailtext')
                            ->label('Text der E-Mail zur Rechnung')
                            ->placeholder('anbei erhalten Sie Ihre Rechnung für my-servicecenter als PDF.')
                            ->rows(3)
                            ->maxLength(2000)
                            ->columnSpanFull(),
                    ]),
                Section::make('Nummern & Zahlungsziel')
                    ->columns(3)
                    ->schema([
                        TextInput::make('rechnung_praefix')
                            ->label('Kürzel vor der Nummer')
                            ->helperText('Ergibt z. B. RE-2026-0001. Nummern laufen je Jahr fortlaufend.')
                            ->regex('/^[A-Za-z0-9]{1,8}$/')
                            ->required(),
                        TextInput::make('zahlungsziel_tage')
                            ->label('Zahlungsziel')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(90)
                            ->suffix('Tage')
                            ->required(),
                        TextInput::make('umsatz_ziel')
                            ->label('Umsatzziel pro Monat (netto)')
                            ->helperText('Für die Anzeige im Dashboard.')
                            ->numeric()
                            ->minValue(0)
                            ->suffix('€'),
                    ]),
                Section::make('Steuer')
                    ->columns(3)
                    ->schema([
                        Toggle::make('kleinunternehmer')
                            ->label('Kleinunternehmer (§ 19 UStG)')
                            ->helperText('Dann ohne Umsatzsteuer, mit Hinweis auf der Rechnung.')
                            ->live()
                            ->columnSpanFull(),
                        TextInput::make('ust_satz')
                            ->label('Umsatzsteuer')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(30)
                            ->suffix('%')
                            ->hidden(fn (Get $get): bool => (bool) $get('kleinunternehmer')),
                        TextInput::make('steuernummer')
                            ->label('Steuernummer')
                            ->maxLength(50),
                        TextInput::make('ust_id')
                            ->label('USt-IdNr.')
                            ->maxLength(50),
                    ]),
                Section::make('Bankverbindung')
                    ->columns(2)
                    ->schema([
                        TextInput::make('bank_inhaber')->label('Kontoinhaber')->maxLength(255),
                        TextInput::make('bank_name')->label('Bank')->maxLength(255),
                        TextInput::make('iban')
                            ->label('IBAN')
                            ->maxLength(42)
                            ->dehydrateStateUsing(fn (?string $state): ?string => $state ? trim(chunk_split(strtoupper(preg_replace('/\s+/', '', $state)), 4, ' ')) : null)
                            ->rule('regex:/^[A-Za-z]{2}[0-9]{2}[A-Za-z0-9 ]{10,38}$/'),
                        TextInput::make('bic')->label('BIC')->maxLength(11),
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

    protected function getHeaderActions(): array
    {
        return [
            Action::make('vorschau')
                ->label('Vorschau als PDF')
                ->icon(Heroicon::OutlinedEye)
                ->color('gray')
                ->action(function () {
                    try {
                        $pdf = app(Rechnungsstellung::class)->vorschau();
                    } catch (Throwable $e) {
                        Notification::make()->title('Vorschau nicht möglich')->body($e->getMessage())->danger()->send();

                        return null;
                    }

                    return response()->streamDownload(fn () => print ($pdf), 'Rechnung-Vorschau.pdf', ['Content-Type' => 'application/pdf']);
                }),
        ];
    }

    public function save(): void
    {
        $daten = array_intersect_key($this->form->getState(), array_flip(self::FELDER));

        Einstellung::speichern($daten);

        Notification::make()
            ->title('Rechnungslayout gespeichert')
            ->body('Mit „Vorschau als PDF“ siehst du, wie die Rechnung aussieht.')
            ->success()
            ->send();
    }
}
