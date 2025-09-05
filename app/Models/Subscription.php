<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'desc',
        'duration',
        'number_of_files',
        'unlimited_files',
        'number_of_downloads',
        'unlimited_downloads',
        'telegram_status',
        'price',
        'currency',
        'is_active',
    ];

    protected $casts = [
        'unlimited_files' => 'boolean',
        'unlimited_downloads' => 'boolean',
        'telegram_status' => 'boolean',
        'is_active' => 'boolean',
        'price' => 'decimal:2',
    ];

    // Accessor to get formatted files display
    public function getFilesDisplayAttribute(): string
    {
        return $this->unlimited_files ? 'غير محدود' : number_format($this->number_of_files);
    }

    // Accessor to get formatted downloads display
    public function getDownloadsDisplayAttribute(): string
    {
        return $this->unlimited_downloads ? 'غير محدود' : number_format($this->number_of_downloads);
    }

    // Accessor to get formatted price
    public function getPriceDisplayAttribute(): string
    {
        return number_format($this->price, 2) . ' ' . $this->currency;
    }

    // Scope for active subscriptions
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}