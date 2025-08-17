<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\SallaSetting;
use Filament\Resources\Resource;
use App\Filament\Resources\SallaSettingResource\Pages;

class SallaSettingResource extends Resource
{
    protected static ?string $model = SallaSetting::class;
    protected static ?string $navigationIcon = 'heroicon-o-cog';

    public static function getNavigationLabel(): string
    {
        return __('salla_settings');
    }

    public static function getPluralLabel(): string
    {
        return __('salla_settings');
    }

    public static function getLabel(): string
    {
        return __('salla_settings');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('client_id')
                    ->label('Client ID')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('client_secret')
                    ->label('Client Secret')
                    ->password()
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('webhook_token')
                    ->label('Webhook Token')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('client_id')->label('Client ID'),
                Tables\Columns\TextColumn::make('client_secret')
                    ->label('Client Secret')
                    ->formatStateUsing(fn($state) => str_repeat('*', 8)), // Mask secret
                Tables\Columns\TextColumn::make('webhook_token')
                    ->label('Webhook Token')
                    ->formatStateUsing(fn($state) => str_repeat('*', 8)), // Mask secret
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSallaSettings::route('/'),
            // 'edit' => Pages\EditSallaSetting::route('/{record}/edit'),
        ];
    }

    // Restrict access to admins
    public static function canViewAny(): bool
    {
        $user = User::where('email', 'admin@admin.com')->first();
        if ($user) {
            return false;
        }
        
        return auth()->user()->type == 'admin';
    }

    public static function canCreate(): bool
    {
        return auth()->user()->type == 'admin' && SallaSetting::count() === 0;
    }
}