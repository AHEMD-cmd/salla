<?php

namespace App\Filament\Pages\Settings;

use Closure;
use App\Models\User;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;

class Settings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static string $view = 'filament.pages.settings';
    protected static ?string $title = 'اعدادات عامه';

    public ?array $data = [];

    // public static function shouldRegisterNavigation(): bool
    // {
    //     $user = User::where('email', 'admin@admin.com')->first();
    //     if ($user) {
    //         return false;
    //     }
    //     return auth()->user()->type == 'super-admin' || auth()->user()->type == 'admin';
    // }

    public function mount(): void
    {
        $this->form->fill($this->getUserSettings());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Settings')
                    ->schema([
                        Tabs\Tab::make('اعدادات عامه')
                            ->schema([
                                ColorPicker::make('pdf_color')
                                    ->label(__('Watermark Color'))
                                    ->required()
                                    ->rgb()
                                    ->default('#C8C8C8')
                                    ->helperText(__('Select color for watermark text')),

                                TextInput::make('pdf_opacity')
                                    ->label(__('Watermark Opacity'))
                                    ->numeric()
                                    ->required()
                                    ->default(0.1)
                                    ->step(0.01)
                                    ->minValue(0.01)
                                    ->maxValue(1)
                                    ->helperText(__('افضل قيمة هي بين ( 0.02-0.03 )')),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            if (!is_null($value)) {
                DB::table('settings')->updateOrInsert(
                    ['creator_id' => auth()->id(), 'key' => $key],
                    ['value' => json_encode($value), 'updated_at' => now()]
                );
            }
        }

        Notification::make()
            ->title('Settings saved successfully!')
            ->success()
            ->send();
    }

    private function getUserSettings(): array
    {
        $settings = DB::table('settings')
            ->where('creator_id', auth()->id())
            ->whereIn('key', ['pdf_color', 'pdf_opacity'])
            ->pluck('value', 'key')
            ->toArray();

        return [
            'pdf_color' => isset($settings['pdf_color']) ? json_decode($settings['pdf_color'], true) : '#C8C8C8',
            'pdf_opacity' => isset($settings['pdf_opacity']) ? json_decode($settings['pdf_opacity'], true) : 0.1,
        ];
    }

    // Helper method to get user setting (use this in other parts of your app)
    public static function getUserSetting(string $key, $default = null, ?int $userId = null)
    {
        $userId = $userId ?? auth()->id();
        
        $setting = DB::table('settings')
            ->where('creator_id', $userId)
            ->where('key', $key)
            ->first();

        return $setting ? json_decode($setting->value, true) : $default;
    }
}