<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use App\Models\DeliveryChargeSetup;
use App\Models\DeliveryChargeByArea;

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
    ];


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
}