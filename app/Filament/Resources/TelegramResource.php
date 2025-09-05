<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\TelegramSetting;
use Filament\Resources\Resource;
use Filament\Forms\Components\Textarea;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\TelegramResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\TelegramResource\RelationManagers;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;

class TelegramResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = TelegramSetting::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getNavigationLabel(): string
    {
        return __('Telegram Settings'); // Translated navigation label
    }

    public static function getPluralLabel(): string
    {
        return __('Telegram Settings');
    }

    public static function getLabel(): string
    {
        return __('Telegram Settings');
    }



    // public static function canViewAny(): bool
    // {
    //     $user = User::where('email', 'admin@admin.com')->first();
    //     if ($user) {
    //         return false;
    //     }

    //     return auth()->user()->type == 'super-admin' || auth()->user()->type == 'admin';
    // }

    public static function canCreate(): bool
    {
        return (TelegramSetting::where('creator_id', auth()->user()->parent_id)->count() == 0 || TelegramSetting::where('creator_id', auth()->id())->count() == 0) && auth()->user()->hasPermissionTo('ربط ب تيليغرام_telegram') ;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Textarea::make('welcome_message')
                    ->required()
                    ->label(__('Telegram Welcome Message')) // Translated label
                    ->helperText(__('Set the welcome message for Telegram users')),
                Textarea::make('order_message')
                    ->required()
                    ->label(__('Telegram Order Message')) // Translated label
                    ->helperText(__('Set the order message for Telegram users')),
                Textarea::make('order_not_found_message')
                    ->required()
                    ->label(__('Telegram Order Not Found Message')) // Translated label
                    ->helperText(__('Set the order not found message for Telegram users')),
                Textarea::make('telegram_order_already_delivered_message')
                    ->required()
                    ->label(__('Telegram Order Already Delivered Message'))
                    ->helperText(__('Set the order already delivered message for Telegram users')),
                Textarea::make('webhook_token')
                    ->required()
                    ->label(__('webhook_token'))
                    ->helperText(__('Set the webhook token for Telegram bot')),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('creator_id', auth()->id());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('welcome_message')
                    ->label(__('welcome_message'))
                    ->limit(30)
                    ->wrap(),

                Tables\Columns\TextColumn::make('order_message')
                    ->label(__('order_message'))
                    ->limit(30)
                    ->wrap(),

                Tables\Columns\TextColumn::make('order_not_found_message')
                    ->label(__('order_not_found_message'))
                    ->limit(30)
                    ->wrap(),

                Tables\Columns\TextColumn::make('telegram_order_already_delivered_message')
                    ->label(__('already_delivered_message'))
                    ->limit(30)
                    ->wrap(),

                Tables\Columns\TextColumn::make('webhook_token')
                    ->label(__('webhook_token'))
                    ->limit(30)
                    ->wrap(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('created_at'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->dateTime(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('updated_at'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->dateTime(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label(__('edit')),
                Tables\Actions\DeleteAction::make()
                    ->label(__('delete')),


            ])
            ->defaultSort('id', 'desc');
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
            'index' => Pages\ListTelegrams::route('/'),
            // 'create' => Pages\CreateTelegram::route('/create'),
            // 'edit' => Pages\EditTelegram::route('/{record}/edit'),
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
            'connect_with_telegram' => 'ربط ب تيليغرام',
        ];
    }
}
