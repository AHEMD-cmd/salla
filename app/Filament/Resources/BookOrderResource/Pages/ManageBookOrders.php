<?php

namespace App\Filament\Resources\BookOrderResource\Pages;

use App\Filament\Resources\BookOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageBookOrders extends ManageRecords
{
    protected static string $resource = BookOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
            ->label(__('add_new_order')),
        ];
    }
}
