<?php

namespace App\Filament\Resources\Abos\Pages;

use App\Filament\Resources\Abos\AboResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAbo extends EditRecord
{
    protected static string $resource = AboResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Rechnungen bleiben beim Löschen erhalten.
            DeleteAction::make(),
        ];
    }
}
