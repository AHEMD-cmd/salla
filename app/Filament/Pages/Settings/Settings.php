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

                            // Textarea::make('telegram_welcome_message')
                            //     ->label(__('Telegram Welcome Message')) // Translated label
                            //     ->helperText(__('Set the welcome message for Telegram users')),
                            // Textarea::make('telegram_order_message')
                            //     ->label(__('Telegram Order Message')) // Translated label


                            //     ->helperText(__('Set the order message for Telegram users')),
                            // Textarea::make('telegram_order_not_found_message')
                            //     ->label(__('Telegram Order Not Found Message')) // Translated label
                            //     ->helperText(__('Set the order not found message for Telegram users')),
                            // Textarea::make('telegram_order_already_delivered_message')
                            //     ->label(__('Telegram Order Already Delivered Message')) // Translated label
                            //     ->helperText(__('Set the order already delivered message for Telegram users')),
                            // File Upload for PDF Book (with Arabic translation)
                            // FileUpload::make('pdf_book')
                            //     ->label(__('PDF Book File')) // Translated label
                            //     ->disk('public')
                            //     ->directory('pdf_books')
                            //     ->helperText(__('Upload the PDF file for the book')),

                            // Watermark X Position as Select
                            // Select::make('pdf_watermark_x')
                            //     ->label(__('Watermark X Position'))
                            //     ->options([
                            //         '30' => __('30 mm'),
                            //         '50' => __('50 mm'),
                            //         '70' => __('70 mm'),
                            //         '100' => __('100 mm'),
                            //         '150' => __('150 mm'),
                            //     ])
                            //     ->default(50)
                            //     ->helperText(__('Select X position for watermark text (horizontal position, in mm)')),

                            // Watermark Y Position as Select
                            // Select::make('pdf_watermark_y')
                            //     ->label(__('Watermark Y Position'))
                            //     ->options([
                            //         '50' => __('50 mm'),
                            //         '100' => __('100 mm'),
                            //         '150' => __('150 mm'),
                            //         '200' => __('200 mm'),
                            //         '250' => __('250 mm'),
                            //     ])
                            //     ->default(150)
                            //     ->helperText(__('Select Y position for watermark text (vertical position, in mm)')),



                            // Select::make('pdf_font_style')
                            // ->label(__('Font Style for Watermark'))
                            // ->options([
                            //     'B' => 'Bold',
                            //     'I' => 'Italic',
                            //     'BI' => 'Bold Italic',
                            //     '' => 'Regular',
                            // ])
                            // ->default('B')
                            // ->helperText(__('Choose font style for watermark text')),
                            //     Select::make('pdf_font_size')
                            //     ->label(__('Watermark Font Size'))
                            //     ->options([
                            //         '12' => '12',
                            //         '14' => '14',
                            //         '16' => '16',
                            //         '18' => '18',
                            //         '20' => '20',
                            //         '22' => '22',
                            //         '24' => '24',
                            //         '26' => '26',
                            //         '28' => '28',
                            //         '30' => '30',
                            //     ])
                            //     ->default(12)
                            //     ->helperText(__('Select font size for watermark text')),


                            //     Select::make('pdf_rotation_angle')
                            // ->label(__('Watermark Rotation Angle'))
                            // ->options([
                            //     '0' => '0°',
                            //     '45' => '45°',
                            //     '90' => '90°',
                            //     '135' => '135°',
                            //     '180' => '180°',
                            //     '225' => '225°',
                            //     '270' => '270°',
                            //     '315' => '315°',
                            // ])
                            // ->default(0) // Default to no rotation
                            // ->helperText(__('Select the rotation angle for the watermark text')),



                        ]),

                ]),
        ];
    }
}
