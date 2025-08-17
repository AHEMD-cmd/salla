<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;

class EditProfile extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';
    protected static string $view = 'filament.pages.edit-profile';
    protected static ?string $navigationLabel = 'تعديل الملف الشخصي';
    protected static ?string $title = 'تعديل الملف الشخصي';

    public $email;
    public $password;
    public $password_confirmation;

    public function mount(): void
    {
        $user = Auth::user();
        $this->form->fill([
            'email' => $user->email,
        ]);
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\TextInput::make('email')
                ->label('البريد الالكتروني')
                ->unique()
                ->required()
                ->email()
                ->validationMessages([
                    'unique' => 'يمكنك فقط تغيير كلمه مرور حسابك',
                ]),

            Forms\Components\TextInput::make('password')
                ->label('كلمة المرور')
                ->password()
                ->required()
                ->minLength(6)
                ->confirmed()
                ->dehydrated(fn($state) => filled($state)) // only include in form state if filled
                ->validationMessages([
                    'required' => 'كلمة المرور مطلوبة',
                    'min' => 'كلمة المرور يجب أن تكون على الأقل 6 أحرف',
                    'confirmed' => 'تأكيد كلمة المرور غير مطابق',
                ]),


            Forms\Components\TextInput::make('password_confirmation')
                ->label('تأكيد كلمة المرور')
                ->password()
                ->dehydrated(false)
                ->requiredWith('password')
                ->validationMessages([
                    'required_with' => 'تأكيد كلمة المرور مطلوب عند إدخال كلمة مرور جديدة',
                ]),

        ];
    }

    protected function getFormModel(): string
    {
        return \App\Models\User::class;
    }

    public function submit(): void
    {
        $data = $this->form->getState();
        $user = Auth::user();

        // Update only the fields that changed
        $updateData = ['email' => $data['email']];

        if (!empty($data['password'])) {
            $updateData['password'] = $data['password'];
        }

        // Use update method instead of individual assignment
        $user->update($updateData);

        Notification::make()
            ->title('تم تحديث الملف الشخصي بنجاح!')
            ->success()
            ->send();
    }
}
