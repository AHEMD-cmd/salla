<?php

namespace App\Filament\Resources\UserSubscriptionResource\Pages;

use App\Filament\Resources\UserSubscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListUserSubscriptions extends ListRecords
{
    protected static string $resource = UserSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('إنشاء اشتراك جديد'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('الكل'),
            'active' => Tab::make('النشطة')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'active'))
                ->badge(fn () => $this->getModel()::where('status', 'active')->count()),
            'suspended' => Tab::make('موقّفة مؤقت')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'suspended'))
                ->badge(fn () => $this->getModel()::where('status', 'suspended')->count()),
            'cancelled' => Tab::make('ملغاة')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'cancelled'))
                ->badge(fn () => $this->getModel()::where('status', 'cancelled')->count()),
            'expiring_soon' => Tab::make('تنتهي قريباً')
                ->modifyQueryUsing(fn (Builder $query) => $query->expiringSoon())
                ->badge(fn () => $this->getModel()::expiringSoon()->count()),
        ];
    }
}