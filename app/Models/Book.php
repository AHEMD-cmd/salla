<?php

namespace App\Models;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Book extends Model
{
    protected $fillable = [
        'name',
        'path',
    ];


    protected static function booted()
    {
        static::updating(function ($book) {
            // لو المسار اتغير
            if ($book->isDirty('path')) {
                $oldPath = $book->getOriginal('path');

                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }
        });

        static::creating(function ($book) {
            
            $book->creator_id = Auth::user()->id;
            
        });
    }
    
    public function getNameAttribute($value)
    {
        return trim($value);
    }

}
