<?php

namespace App\Filament\Resources\Tenants\Pages;

use App\Filament\Resources\Tenants\TenantResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    /**
     * Nach dem Anlegen (Datenbank ist dann schon erstellt) die Subdomain registrieren.
     */
    protected function afterCreate(): void
    {
        $this->record->domains()->create([
            'domain' => $this->record->getTenantKey(),
        ]);
    }
}
