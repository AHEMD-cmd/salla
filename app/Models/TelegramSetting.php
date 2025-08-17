<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramSetting extends Model
{
    protected $fillable = [
        'welcome_message',
        'order_message',
        'order_not_found_message',
        'telegram_order_already_delivered_message',
        'webhook_token',
    ];

    public function setWebhookTokenAttribute($value)
    {
        $this->attributes['webhook_token'] = trim($value, '/');
    }
}
