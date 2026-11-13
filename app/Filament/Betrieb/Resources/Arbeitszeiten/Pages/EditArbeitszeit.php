<?php

namespace App\Filament\Betrieb\Resources\Arbeitszeiten\Pages;

use App\Filament\Betrieb\Resources\Arbeitszeiten\ArbeitszeitResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditArbeitszeit extends EditRecord
{
    protected static string $resource = ArbeitszeitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
