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
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;


class UserResource extends Resource implements HasShieldPermissions
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

    // public static function canViewAny(): bool
    // {
    //     $user = User::where('email', 'admin@admin.com')->first();
    //     if ($user) {
    //         return false;
    //     }

    //     return auth()->user()->type == 'super-admin' || auth()->user()->type == 'admin';
    // }


    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where(function ($query) {
                $query->where('creator_id', auth()->id())
                    ->orWhere('creator_id', auth()->user()->parent_id);
            });
    }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Select::make('type')
                //     ->label('الدور')
                //     ->options(function (Get $get) {
                //         if (auth()->user()->type == 'user') {
                //             return [
                //                 'client' => 'عميل',
                //             ];
                //         }

                //         return [
                //             'admin' => 'مدير',
                //             'client' => 'عميل',
                //             'user' => 'موظف',
                //         ];
                //     })
                //     ->reactive()
                //     ->required(),
                Forms\Components\Select::make('roles')
                    ->relationship('roles', 'name', fn($query) => $query->where(function ($query) {
                        $query->where('creator_id', auth()->id())
                            ->orWhere('creator_id', auth()->user()->parent_id);
                    }))
                    // ->multiple()    
                    ->preload()
                    ->required()
                    ->validationMessages([
                        'required' => 'الدورات يجب أن تكون محددة'
                    ]),


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

                // Forms\Components\TextInput::make('password')
                //     ->label(__('password'))
                //     ->required(fn(Get $get) => $get('type') !== 'client')
                //     ->password()
                //     ->minLength(8)
                //     ->maxLength(255)
                //     ->helperText('يجب أن تحتوي على حرف صغير وحرف كبير ورقم ورمز خاص واحد على الأقل')
                //     ->regex('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]+$/')
                //     ->validationMessages([
                //         'regex' => 'كلمة المرور يجب أن تحتوي على حرف صغير وحرف كبير ورقم ورمز خاص واحد على الأقل',
                //         'min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل'
                //     ])
                //     ->rules(['regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]+$/'])
                //     ->dehydrated()
                //     ->visible(function (Get $get, $record) {
                //         // Hide password field for clients
                //         if ($get('type') === 'client') {
                //             return false;
                //         }

                //         // Hide password field for users
                //         if ($get('type') === 'user') {
                //             return true;
                //         }

                //         // If current user is admin and editing another admin, hide password field
                //         if (auth()->user()->type === 'admin' && $record && $record->type === 'admin') {
                //             return false;
                //         }

                //         return true;
                //     }),
                Forms\Components\TextInput::make('password')
                    ->label(__('password'))
                    ->required()
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
                    ->visible(fn(string $operation = null) => $operation === 'create'),

                Forms\Components\Checkbox::make('is_subscriber')
                    ->label('هل هو مشترك')
                    ->default(false)
                    ->visible(fn(string $operation = null) => auth()->user()->hasRole('super_admin'))

                    /**
                     * This checkbox is reactive, meaning its value will update dynamically
                     * when other form components or data changes. In this case, when the 'is_subscriber'
                     * field changes, this checkbox will reflect the new value.
                     * 
                     * To make a form component reactive, you use the `reactive()` method. This method
                     * should be called on the component itself, and it returns the component instance.
                     * 
                     * In our case, we're calling `reactive()` on the checkbox component, which means
                     * that the checkbox's value will be updated when the `is_subscriber` field changes.
                     */
                    ->reactive(),
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
                Tables\Filters\SelectFilter::make('roles')
                    ->relationship('roles', 'name')
                    ->label('الادوار')
                    ->multiple()
                    ->preload(),
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

    public static function getPermissionPrefixes(): array
    {
        return [
            'view',
            'view_any',
            'create',
            'update',
            'delete',
        ];
    }
}
