<?php

namespace App\Filament\Resources;

use App\Models\User;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Get;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Spatie\Permission\Models\Role;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\UserResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Validation\Rule;


class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getNavigationLabel(): string
    {
        return __('users');
    }

    public static function getPluralLabel(): string
    {
        return __('users');
    }

    public static function getLabel(): string
    {
        return __('user');
    }

    public static function canViewAny(): bool
    {
        $user = User::where('email', 'admin@admin.com')->first();
        if ($user) {
            return false;
        }
        
        return auth()->user()->type == 'admin' || auth()->user()->type == 'user';
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // If authenticated user is of type 'user', only show clients
        if (auth()->user()->type === 'user') {
            $query->where('type', 'client');
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('type')
                    ->label('الدور')
                    ->options(function (Get $get) {
                        if (auth()->user()->type == 'user') {
                            return [
                                'client' => 'عميل',
                            ];
                        }

                        return [
                            'admin' => 'مدير',
                            'client' => 'عميل',
                            'user' => 'موظف',
                        ];
                    })
                    ->reactive()
                    ->required(),

                Forms\Components\TextInput::make('name')
                    ->label(__('name'))
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('phone')
                    ->label(__('phone'))
                    ->required()
                    ->maxLength(255)
                    ->rules(function ($record) {
                        return [
                            Rule::unique('users', 'phone')->ignore($record?->id),
                        ];
                    })
                    ->regex('/^[\d+]+$/')
                    ->validationMessages([
                        'regex' => 'رقم الهاتف يجب أن يحتوي على أرقام إنجليزية فقط',
                        'unique' => 'رقم الهاتف مستخدم من قبل.',
                    ]),

                Forms\Components\TextInput::make('email')
                    ->email()
                    ->label(__('email'))
                    ->required(fn(Get $get) => $get('type') !== 'client')
                    ->maxLength(255)
                    ->rules(function ($record) {
                        return [
                            Rule::unique('users', 'email')->ignore($record?->id),
                        ];
                    })
                    ->validationMessages([
                        'unique' => 'البريد الإلكتروني مستخدم من قبل.',
                    ]),

                Forms\Components\TextInput::make('password')
                    ->label(__('password'))
                    ->required(fn(Get $get) => $get('type') !== 'client')
                    ->password()
                    ->minLength(8)
                    ->maxLength(255)
                    ->helperText('يجب أن تحتوي على حرف صغير وحرف كبير ورقم ورمز خاص واحد على الأقل')
                    ->regex('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]+$/')
                    ->validationMessages([
                        'regex' => 'كلمة المرور يجب أن تحتوي على حرف صغير وحرف كبير ورقم ورمز خاص واحد على الأقل',
                        'min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل'
                    ])
                    ->rules(['regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]+$/'])
                    ->dehydrated()
                    ->visible(function (Get $get, $record) {
                        // Hide password field for clients
                        if ($get('type') === 'client') {
                            return false;
                        }

                        // Hide password field for users
                        if ($get('type') === 'user') {
                            return true;
                        }

                        // If current user is admin and editing another admin, hide password field
                        if (auth()->user()->type === 'admin' && $record && $record->type === 'admin') {
                            return false;
                        }

                        return true;
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('name'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('email')
                    ->label(__('email'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('النوع')
                    ->options([
                        'admin' => 'مدير',
                        'user' => 'موظف',
                        'client' => 'عميل',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListUsers::route('/'),
            // 'create' => Pages\CreateUser::route('/create'),
            // 'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
