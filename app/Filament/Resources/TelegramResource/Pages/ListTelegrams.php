<?php

namespace App\Filament\Resources\TelegramResource\Pages;

use App\Filament\Resources\TelegramResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Http;


class ListTelegrams extends ListRecords
{
    protected static string $resource = TelegramResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
            ->createAnother(false)
            ->label(__('add_telegram_setting')),

            Actions\Action::make('setWebhook')
            ->label(' ربط البوت بـالموقع')
            ->action(function () {

                $response = Http::get(route('telegram.setWebhook'));

                if ($response->successful()) {
                    Notification::make()
                        ->title('تم إرسال رابط الـ Webhook بنجاح!')
                        ->success()
                        ->send();
                } else {
                    Notification::make()
                        ->title('فشل في ربط الـ Webhook!')
                        ->danger()
                        ->send();
                }
            }),
        ];
    }
}