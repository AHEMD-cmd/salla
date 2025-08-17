<?php

namespace App\Filament\Resources\SallaSettingResource\Pages;

use App\Filament\Resources\SallaSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSallaSettings extends ListRecords
{
    protected static string $resource = SallaSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
            ->label(__('add_salla_setting'))
            ->createAnother(false),
        ];
    }
}
