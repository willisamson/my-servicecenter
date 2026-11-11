<?php

namespace App\Filament\Betrieb\Resources\Anfragen\Pages;

use App\Filament\Betrieb\Resources\Anfragen\AnfrageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAnfragen extends ListRecords
{
    protected static string $resource = AnfrageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Telefonische Anfrage erfassen'),
        ];
    }
}
