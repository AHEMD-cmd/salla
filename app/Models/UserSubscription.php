<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class UserSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'user_id',
        'subscription_id',
        'start_date',
        'end_date',
        'auto_renewal',
        'status',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'auto_renewal' => 'boolean',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    // Accessors
    public function getRemainingDaysAttribute(): int
    {
        if ($this->status !== 'active') {
            return 0;
        }

        $today = Carbon::now()->startOfDay();
        $endDate = Carbon::parse($this->end_date)->startOfDay();

        if ($endDate->isPast()) {
            return 0;
        }

        return $today->diffInDays($endDate);
    }

    public function getIsExpiredAttribute(): bool
    {
        return Carbon::now()->startOfDay()->greaterThan(Carbon::parse($this->end_date)->startOfDay());
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'active' => 'نشط',
            'suspended' => 'موقّف مؤقت',
            'cancelled' => 'ملغي',
            default => 'غير معروف'
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'active' => 'success',
            'suspended' => 'warning',
            'cancelled' => 'danger',
            default => 'gray'
        };
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeExpired($query)
    {
        return $query->whereDate('end_date', '<', Carbon::now()->startOfDay());
    }

    public function scopeExpiringSoon($query, $days = 7)
    {
        return $query->where('status', 'active')
                    ->whereDate('end_date', '>=', Carbon::now())
                    ->whereDate('end_date', '<=', Carbon::now()->addDays($days));
    }

    // Model Events
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->order_number)) {
                $model->order_number = 'ORD-' . str_pad(
                    static::max('id') + 1, 
                    6, 
                    '0', 
                    STR_PAD_LEFT
                );
            }

            // Auto calculate end_date based on subscription duration
            if ($model->subscription && $model->start_date) {
                $model->end_date = Carbon::parse($model->start_date)
                    ->addDays($model->subscription->duration);
            }
        });
    }
}