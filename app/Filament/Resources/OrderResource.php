<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\Book;
use App\Models\User;
use Filament\Tables;
use App\Models\Order;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Storage;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\ToggleColumn;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\TextInputColumn;
use App\Filament\Resources\OrderResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\OrderResource\RelationManagers;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getNavigationLabel(): string
    {
        return __('orders');
    }

    public static function getPluralLabel(): string
    {
        return __('orders');
    }

    public static function getLabel(): string
    {
        return __('order');
    }
    public static function canCreate(): bool
    {
        return true; // This will disable the "Create" button
    }

    public static function canViewAny(): bool
    {
        $user = User::where('email', 'admin@admin.com')->first();
        if ($user) {
            return false;
        }
        return auth()->user()->type == 'admin' || auth()->user()->type == 'user';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('order_number')
                    ->label('رقم الطلب')
                    // ->required()
                    // ->regex('/^[a-zA-Z0-9\-_\s]+$/')  // Added \s to allow spaces
                    ->validationMessages([
                        'regex' => 'رقم الطلب يجب أن يحتوي على أحرف إنجليزية وأرقام ومسافات فقط'
                    ])
                    ->disabled()
                    ->helperText('يتم انشاؤه تلقائيا'),
                    // ->helperText('يُسمح بالأحرف الإنجليزية والأرقام والشرطات والمسافات فقط')

                // TextInput::make('status')
                //     ->label('الحالة')
                //     ->disabled()
                //     ->default('تم التنفيذ')
                //     ->required(),
                Select::make('user_id') 
                    ->relationship('user', 'name')
                    ->searchable()
                    ->label(__('user'))
                    ->required(),
                Select::make('pdf_path')
                    ->label(__('path'))
                    ->options(
                        Book::all()->pluck('name', 'path')
                    )
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('order_number')
                    ->sortable()
                    ->searchable()
                    ->label('رقم الطلب'),
                TextColumn::make('serial_number')
                    ->sortable()
                    ->searchable()
                    ->label('الرقم التسلسلي'),

                TextColumn::make('user.name')  // Displaying user name associated with the order
                    ->sortable()
                    ->searchable()
                    ->label(__('user')),

                TextColumn::make('user.phone')  // Displaying user name associated with the order
                    ->sortable()
                    ->searchable()
                    ->label(__('phone')),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->label(__('created_at')),
                ToggleColumn::make('telegram_delivery_status')

                    ->label('حاله الوصول الي تلجرام'),

                // TextInputColumn::make(Storage::url('pdf_path'))
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('download')
                    ->label('انشاء PDF')
                    ->url(
                        fn(Order $record): string => route('create_pdf', $record->id),
                        shouldOpenInNewTab: true
                    ),

                // Tables\Actions\Action::make('preview')
                //     ->label('معاينة PDF')
                //     ->icon('heroicon-o-eye')
                //     ->color('primary')
                //     ->url(fn(Order $record) => route('preview_pdf', $record), true),
                Tables\Actions\Action::make('preview')
                    ->label('تحميل PDF')
                    ->color('primary')
                    ->url(fn(Order $record) => route('send_pdf', $record), true),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListOrders::route('/'),
            // 'create' => Pages\CreateOrder::route('/create'),
            // 'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
