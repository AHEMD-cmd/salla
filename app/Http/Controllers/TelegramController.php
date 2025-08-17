<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\TelegramSetting;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Update;

class TelegramController extends Controller
{
    public function __invoke(Update $update, TelegramSetting $settings, Api $api)
    {
        $welcomeMessage = $settings->welcome_message ?? "👋 مرحبًا بك!\n\nمن فضلك أرسل رقم الطلب الخاص بك للحصول على رابط تحميل الكتاب.";
        $orderMessage = $settings->order_message ?? "✅ تم التحقق من الطلب!\n\n📥 حمل كتابك من الرابط التالي:\n";
        $orderNotFoundMessage = $settings->order_not_found_message ?? "❌ لم يتم العثور على طلب مدفوع بهذا الرقم.\nيرجى التحقق والمحاولة مرة أخرى.";
        $orderAlreadyDeliveredMessage = $settings->telegram_order_already_delivered_message ?? "✅ تم التحقق من الطلب مسبقًا!\n\n📥 يمكنك تحميل الملف من الرابط التالي:\n";

        if ($update->has('message')) {
            $message = $update->getMessage();
            Log::info('Received message: ' . json_encode($message));

            if (!$message) {
                return response('لم يتم استلام أي رسالة.', 200);
            }

            $chat = $message->get('chat');
            $chatId = $chat['id'];
            $text = trim($message->getText());

            if (Str::startsWith($text, '/start')) {
                $api->sendMessage([
                    'chat_id' => $chatId,
                    'text' => $welcomeMessage,
                ]);

                Cache::put("telegram_user_state_{$chatId}", 'awaiting_order', now()->addMinutes(10));
                return response('بانتظار رقم الطلب...', 200);
            }

            $state = Cache::get("telegram_user_state_{$chatId}");

            if ($state === 'awaiting_order') {
                $order = Order::where('order_number', $text)->where('status', 'LIKE', '%تم التنفيذ%')->first();

                if ($order) {
                    $downloadLink = asset('storage/' . $order->pdf_path);

                    if ($order->telegram_delivery_status) {
                        $api->sendMessage([
                            'chat_id' => $chatId,
                            'text' => $orderAlreadyDeliveredMessage . $downloadLink,
                        ]);
                        return response('تم التحقق من الطلب.', 200);
                    }

                    $api->sendMessage([
                        'chat_id' => $chatId,
                        'text' => $orderMessage . $downloadLink,
                    ]);

                    $order->telegram_delivery_status = true;
                    $order->save();

                    Cache::forget("telegram_user_state_{$chatId}");
                } else {
                    $api->sendMessage([
                        'chat_id' => $chatId,
                        'text' => $orderNotFoundMessage,
                    ]);
                }

                return response('تم التحقق من الطلب.', 200);
            }

            $api->sendMessage([
                'chat_id' => $chatId,
                'text' => "❓ أمر غير معروف. اكتب /start للبدء.",
            ]);

            return response('تمت المعالجة.', 200);
        }

        if ($update->has('my_chat_member')) {
            $chatMember = $update->getMyChatMember();
            Log::info('Chat Member Update: ' . json_encode($chatMember));

            $chat = $chatMember->getChat();
            $chatId = $chat['id'];
            $newStatus = $chatMember->getNewChatMember()->getStatus();

            if ($newStatus === 'kicked') {
                Log::info("User with chat_id $chatId removed the bot.");
            }

            return response('my_chat_member handled.', 200);
        }

        return response('نوع التحديث غير مدعوم.', 200);
    }
}
