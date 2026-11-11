<?php

namespace App\Filament\Betrieb\Resources\Users\Pages;

use App\Filament\Betrieb\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
}
