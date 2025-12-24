<?php

namespace App\Models;

use App\User;
use App\Model\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceReview extends Model
{
    protected $table = 'service_reviews';

    protected $fillable = [
        'user_id',
        'service_type',
        'order_id',
        'rating',
        'comment',
        'attachment',
        'service_ratings',
        'is_active'
    ];

    protected $casts = [
        'rating' => 'float',
        'attachment' => 'array',
        'service_ratings' => 'array',
        'is_active' => 'boolean',
        'user_id' => 'integer',
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
     * Scope to filter by service type
     */
    public function scopeByServiceType($query, $serviceType)
    {
        return $query->where('service_type', $serviceType);
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

    /**
     * Get average rating for specific service aspect
     */
    public function getAspectRating($aspect)
    {
        if (!$this->service_ratings || !is_array($this->service_ratings)) {
            return null;
        }

        $aspectRating = collect($this->service_ratings)->firstWhere('aspect', $aspect);
        return $aspectRating ? $aspectRating['rating'] : null;
    }
}