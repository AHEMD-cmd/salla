<?php

namespace App\Models;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'settings';

    protected $fillable = [
        'key',
        'value',
        'creator_id',
    ];

    // protected static function booted()
    // {
    //     parent::booted();

    //     static::creating(function ($setting) {
            
    //         $setting->creator_id = Auth::user()->id;
            
    //     });
        
    //     static::updating(function ($setting) {

    //         $setting->creator_id = Auth::user()->id;
    //     });

    // }
}
