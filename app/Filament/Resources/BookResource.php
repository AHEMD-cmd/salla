<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\Book;
use App\Models\User;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\BookResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\BookResource\RelationManagers;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;

class BookResource extends Resource implements HasShieldPermissions
{

    protected static ?string $model = Book::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getNavigationLabel(): string
    {
        return __('books');
    }

    public static function getPluralLabel(): string
    {
        return __('books');
    }

    public static function getLabel(): string
    {
        return __('book');
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
                Forms\Components\TextInput::make('name')
                    ->label(__('name'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\FileUpload::make('path')
                    ->label(__('path'))
                    ->disk('public')
                    ->directory('pdf') // مكان الحفظ داخل storage/app/public/pdfs
                    ->acceptedFileTypes(['application/pdf']) // يقبل بس PDF
                    ->maxSize(10240) // الحجم الأقصى بالـ KB (هنا 10MB)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->sortable()
                    ->searchable()
                    ->label(__('name')),
                Tables\Columns\TextColumn::make('path')
                    ->label(__('path'))
                    ->url(fn($record) => Storage::url($record->path))
                    ->openUrlInNewTab(),
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
            'index' => Pages\ManageBooks::route('/'),
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
        ];
    }
}
