<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserSubscriptionResource\Pages;
use App\Models\UserSubscription;
use App\Models\User;
use App\Models\Subscription;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Filament\Forms\Get;
use Filament\Forms\Set;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;

class UserSubscriptionResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = UserSubscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'الاشتراكات';

    protected static ?string $modelLabel = 'اشتراك';

    protected static ?string $pluralModelLabel = 'الاشتراكات';

    // public static function canViewAny(): bool
    // {
    //     $user = User::where('email', 'admin@admin.com')->first();
    //     if ($user) {
    //         return false;
    //     }

    //     return auth()->user()->type == 'super-admin';
    // }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات العميل')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('العميل')
                            ->relationship(
                                'user',
                                'name',
                                fn($query) => $query
                                    ->where(function ($query) {
                                        $query->where('creator_id', auth()->id())
                                            ->orWhere('creator_id', auth()->user()->parent_id);
                                    })
                                    ->where('id', '!=', auth()->id()) 
                            )
                            ->searchable(['name', 'phone', 'email'])

                            ->preload()
                            ->required()
                            ->getOptionLabelFromRecordUsing(
                                fn(User $record): string =>
                                "{$record->name} - {$record->phone} - {$record->email}"
                            )
                            ->validationMessages([
                                'required' => 'العميل مطلوب',
                            ])
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('الاسم')
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('phone')
                                    ->label('رقم الجوال')
                                    ->required()
                                    ->unique(User::class, 'phone', ignoreRecord: true)
                                    ->regex('/^[0-9+\-\s()]+$/')
                                    ->maxLength(20),

                                Forms\Components\TextInput::make('email')
                                    ->label('البريد الإلكتروني')
                                    ->email()
                                    ->required()
                                    ->unique(User::class, 'email', ignoreRecord: true)
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('password')
                                    ->label('كلمة المرور')
                                    ->password()
                                    ->required()
                                    ->minLength(8),
                            ])
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('تفاصيل الاشتراك')
                    ->schema([
                        Forms\Components\Select::make('subscription_id')
                            ->label('الباقة')
                            ->relationship('subscription', 'name', fn($query) => $query->where(function ($query) {
                                $query->where('is_active', true);
                            }))
                            ->required()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                if ($state && $get('start_date')) {
                                    $subscription = Subscription::find($state);
                                    if ($subscription) {
                                        $endDate = Carbon::parse($get('start_date'))
                                            ->addDays($subscription->duration)
                                            ->format('Y-m-d');
                                        $set('end_date', $endDate);
                                    }
                                }
                            }),

                        Forms\Components\DatePicker::make('start_date')
                            ->label('تاريخ البدء')
                            ->required()
                            ->default(now())
                            ->reactive()
                            ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                if ($state && $get('subscription_id')) {
                                    $subscription = Subscription::find($get('subscription_id'));
                                    if ($subscription) {
                                        $endDate = Carbon::parse($state)
                                            ->addDays($subscription->duration)
                                            ->format('Y-m-d');
                                        $set('end_date', $endDate);
                                    }
                                }
                            }),

                        Forms\Components\DatePicker::make('end_date')
                            ->label('تاريخ الانتهاء')
                            ->required()
                            ->disabled()
                            ->dehydrated(),
                    ])->columns(3),

                Forms\Components\Section::make('إعدادات إضافية')
                    ->schema([
                        Forms\Components\Toggle::make('auto_renewal')
                            ->label('تجديد تلقائي')
                            ->default(false),

                        Forms\Components\Select::make('status')
                            ->label('الحالة')
                            ->required()
                            ->options([
                                'active' => 'نشط',
                                'suspended' => 'موقّف مؤقت',
                                'cancelled' => 'ملغي',
                            ])
                            ->default('active'),

                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->label('رقم الطلب')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.phone')
                    ->label('الجوال')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('user.email')
                    ->label('الإيميل')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('subscription.name')
                    ->label('اسم الباقة')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('subscription.duration')
                    ->label('مدة الاشتراك')
                    ->suffix(' يوم')
                    ->sortable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('تاريخ البدء')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('تاريخ الانتهاء')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('remaining_days')
                    ->label('الأيام المتبقية')
                    ->getStateUsing(
                        fn(UserSubscription $record): string =>
                        $record->remaining_days > 0 ? $record->remaining_days . ' يوم' : 'منتهي'
                    )
                    ->color(
                        fn(UserSubscription $record): string =>
                        match (true) {
                            $record->remaining_days <= 0 => 'danger',
                            $record->remaining_days <= 7 => 'warning',
                            default => 'success'
                        }
                    ),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->getStateUsing(fn(UserSubscription $record): string => $record->status_label)
                    ->color(fn(UserSubscription $record): string => $record->status_color),

                Tables\Columns\IconColumn::make('auto_renewal')
                    ->label('تجديد تلقائي')
                    ->boolean()
                    ->trueIcon('heroicon-o-arrow-path')
                    ->falseIcon('heroicon-o-x-mark'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'active' => 'نشط',
                        'suspended' => 'موقّف مؤقت',
                        'cancelled' => 'ملغي',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('subscription_id')
                    ->label('الباقة')
                    ->relationship('subscription', 'name')
                    ->multiple()
                    ->preload(),

                Tables\Filters\Filter::make('expired')
                    ->label('المنتهية الصلاحية')
                    ->query(fn(Builder $query): Builder => $query->expired()),

                Tables\Filters\Filter::make('expiring_soon')
                    ->label('تنتهي قريباً (7 أيام)')
                    ->query(fn(Builder $query): Builder => $query->expiringSoon()),

                Tables\Filters\TernaryFilter::make('auto_renewal')
                    ->label('تجديد تلقائي')
                    ->trueLabel('مع التجديد')
                    ->falseLabel('بدون تجديد')
                    ->native(false),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('من تاريخ'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('إلى تاريخ'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('عرض'),

                    Tables\Actions\EditAction::make()
                        ->label('تعديل'),

                    Tables\Actions\Action::make('activate')
                        ->label('تفعيل')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn(UserSubscription $record): bool => $record->status !== 'active' && auth()->user()->can('تغيير الحالة_user::subscription'))
                        ->requiresConfirmation()
                        ->action(fn(UserSubscription $record) => $record->update(['status' => 'active'])),

                    Tables\Actions\Action::make('suspend')
                        ->label('إيقاف مؤقت')
                        ->icon('heroicon-o-pause')
                        ->color('warning')
                        ->visible(fn(UserSubscription $record): bool => $record->status === 'active' && auth()->user()->can('تغيير الحالة_user::subscription'))
                        ->requiresConfirmation()
                        ->action(fn(UserSubscription $record) => $record->update(['status' => 'suspended'])),

                    Tables\Actions\Action::make('cancel')
                        ->label('إلغاء')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn(UserSubscription $record): bool => $record->status !== 'cancelled' && auth()->user()->can('تغيير الحالة_user::subscription'))
                        ->requiresConfirmation()
                        ->action(fn(UserSubscription $record) => $record->update(['status' => 'cancelled'])),

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
                        ->action(fn($records) => $records->each->update(['status' => 'active'])),

                    Tables\Actions\BulkAction::make('suspend')
                        ->label('إيقاف المحدد مؤقتاً')
                        ->icon('heroicon-o-pause')
                        ->color('warning')
                        ->action(fn($records) => $records->each->update(['status' => 'suspended'])),

                    Tables\Actions\BulkAction::make('cancel')
                        ->label('إلغاء المحدد')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn($records) => $records->each->update(['status' => 'cancelled'])),
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
            'index' => Pages\ListUserSubscriptions::route('/'),
            'create' => Pages\CreateUserSubscription::route('/create'),
            'view' => Pages\ViewUserSubscription::route('/{record}'),
            'edit' => Pages\EditUserSubscription::route('/{record}/edit'),
        ];
    }




    // public static function getNavigationBadge(): ?string
    // {
    //     return static::getModel()::where('status', 'active')->count();
    // }

    public static function getPermissionPrefixes(): array
    {
        return [
            'view',
            'view_any',
            'create',
            'update',
            'delete',
            'status' => 'تغيير الحالة',
        ];
    }
}
