<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use App\Http\Controllers\Front\FrontController;

class Order extends Model
{
    protected $guarded = [];

    protected $casts = [
        'data' => 'array',
        'invoice' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->serial_number)) {
                $order->serial_number = random_int(10000, 99999);
            }

            if (empty($order->order_number)) {
                $order->order_number = random_int(100000000, 999999999);
                while (Order::where('order_number', $order->order_number)->exists()) {
                    $order->order_number = random_int(100000000, 999999999);
                }
            }

            if (request()->getHost() == 'salla.cupun.net') { //  better sulotion request()->getHost() == env(APP_URL)
                $order->status = 'تم التنفيذ';
            }

            $order->pdf_path_original = $order->pdf_path;
        });

        static::created(function ($order) {
            if (!Str::startsWith($order->pdf_path, '/book_')) {
                $pdf = new FrontController();
                $pdf->create_pdf_order($order->id);
            }
        });
    }
}
