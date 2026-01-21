<?php

namespace App\Model;

use App\Models\BranchReview;
use App\Models\DeliveryChargeSetup;
use App\Models\DeliveryChargeByArea;
use Illuminate\Support\Facades\Storage;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Branch extends Authenticatable
{
    use Notifiable;

    protected $guarded = [];

    protected $casts = [
        'coverage' => 'integer',
        'status' => 'integer',
        'branch_promotion_status' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'preparation_time' => 'integer',
        'wallet_balance' => 'float',
    ];

    public function wallet_transactions(): HasMany
    {
        return $this->hasMany(BranchWalletTransaction::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'branch_product', 'branch_id', 'product_id')
            ->withTimestamps();
    }

    public function product_details(): HasMany
    {
        return $this->hasMany(ProductByBranch::class, 'branch_id');
    }

    public function branch_promotion(): HasMany
    {
        return $this->hasMany(BranchPromotion::class);
    }

    public function table(): HasMany
    {
        return $this->hasMany(Table::class, 'branch_id', 'id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    // Time schedules relationship
    public function timeSchedules(): HasMany
    {
        return $this->hasMany(TimeSchedule::class, 'branch_id', 'id')
            ->orderBy('day')
            ->orderBy('opening_time');
    }

    /**
     * Get coupons for this branch
     */
    public function coupons(): HasMany
    {
        return $this->hasMany(Coupon::class);
    }

    /**
     * Get active coupons for this branch (including global coupons)
     */
    public function activeCoupons()
    {
        return Coupon::where(function ($query) {
            $query->where('branch_id', $this->id)
                ->orWhereNull('branch_id');
        })
            ->active()
            ->get();
    }

    public function getImageFullPathAttribute(): string
    {
        $image = $this->image ?? null;
        $path = asset('public/assets/admin/img/160x160/img2.jpg');

        if (!is_null($image) && Storage::disk('public')->exists('branch/' . $image)) {
            $path = asset('storage/app/public/branch/' . $image);
        }

        return $path;
    }

    public function getCoverImageFullPathAttribute(): string
    {
        $image = $this->cover_image ?? null;
        $path = asset('public/assets/admin/img/160x160/img2.jpg');

        if (!is_null($image) && Storage::disk('public')->exists('branch/' . $image)) {
            $path = asset('storage/app/public/branch/' . $image);
        }

        return $path;
    }

    public function delivery_charge_setup()
    {
        return $this->hasOne(DeliveryChargeSetup::class, 'branch_id', 'id');
    }

    public function delivery_charge_by_area()
    {
        return $this->hasMany(DeliveryChargeByArea::class, 'branch_id', 'id')->latest();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 0);
    }

    public function scopeStatus($query, $status = 1)
    {
        return $query->where('status', $status);
    }

    public function isActive(): bool
    {
        return $this->status === 1;
    }

    /**
     * Get reviews for this branch
     */
    public function reviews()
    {
        return $this->hasMany(BranchReview::class);
    }

    /**
     * Get active reviews for this branch
     */
    public function activeReviews()
    {
        return $this->hasMany(BranchReview::class)->where('is_active', true);
    }

    /**
     * Get average rating
     */
    public function getAverageRatingAttribute()
    {
        return round($this->reviews()->avg('rating'), 2);
    }

    /**
     * Get total reviews count
     */
    public function getTotalReviewsAttribute()
    {
        return $this->reviews()->count();
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}