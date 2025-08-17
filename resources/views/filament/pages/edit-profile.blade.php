<x-filament::page>
    <div class="flex justify-center">
        <form wire:submit.prevent="submit" class="w-full max-w-md space-y-6 rtl:text-right">
            {{ $this->form }}

            <x-filament::button type="submit" color="primary" class="w-full">
                تحديث الملف الشخصي
            </x-filament::button>

            @if (session()->has('success'))
                <div class="text-green-500 text-sm mt-2">
                    {{ session('success') }}
                </div>
            @endif
        </form>
    </div>
</x-filament::page>
