<?php

namespace App\Filament\Betrieb\Resources\Auftraege\Pages;

use App\Filament\Betrieb\Resources\Auftraege\AuftragResource;
use App\Models\Arbeitszeit;
use App\Models\AuftragNotiz;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

/**
 * Ein Auftrag aus Sicht des Mitarbeiters, mit großen Knöpfen für unterwegs.
 */
class ViewAuftrag extends ViewRecord
{
    protected static string $resource = AuftragResource::class;

    public function getTitle(): string
    {
        return $this->record->name.' – '.$this->record->anliegenLabel();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('anrufen')
                ->label('Anrufen')
                ->icon(Heroicon::OutlinedPhone)
                ->color('gray')
                ->visible(fn (): bool => filled($this->record->telefon))
                ->url(fn (): string => 'tel:'.preg_replace('/[^0-9+]/', '', (string) $this->record->telefon)),
            Action::make('route')
                ->label('Route')
                ->icon(Heroicon::OutlinedMapPin)
                ->color('gray')
                ->visible(fn (): bool => $this->record->routenLink() !== null)
                ->url(fn (): ?string => $this->record->routenLink())
                ->openUrlInNewTab(),
            Action::make('unterwegs')
                ->label('Losfahren')
                ->icon(Heroicon::OutlinedTruck)
                ->color('primary')
                ->visible(fn (): bool => in_array($this->record->status, ['neu', 'angenommen', 'termin'], true))
                ->requiresConfirmation()
                ->modalHeading('Losfahren?')
                ->modalDescription('Der Kunde sieht auf seiner Status-Seite „Monteur unterwegs“. Ihre Stempeluhr läuft ab jetzt für diesen Auftrag.')
                ->action(function () {
                    $this->record->update(['status' => 'unterwegs']);
                    Arbeitszeit::einstempeln(auth()->id(), $this->record->id, 'Anfahrt');
                    Notification::make()->title('Gute Fahrt! Status: unterwegs')->success()->send();
                }),
            Action::make('erledigt')
                ->label('Erledigt')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->visible(fn (): bool => in_array($this->record->status, ['termin', 'unterwegs', 'angenommen'], true))
                ->modalHeading('Auftrag abschließen')
                ->modalDescription('Der Kunde sieht danach „Erledigt“. Die Stempeluhr für diesen Auftrag wird beendet.')
                ->schema([
                    Textarea::make('notiz')
                        ->label('Was wurde gemacht? (optional)')
                        ->rows(3)
                        ->maxLength(5000),
                ])
                ->action(function (array $data) {
                    if (filled($data['notiz'] ?? null)) {
                        AuftragNotiz::create(['anfrage_id' => $this->record->id, 'text' => $data['notiz']]);
                    }

                    $this->record->update(['status' => 'erledigt']);

                    $laufend = Arbeitszeit::laufend(auth()->id());
                    if ($laufend && $laufend->anfrage_id === $this->record->id) {
                        Arbeitszeit::ausstempeln(auth()->id());
                    }

                    Notification::make()->title('Auftrag erledigt – danke!')->success()->send();
                }),
            Action::make('stempeln')
                ->label('Zeit auf diesen Auftrag')
                ->icon(Heroicon::OutlinedClock)
                ->color('gray')
                ->visible(fn (): bool => Arbeitszeit::laufend(auth()->id())?->anfrage_id !== $this->record->id && in_array($this->record->status, ['termin', 'unterwegs', 'angenommen'], true))
                ->requiresConfirmation()
                ->modalDescription('Stempeluhr startet (oder wechselt) auf diesen Auftrag.')
                ->action(function () {
                    Arbeitszeit::einstempeln(auth()->id(), $this->record->id);
                    Notification::make()->title('Stempeluhr läuft für diesen Auftrag')->success()->send();
                }),
        ];
    }
}
