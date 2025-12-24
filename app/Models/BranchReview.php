<?php

namespace App\Models;

use App\User;
use App\Model\Order;
use App\Model\Branch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchReview extends Model
{
    protected $table = 'branch_reviews';

    protected $fillable = [
        'user_id',
        'branch_id',
        'order_id',
        'rating',
        'comment',
        'attachment',
        'is_active'
    ];

    protected $casts = [
        'rating' => 'float',
        'attachment' => 'array',
        'is_active' => 'boolean',
        'user_id' => 'integer',
        'branch_id' => 'integer',
        'order_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that created the review
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the branch being reviewed
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    /**
     * Get the order associated with this review
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Scope to get active reviews only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by rating range
     */
    public function scopeByRating($query, $minRating, $maxRating = 5)
    {
        return $query->whereBetween('rating', [$minRating, $maxRating]);
    }

    /**
     * Get formatted attachment URLs
     */
    public function getAttachmentUrlsAttribute()
    {
        if (!$this->attachment || !is_array($this->attachment)) {
            return [];
        }

        return array_map(function ($image) {
            return asset('storage/app/public/review/' . $image);
        }, $this->attachment);
    }
}