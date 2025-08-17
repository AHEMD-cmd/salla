<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TelegramSetting;

class TelegramSettingSeeder extends Seeder
{
    public function run(): void
    {
        TelegramSetting::create([
            'welcome_message' => 'حياك الله في بوتنا! 🌟',
            'order_message' => 'طلبك قيد المعالجة. شكراً لاستخدامك خدمتنا. 🚀',
            'order_not_found_message' => 'عذراً، لم يتم العثور على الطلب. الرجاء التحقق من البيانات المدخلة. ❌',
            'telegram_order_already_delivered_message' => 'هذا الطلب تم تسليمه بالفعل. ✅',
        ]);
    }
}
