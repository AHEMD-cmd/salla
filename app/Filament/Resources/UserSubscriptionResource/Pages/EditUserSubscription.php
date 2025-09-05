<?php

namespace App\Filament\Resources\UserSubscriptionResource\Pages;

use App\Filament\Resources\UserSubscriptionResource;
use App\Models\Subscription;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Carbon\Carbon;
use Filament\Notifications\Notification;

class EditUserSubscription extends EditRecord
{
    protected static string $resource = UserSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            // Actions\Action::make('extend_subscription')
            //     ->label('تمديد الاشتراك')
            //     ->icon('heroicon-o-calendar-days')
            //     ->color('info')
            //     ->form([
            //         \Filament\Forms\Components\TextInput::make('extend_days')
            //             ->label('عدد الأيام للتمديد')
            //             ->numeric()
            //             ->required()
            //             ->minValue(1)
            //             ->suffix('يوم'),
            //     ])
            //     ->action(function (array $data) {
            //         $this->record->update([
            //             'end_date' => Carbon::parse($this->record->end_date)
            //                 ->addDays((int) $data['extend_days'])
            //         ]);

            //         Notification::make()
            //             ->title('تم تمديد الاشتراك بنجاح')
            //             ->success()
            //             ->send();
            //     }),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Auto-calculate end_date based on subscription duration if changed
        if (!empty($data['subscription_id']) && !empty($data['start_date'])) {
            $subscription = Subscription::find($data['subscription_id']);
            if ($subscription && $data['subscription_id'] != $this->record->subscription_id) {
                $data['end_date'] = Carbon::parse($data['start_date'])
                    ->addDays($subscription->duration)
                    ->format('Y-m-d');
            }
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'تم تحديث الاشتراك بنجاح';
    }
}

