<?php

namespace App\Filament\Betrieb\Resources\Anfragen\Pages;

use App\Filament\Betrieb\Resources\Anfragen\AnfrageResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAnfrage extends CreateRecord
{
    protected static string $resource = AnfrageResource::class;

    /** Nach dem Anlegen direkt zur Bearbeitung – dort steht der Status-Link für den Kunden. */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
