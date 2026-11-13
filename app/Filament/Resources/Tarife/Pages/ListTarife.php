<?php

namespace App\Filament\Resources\Tarife\Pages;

use App\Filament\Resources\Tarife\TarifResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTarife extends ListRecords
{
    protected static string $resource = TarifResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
