<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

class BookOrder extends Model
{
    protected $fillable = [
        'customer_name',
        'book_id',
        'order_number',
        'serial_number',
    ];

    public function book()
    {
        return $this->belongsTo(Book::class);
    }


    protected static function booted()
    {
        static::creating(function ($book) {

            $book->serial_number = Str::random(6);
            
        });
    }
}
