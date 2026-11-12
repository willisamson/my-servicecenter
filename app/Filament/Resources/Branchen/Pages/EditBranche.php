<?php

namespace App\Filament\Resources\Branchen\Pages;

use App\Filament\Resources\Branchen\BrancheResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBranche extends EditRecord
{
    protected static string $resource = BrancheResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Löschen nur, wenn kein Betrieb diese Branche nutzt – sonst lieber deaktivieren.
            DeleteAction::make()
                ->hidden(fn (): bool => $this->record->anzahlBetriebe() > 0),
        ];
    }
}
