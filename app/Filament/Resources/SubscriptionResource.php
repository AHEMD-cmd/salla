<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\Subscription;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\SubscriptionResource\Pages;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;

class SubscriptionResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Subscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'الباقات';

    protected static ?string $modelLabel = 'باقة';

    protected static ?string $pluralModelLabel = 'الباقات';

    // public static function canViewAny(): bool
    // {
    //     $user = User::where('email', 'admin@admin.com')->first();
    //     if ($user) {
    //         return false;
    //     }

    //     return auth()->user()->type == 'super-admin';
    // }

    // public static function shouldRegisterNavigation(): bool
    // {
    //     return auth()->user()->can('view_any_subscription');
    // }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الباقة الأساسية')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('اسم الباقة')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('desc')
                            ->label('الوصف')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('تفاصيل الباقة')
                    ->schema([
                        Forms\Components\TextInput::make('duration')
                            ->label('المدة (بالأيام)')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->suffix('يوم'),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('unlimited_files')
                                    ->label('ملفات غير محدودة')
                                    ->reactive()
                                    ->afterStateUpdated(
                                        fn($state, callable $set) =>
                                        $state ? $set('number_of_files', null) : null
                                    ),

                                Forms\Components\TextInput::make('number_of_files')
                                    ->label('عدد الملفات')
                                    ->numeric()
                                    ->minValue(1)
                                    ->hidden(fn(callable $get) => $get('unlimited_files'))
                                    ->required(fn(callable $get) => !$get('unlimited_files')),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('unlimited_downloads')
                                    ->label('تنزيلات غير محدودة')
                                    ->reactive()
                                    ->afterStateUpdated(
                                        fn($state, callable $set) =>
                                        $state ? $set('number_of_downloads', null) : null
                                    ),

                                Forms\Components\TextInput::make('number_of_downloads')
                                    ->label('عدد التنزيلات')
                                    ->numeric()
                                    ->minValue(1)
                                    ->hidden(fn(callable $get) => $get('unlimited_downloads'))
                                    ->required(fn(callable $get) => !$get('unlimited_downloads')),
                            ]),
                    ])->columns(1),

                Forms\Components\Section::make('التكلفة والإعدادات')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('price')
                                    ->label('السعر')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(0.01),

                                Forms\Components\Select::make('currency')
                                    ->label('العملة')
                                    ->required()
                                    ->options([
                                        'USD' => 'دولار أمريكي (USD)',
                                        'SAR' => 'ريال سعودي (SAR)',
                                        'AED' => 'درهم إماراتي (AED)',
                                        'EGP' => 'جنيه مصري (EGP)',
                                        'EUR' => 'يورو (EUR)',
                                    ])
                                    ->default('USD'),
                            ]),

                        Forms\Components\Toggle::make('telegram_status')
                            ->label('تفعيل نظام تيليغرام')
                            ->default(false),

                        Forms\Components\Toggle::make('is_active')
                            ->label('الحالة (مفعّلة)')
                            ->default(true),
                    ])->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم الباقة')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('duration')
                    ->label('المدة')
                    ->suffix(' يوم')
                    ->sortable(),

                Tables\Columns\TextColumn::make('files_display')
                    ->label('عدد الملفات')
                    ->getStateUsing(
                        fn(Subscription $record): string =>
                        $record->unlimited_files ? 'غير محدود' : number_format($record->number_of_files ?? 0)
                    ),

                Tables\Columns\TextColumn::make('downloads_display')
                    ->label('عدد التنزيلات')
                    ->getStateUsing(
                        fn(Subscription $record): string =>
                        $record->unlimited_downloads ? 'غير محدود' : number_format($record->number_of_downloads ?? 0)
                    ),

                Tables\Columns\IconColumn::make('telegram_status')
                    ->label('تيليغرام')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('price_display')
                    ->label('السعر')
                    ->getStateUsing(
                        fn(Subscription $record): string =>
                        number_format($record->price, 2) . ' ' . $record->currency
                    )
                    ->sortable(['price']),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->visible(fn ($record) => auth()->user()->can('تفعيل_subscription'))
                    ->label('مفعّلة')
                    ->onColor('success')
                    ->offColor('danger'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('الحالة')
                    ->trueLabel('المفعّلة فقط')
                    ->falseLabel('المعطّلة فقط')
                    ->native(false),

                Tables\Filters\TernaryFilter::make('telegram_status')
                    ->label('تيليغرام')
                    ->trueLabel('مع تيليغرام')
                    ->falseLabel('بدون تيليغرام')
                    ->native(false),

                Tables\Filters\TernaryFilter::make('unlimited_files')
                    ->label('الملفات')
                    ->trueLabel('غير محدودة')
                    ->falseLabel('محدودة')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('عرض'),
                    Tables\Actions\EditAction::make()
                        ->label('تعديل'),
                    Tables\Actions\Action::make('toggle_status')
                        ->visible(fn ($record) => auth()->user()->can('تفعيل_subscription'))
                        ->label(fn(Subscription $record): string => $record->is_active ? 'إيقاف' : 'تفعيل')
                        ->icon(fn(Subscription $record): string => $record->is_active ? 'heroicon-o-pause' : 'heroicon-o-play')
                        ->color(fn(Subscription $record): string => $record->is_active ? 'warning' : 'success')
                        ->requiresConfirmation()
                        ->action(fn(Subscription $record) => $record->update(['is_active' => !$record->is_active])),
                    Tables\Actions\DeleteAction::make()
                        ->label('حذف'),
                ])
                    ->label('الإجراءات')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->color('gray')
                    ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('حذف المحدد'),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('تفعيل المحدد')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn($records) => $records->each->update(['is_active' => true])),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('إيقاف المحدد')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->action(fn($records) => $records->each->update(['is_active' => false])),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscriptions::route('/'),
            'create' => Pages\CreateSubscription::route('/create'),
            'view' => Pages\ViewSubscription::route('/{record}'),
            'edit' => Pages\EditSubscription::route('/{record}/edit'),
        ];
    }

    public static function getPermissionPrefixes(): array
    {
        return [
            'view',
            'view_any',
            'create',
            'update',
            'delete',
            // 'delete_any',
            'تفعيل_subscription' => 'تفعيل',
        ];
    }
}
