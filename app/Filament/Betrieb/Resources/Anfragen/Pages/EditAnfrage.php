<?php

namespace App\Filament\Betrieb\Resources\Anfragen\Pages;

use App\Filament\Betrieb\Resources\Anfragen\AnfrageResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAnfrage extends EditRecord
{
    protected static string $resource = AnfrageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('statusseite')
                ->label('Status-Seite ansehen')
                ->icon('heroicon-o-eye')
                ->color('gray')
                ->url(fn (): string => $this->record->status_link)
                ->openUrlInNewTab(),
            DeleteAction::make(),
        ];
    }
}
