<?php

namespace App\Filament\Resources\TelegramResource\Pages;

use Filament\Actions;
use App\Models\TelegramSetting;
use Illuminate\Support\Facades\Http;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\TelegramResource;


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
            ->visible(function () {
                return TelegramSetting::where('creator_id', auth()->user()->id)->count() == 1 && auth()->user()->can('ربط ب تيليغرام_telegram');
            })
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