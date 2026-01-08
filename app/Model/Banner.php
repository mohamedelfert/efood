<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class Banner extends Model
{
    protected $casts = [
        'product_id' => 'integer',
        'category_id' => 'integer',
        'offer_price' => 'float',
        'discount_percentage' => 'float',
        'start_date' => 'date',
        'end_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $fillable = [
        'title',
        'image',
        'banner_type',
        'product_id',
        'category_id',
        'offer_price',
        'discount_percentage',
        'start_date',
        'end_date',
        'status'
    ];

    public function scopeActive($query)
    {
        return $query->where('status', '=', 1);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }

    /**
     * Multiple products relationship with offer details in pivot
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'banner_products', 'banner_id', 'product_id')
            ->withPivot('offer_price', 'discount_percentage')
            ->withTimestamps();
    }

    public function getImageFullPathAttribute(): string
    {
        $image = $this->image ?? null;
        $path = asset('public/assets/admin/img/icons/upload_img2.png');

        if (!is_null($image) && Storage::disk('public')->exists('banner/' . $image)) {
            $path = asset('storage/app/public/banner/' . $image);
        }
        return $path;
    }

    /**
     * Check if banner offer is active based on date range
     */
    public function isOfferActive(): bool
    {
        if (!$this->start_date || !$this->end_date) {
            return true; // No date restriction
        }

        $now = now();
        return $now->between($this->start_date, $this->end_date);
    }

    /**
     * Calculate original price based on banner type
     */
    public function calculateOriginalPrice(): float
    {
        $total = 0;

        if ($this->banner_type === 'single_product' && $this->product) {
            $total = $this->product->price;
        } elseif ($this->banner_type === 'multiple_products') {
            $total = $this->products->sum('price');
        }

        return $total;
    }

    /**
     * Calculate final price after discount
     */
    public function calculateFinalPrice(): float
    {
        $originalPrice = $this->calculateOriginalPrice();

        // For single product
        if ($this->banner_type === 'single_product') {
            if ($this->offer_price) {
                return $this->offer_price;
            } elseif ($this->discount_percentage) {
                return $originalPrice - ($originalPrice * ($this->discount_percentage / 100));
            }
        }

        // For multiple products - sum individual offer prices
        if ($this->banner_type === 'multiple_products') {
            $total = 0;
            foreach ($this->products as $product) {
                if ($product->pivot->offer_price) {
                    $total += $product->pivot->offer_price;
                } elseif ($product->pivot->discount_percentage) {
                    $total += $product->price - ($product->price * ($product->pivot->discount_percentage / 100));
                } else {
                    $total += $product->price;
                }
            }
            return $total;
        }

        return $originalPrice;
    }

    /**
     * Get total discount amount
     */
    public function getDiscountAmount(): float
    {
        $originalPrice = $this->calculateOriginalPrice();
        $finalPrice = $this->calculateFinalPrice();
        
        return max(0, $originalPrice - $finalPrice);
    }

    /**
     * Get discount percentage
     */
    public function getDiscountPercentage(): float
    {
        $originalPrice = $this->calculateOriginalPrice();
        if ($originalPrice <= 0) {
            return 0;
        }

        $discountAmount = $this->getDiscountAmount();
        return round(($discountAmount / $originalPrice) * 100, 2);
    }
}