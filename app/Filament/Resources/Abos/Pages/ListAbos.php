<?php

namespace App\Filament\Resources\Abos\Pages;

use App\Filament\Resources\Abos\AboResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAbos extends ListRecords
{
    protected static string $resource = AboResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
