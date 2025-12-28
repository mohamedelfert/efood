<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashbackSetting extends Model
{
    protected $fillable = [
        'type',
        'cashback_type',
        'cashback_value',
        'min_amount',
        'max_cashback',
        'status',
        'start_date',
        'end_date',
        'title',
        'description'
    ];

    protected $casts = [
        'cashback_value' => 'decimal:3',
        'min_amount' => 'decimal:3',
        'max_cashback' => 'decimal:3',
        'status' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Scope for active cashback settings
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1)
            ->where(function ($q) {
                $q->whereNull('start_date')
                  ->orWhere('start_date', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', now());
            });
    }

    /**
     * Scope for wallet topup cashback
     */
    public function scopeForWalletTopup($query)
    {
        return $query->where('type', 'wallet_topup');
    }

    /**
     * Scope for order cashback
     */
    public function scopeForOrder($query)
    {
        return $query->where('type', 'order');
    }

    /**
     * Calculate cashback amount
     */
    public function calculateCashback($amount): float
    {
        if ($amount < $this->min_amount) {
            return 0;
        }

        if ($this->cashback_type === 'percentage') {
            $cashback = ($amount * $this->cashback_value) / 100;
            return min($cashback, $this->max_cashback);
        }

        return $this->cashback_value;
    }

    /**
     * Check if cashback is applicable for amount
     */
    public function isApplicable($amount): bool
    {
        return $this->status && $amount >= $this->min_amount;
    }
}