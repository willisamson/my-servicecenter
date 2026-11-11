<?php

namespace App\Filament\Resources\Tenants\Pages;

use App\Filament\Resources\Tenants\TenantResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    /** Inhaber-Daten aus dem Formular (gehören nicht in die Tabelle "tenants"). */
    protected array $inhaber = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->inhaber = [
            'name' => $data['inhaber_name'],
            'email' => $data['inhaber_email'],
            'password' => $data['inhaber_passwort'],
            'rolle' => 'inhaber',
        ];

        unset($data['inhaber_name'], $data['inhaber_email'], $data['inhaber_passwort']);

        return $data;
    }

    /**
     * Nach dem Anlegen (Datenbank ist dann schon erstellt):
     * Subdomain registrieren und den Inhaber in der Datenbank des Betriebs anlegen.
     */
    protected function afterCreate(): void
    {
        $this->record->domains()->create([
            'domain' => $this->record->getTenantKey(),
        ]);

        $inhaber = $this->inhaber;

        $this->record->run(function () use ($inhaber) {
            User::create($inhaber);
        });
    }
}
