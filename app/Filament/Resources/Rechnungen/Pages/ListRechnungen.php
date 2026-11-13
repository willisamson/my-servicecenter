<?php

namespace App\Filament\Resources\Rechnungen\Pages;

use App\Filament\Resources\Rechnungen\RechnungResource;
use App\Models\Abo;
use App\Models\Tenant;
use App\Services\Rechnungsstellung;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Throwable;

class ListRechnungen extends ListRecords
{
    protected static string $resource = RechnungResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('abrechnen')
                ->label('Fällige Abos abrechnen')
                ->icon(Heroicon::OutlinedPlay)
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription('Erstellt für alle Abos, die heute oder früher dran sind, die Rechnung und schickt sie per E-Mail. Das passiert sonst automatisch jeden Morgen (wenn eingeschaltet).')
                ->action(function () {
                    Artisan::call('abos:abrechnen');
                    Notification::make()
                        ->title('Abrechnung gelaufen')
                        ->body(preg_replace('/\s*\R\s*/', ' · ', trim(Artisan::output())))
                        ->success()
                        ->persistent()
                        ->send();
                }),
            Action::make('einzelrechnung')
                ->label('Einzelrechnung')
                ->icon(Heroicon::OutlinedPlus)
                ->modalHeading('Einzelrechnung an einen Betrieb')
                ->modalDescription('Für Einmaliges, z. B. Einrichtung oder Schulung. Die Rechnung bekommt die nächste fortlaufende Nummer.')
                ->modalWidth('3xl')
                ->schema([
                    Select::make('tenant_id')
                        ->label('Betrieb')
                        ->options(fn (): array => Tenant::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->required(),
                    DatePicker::make('leistung_am')
                        ->label('Leistungsdatum')
                        ->native(false)
                        ->displayFormat('d.m.Y')
                        ->default(today())
                        ->required(),
                    Repeater::make('positionen')
                        ->label('Positionen')
                        ->columns(4)
                        ->minItems(1)
                        ->defaultItems(1)
                        ->addActionLabel('Position hinzufügen')
                        ->schema([
                            TextInput::make('bezeichnung')->label('Leistung')->required()->maxLength(255)->columnSpan(2),
                            TextInput::make('menge')->label('Menge')->numeric()->default(1)->required(),
                            TextInput::make('einzelpreis')->label('Einzelpreis netto')->numeric()->step('0.01')->suffix('€')->required(),
                            TextInput::make('einheit')->label('Einheit')->default('pauschal')->maxLength(20),
                            TextInput::make('beschreibung')->label('Zusatz (optional)')->maxLength(255)->columnSpan(3),
                        ]),
                    Toggle::make('senden')->label('Per E-Mail senden')->default(true),
                ])
                ->action(function (array $data) {
                    $betrieb = Tenant::find($data['tenant_id']);
                    $abo = Abo::where('tenant_id', $data['tenant_id'])->first();
                    $tag = Carbon::parse($data['leistung_am']);

                    $positionen = array_values(array_map(fn (array $p) => [
                        'bezeichnung' => $p['bezeichnung'],
                        'beschreibung' => $p['beschreibung'] ?? null,
                        'menge' => (float) $p['menge'],
                        'einheit' => $p['einheit'] ?? '',
                        'einzelpreis' => round((float) $p['einzelpreis'], 2),
                    ], $data['positionen']));

                    $empfaenger = filled($abo?->rechnung_adresse)
                        ? array_values(array_filter(array_map('trim', preg_split('/\R/', $abo->rechnung_adresse))))
                        : array_values(array_filter([$betrieb?->name, $betrieb?->strasse, trim(($betrieb?->plz ?? '').' '.($betrieb?->ort ?? ''))]));

                    try {
                        $dienst = app(Rechnungsstellung::class);
                        $rechnung = $dienst->erstellen(
                            tenantId: $data['tenant_id'],
                            empfaenger: ['zeilen' => $empfaenger, 'kundennummer' => $data['tenant_id']],
                            positionen: $positionen,
                            leistungVon: $tag,
                            leistungBis: $tag,
                            email: $abo?->rechnung_email ?: $betrieb?->email,
                            aboId: $abo?->id,
                        );
                        $gesendet = ! empty($data['senden']) && $dienst->versenden($rechnung);
                    } catch (Throwable $e) {
                        Notification::make()->title('Rechnung nicht erstellt')->body($e->getMessage())->danger()->persistent()->send();

                        return;
                    }

                    Notification::make()
                        ->title('Rechnung '.$rechnung->nummer.' erstellt')
                        ->body($gesendet ? 'Per E-Mail gesendet.' : 'Nicht per E-Mail gesendet.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
