<?php

namespace App\Filament\Resources\UserSubscriptionResource\Pages;

use App\Filament\Resources\UserSubscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;

class ViewUserSubscription extends ViewRecord
{
    protected static string $resource = UserSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('معلومات الطلب')
                    ->schema([
                        Components\TextEntry::make('order_number')
                            ->label('رقم الطلب')
                            ->copyable(),
                        Components\TextEntry::make('status')
                            ->label('الحالة')
                            ->getStateUsing(fn ($record): string => $record->status_label)
                            ->badge()
                            ->color(fn ($record): string => $record->status_color),
                        Components\TextEntry::make('remaining_days')
                            ->label('الأيام المتبقية')
                            ->getStateUsing(fn ($record): string => 
                                $record->remaining_days > 0 ? $record->remaining_days . ' يوم' : 'منتهي'
                            )
                            ->badge()
                            ->color(fn ($record): string => 
                                match(true) {
                                    $record->remaining_days <= 0 => 'danger',
                                    $record->remaining_days <= 7 => 'warning',
                                    default => 'success'
                                }
                            ),
                    ])->columns(3),

                Components\Section::make('معلومات العميل')
                    ->schema([
                        Components\TextEntry::make('user.name')
                            ->label('الاسم'),
                        Components\TextEntry::make('user.phone')
                            ->label('رقم الجوال')
                            ->copyable(),
                        Components\TextEntry::make('user.email')
                            ->label('البريد الإلكتروني')
                            ->copyable(),
                    ])->columns(3),

                Components\Section::make('تفاصيل الباقة')
                    ->schema([
                        Components\TextEntry::make('subscription.name')
                            ->label('اسم الباقة'),
                        Components\TextEntry::make('subscription.duration')
                            ->label('مدة الباقة')
                            ->suffix(' يوم'),
                        Components\TextEntry::make('subscription.price_display')
                            ->label('سعر الباقة')
                            ->getStateUsing(fn ($record): string => 
                                number_format($record->subscription->price, 2) . ' ' . $record->subscription->currency
                            ),
                    ])->columns(3),

                Components\Section::make('تواريخ الاشتراك')
                    ->schema([
                        Components\TextEntry::make('start_date')
                            ->label('تاريخ البدء')
                            ->date('d/m/Y'),
                        Components\TextEntry::make('end_date')
                            ->label('تاريخ الانتهاء')
                            ->date('d/m/Y'),
                        Components\IconEntry::make('auto_renewal')
                            ->label('تجديد تلقائي')
                            ->boolean()
                            ->trueIcon('heroicon-o-arrow-path')
                            ->falseIcon('heroicon-o-x-mark')
                            ->trueColor('success')
                            ->falseColor('gray'),
                    ])->columns(3),

                Components\Section::make('معلومات إضافية')
                    ->schema([
                        Components\TextEntry::make('notes')
                            ->label('ملاحظات')
                            ->columnSpanFull()
                            ->placeholder('لا توجد ملاحظات'),
                        Components\TextEntry::make('created_at')
                            ->label('تاريخ الإنشاء')
                            ->dateTime('d/m/Y H:i'),
                        Components\TextEntry::make('updated_at')
                            ->label('آخر تحديث')
                            ->dateTime('d/m/Y H:i'),
                    ])->columns(2),
            ]);
    }
}