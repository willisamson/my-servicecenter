<?php

namespace App\Filament\Resources\Tarife\Pages;

use App\Filament\Resources\Tarife\TarifResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTarif extends EditRecord
{
    protected static string $resource = TarifResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Ein Tarif mit Abos wird nicht gelöscht, sondern deaktiviert.
            DeleteAction::make()
                ->hidden(fn (): bool => $this->record->abos()->exists()),
        ];
    }
}
