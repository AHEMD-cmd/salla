<?php

namespace App\Filament\Resources\SubscriptionResource\Pages;

use App\Filament\Resources\SubscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSubscription extends EditRecord
{
    protected static string $resource = SubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Set unlimited toggles based on null values
        $data['unlimited_files'] = is_null($data['number_of_files']);
        $data['unlimited_downloads'] = is_null($data['number_of_downloads']);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
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