<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashbackSetting extends Model
{
    protected $fillable = [
        'type',
        'cashback_type',
        'cashback_value',
        'min_amount',
        'branch_id',
        'status',
        'start_date',
        'end_date',
        'title',
        'description'
    ];

    protected $casts = [
        'cashback_value' => 'decimal:3',
        'min_amount' => 'decimal:3',
        'status' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Relationship with Branch
     * ADD THIS METHOD - IT WAS MISSING!
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(\App\Model\Branch::class, 'branch_id');
    }

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
     * Scope for specific branch or global (null branch_id)
     */
    public function scopeForBranch($query, $branchId)
    {
        return $query->where(function ($q) use ($branchId) {
            $q->where('branch_id', $branchId)
              ->orWhereNull('branch_id'); // Global cashback
        });
    }

    /**
     * Calculate cashback amount (NO MAX LIMIT)
     */
    public function calculateCashback($amount): float
    {
        if ($amount < $this->min_amount) {
            return 0;
        }

        if ($this->cashback_type === 'percentage') {
            // NO MORE max_cashback - return full percentage
            return ($amount * $this->cashback_value) / 100;
        }

        // For fixed type, return the cashback_value directly
        return $this->cashback_value;
    }

    /**
     * Check if cashback is applicable for amount
     */
    public function isApplicable($amount): bool
    {
        return $this->status && $amount >= $this->min_amount;
    }

    /**
     * Get the best applicable cashback for branch and amount
     */
    public static function getBestCashback($type, $branchId, $amount)
    {
        return static::active()
            ->where('type', $type)
            ->forBranch($branchId)
            ->where('min_amount', '<=', $amount)
            ->orderByDesc('cashback_value') // Get highest cashback
            ->orderBy('branch_id', 'desc') // Prioritize branch-specific over global
            ->first();
    }
}