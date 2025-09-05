<?php

namespace App\Filament\Resources\SubscriptionResource\Pages;

use App\Filament\Resources\SubscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;

class ViewSubscription extends ViewRecord
{
    protected static string $resource = SubscriptionResource::class;

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
                Components\Section::make('معلومات الباقة')
                    ->schema([
                        Components\TextEntry::make('name')
                            ->label('اسم الباقة'),
                        Components\TextEntry::make('desc')
                            ->label('الوصف')
                            ->columnSpanFull(),
                    ])->columns(2),

                Components\Section::make('تفاصيل الباقة')
                    ->schema([
                        Components\TextEntry::make('duration')
                            ->label('المدة')
                            ->suffix(' يوم'),
                        Components\TextEntry::make('files_display')
                            ->label('عدد الملفات')
                            ->getStateUsing(fn ($record): string => 
                                $record->unlimited_files ? 'غير محدود' : number_format($record->number_of_files ?? 0)
                            ),
                        Components\TextEntry::make('downloads_display')
                            ->label('عدد التنزيلات')
                            ->getStateUsing(fn ($record): string => 
                                $record->unlimited_downloads ? 'غير محدود' : number_format($record->number_of_downloads ?? 0)
                            ),
                    ])->columns(3),

                Components\Section::make('الإعدادات والتكلفة')
                    ->schema([
                        Components\TextEntry::make('price_display')
                            ->label('السعر')
                            ->getStateUsing(fn ($record): string => 
                                number_format($record->price, 2) . ' ' . $record->currency
                            ),
                        Components\IconEntry::make('telegram_status')
                            ->label('تيليغرام')
                            ->boolean()
                            ->trueIcon('heroicon-o-check-circle')
                            ->falseIcon('heroicon-o-x-circle')
                            ->trueColor('success')
                            ->falseColor('danger'),
                        Components\IconEntry::make('is_active')
                            ->label('الحالة')
                            ->boolean()
                            ->trueIcon('heroicon-o-check-circle')
                            ->falseIcon('heroicon-o-x-circle')
                            ->trueColor('success')
                            ->falseColor('danger'),
                    ])->columns(3),

                Components\Section::make('معلومات إضافية')
                    ->schema([
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
