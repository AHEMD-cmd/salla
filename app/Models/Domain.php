<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Helpers\LicenseHelper;

class Domain extends Model
{
    protected $fillable = [
        'name',
        'type',
        'license_key',
    ];

    
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = trim($value, '/');
        
    }

    protected static function booted()
    {
        static::creating(function ($domain) {
            // Make sure name is trimmed before generating key
            $domain->name = trim($domain->name, '/');
            $domain->license_key = LicenseHelper::generateKey($domain->name, $domain->type);
        });
    }
}
