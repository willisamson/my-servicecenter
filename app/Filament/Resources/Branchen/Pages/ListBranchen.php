<?php

namespace App\Filament\Resources\Branchen\Pages;

use App\Filament\Resources\Branchen\BrancheResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBranchen extends ListRecords
{
    protected static string $resource = BrancheResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
