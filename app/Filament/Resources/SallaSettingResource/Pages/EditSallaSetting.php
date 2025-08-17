<?php

namespace App\Filament\Resources\SallaSettingResource\Pages;

use App\Filament\Resources\SallaSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSallaSetting extends EditRecord
{
    protected static string $resource = SallaSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
