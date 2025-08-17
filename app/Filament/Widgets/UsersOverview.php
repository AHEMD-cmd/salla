<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class UsersOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('عدد المستخدمين', User::where('type', 'user')->orWhereNull('type')->count())
                ->description('جميع المستخدمين')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),
            
            Stat::make('عدد المستخدمين الجدد خلال 30 يوما', User::where('created_at', '>=', now()->subDays(30))->where('type','user')->count())
                ->description('مستخدمون انضموا خلال الشهر الماضي')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('success'),
            
                Stat::make('عدد الطلبات', Order::count())
                ->description('جميع الطلبات الكامله')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('success'),
        ];
    }
}
