<?php

namespace App\Filament\Resources\UserSubscriptionResource\Pages;

use App\Filament\Resources\UserSubscriptionResource;
use App\Models\Subscription;
use Filament\Resources\Pages\CreateRecord;
use Carbon\Carbon;

class CreateUserSubscription extends CreateRecord
{
    protected static string $resource = UserSubscriptionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Auto-generate order number if not set
        if (empty($data['order_number'])) {
            $lastOrder = static::getModel()::latest('id')->first();
            $nextId = $lastOrder ? $lastOrder->id + 1 : 1;
            $data['order_number'] = 'ORD-' . str_pad($nextId, 6, '0', STR_PAD_LEFT);
        }

        // Auto-calculate end_date based on subscription duration
        if (!empty($data['subscription_id']) && !empty($data['start_date'])) {
            $subscription = Subscription::find($data['subscription_id']);
            if ($subscription) {
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

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم إنشاء الاشتراك بنجاح';
    }
}