<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use App\Models\Domain;
use App\Models\Setting;
use Filament\Forms\Get;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Http;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\DomainResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\DomainResource\RelationManagers;

class DomainResource extends Resource
{
    protected static ?string $model = Domain::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getNavigationLabel(): string
    {
        return __('domains');
    }

    public static function getPluralLabel(): string
    {
        return __('domains');
    }

    public static function getLabel(): string
    {
        return __('domain');
    }

    public static function canViewAny(): bool
    {
        return false; // we no longer allow to view domains

        $user = User::where('email', 'admin@admin.com')->first();
        if ($user) {
            return false;
        }
        
        $host = request()->getHost();

        // If host is one of the predefined ones
        if (
            (auth()->user()->type === 'admin' && $host === 'salla.cupun.net') ||
            $host === 'localhost' ||
            $host === '127.0.0.1'
        ) {
            return true;
        }

        // Otherwise, check via remote API
        $settings = Setting::where('key', 'is_inversor')->where('value', '1')->first();

        if (!$settings) {
            return false;
        }

        return true;
    }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('الدومين')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('license_key')
                    ->label(__('license_key'))
                    ->placeholder('يتم انشاءه تلقائيا')
                    ->disabled()
                    ->maxLength(255),
                Forms\Components\Select::make('type')
                    ->label(__('type'))
                    ->options([
                        'inversor' => __('inversor'),
                        'client' => __('client'),
                    ])
                    ->required()
                    ->disabled(fn (Get $get, $record) => $record !== null) // ✅ disabled only in edit
                    ->visible(function (Get $get, $record) {
                        // Hide password field for clients
                        if (request()->getHost() == 'salla.cupun.net') {
                            return true;
                        }

                        return false;
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('الدومين'),
                Tables\Columns\TextColumn::make('license_key')
                    ->label(__('license_key')),
                Tables\Columns\TextColumn::make('type')
                    ->label(__('type'))
                    ->formatStateUsing(fn(string $state): string => __($state)),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageDomains::route('/'),
        ];
    }
}
