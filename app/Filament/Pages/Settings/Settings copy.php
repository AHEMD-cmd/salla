<?php

namespace App\Filament\Pages\Settings;

use Closure;
use App\Models\User;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\ColorPicker;
use Outerweb\FilamentSettings\Filament\Pages\Settings as BaseSettings;

class Settings extends BaseSettings
{
    public static function shouldRegisterNavigation(): bool
    {
        $user = User::where('email', 'admin@admin.com')->first();
        if ($user) {
            return false;
        }
        return auth()->user()->type == 'admin' || auth()->user()->type == 'user';
    }

    public function schema(): array|Closure
    {
        return [
            Tabs::make('Settings')
                ->schema([
                    Tabs\Tab::make('اعدادات عامه')
                        ->schema([
                            // Color Picker for watermark text (with Arabic translation)
                            ColorPicker::make('pdf_color')
                                ->label(__('Watermark Color')) // Translated label
                                ->required()
                                ->rgb()
                                ->default('#C8C8C8') // Default color (light gray)
                                ->helperText(__('Select color for watermark text')),

                            // Opacity for watermark text (with Arabic translation)
                            TextInput::make('pdf_opacity')
                                ->label(__('Watermark Opacity')) // Translated label
                                ->numeric()
                                ->required()
                                ->default(0.1) // Default opacity
                                ->helperText(__('افضل قيمة هي بين ( 0.02-0.03 )')),
                        ]),

                ]),
        ];
    }
}
