<?php

namespace App\Filament\Resources\SubscriptionResource\Pages;

use App\Filament\Resources\SubscriptionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSubscription extends CreateRecord
{
    protected static string $resource = SubscriptionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set unlimited fields to null when unlimited toggles are true
        if ($data['unlimited_files'] ?? false) {
            $data['number_of_files'] = null;
        }

        if ($data['unlimited_downloads'] ?? false) {
            $data['number_of_downloads'] = null;
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}