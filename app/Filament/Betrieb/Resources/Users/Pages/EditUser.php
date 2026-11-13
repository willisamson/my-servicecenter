<?php

namespace App\Filament\Betrieb\Resources\Users\Pages;

use App\Filament\Betrieb\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                // Mit erfassten Arbeitszeiten nicht löschbar (Aufbewahrungspflicht) – stattdessen Passwort ändern.
                ->hidden(fn (): bool => $this->record->is(auth()->user()) || $this->record->arbeitszeiten()->exists()),
        ];
    }
}
