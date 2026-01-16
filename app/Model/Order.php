<?php

namespace App\Model;

use App\User;
use App\Models\GuestUser;
use App\Models\OrderArea;
use App\Models\BranchReview;
use App\Models\ServiceReview;
use App\Models\OfflinePayment;
use App\Models\OrderChangeAmount;
use App\Models\OrderPartialPayment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    protected $casts = [
        'order_amount' => 'float',
        'coupon_discount_amount' => 'float',
        'total_tax_amount' => 'float',
        'total_add_on_tax' => 'float',
        'delivery_address_id' => 'integer',
        'delivery_man_id' => 'integer',
        'delivery_charge' => 'float',
        'user_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'delivery_address' => 'array',
        'table_id' => 'integer',
        'number_of_people' => 'integer',
        'table_order_id' => 'integer',
        'is_cutlery_required' => 'integer',
        'bring_change_amount' => 'float',
        'cancel_reason' => 'string',
    ];

    public function details(): HasMany
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function delivery_man(): BelongsTo
    {
        return $this->belongsTo(DeliveryMan::class, 'delivery_man_id')->withCount('orders');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withCount('orders');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id')->withCount('orders');
    }

    public function delivery_address(): BelongsTo
    {
        return $this->belongsTo(CustomerAddress::class, 'delivery_address_id');
    }

    public function customer_delivery_address(): BelongsTo
    {
        return $this->belongsTo(CustomerAddress::class, 'delivery_address_id');
    }

    public function table_order(): BelongsTo
    {
        return $this->belongsTo(TableOrder::class, 'table_order_id', 'id');
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class, 'table_id', 'id');
    }

    public function scopePos($query)
    {
        return $query->where('order_type', '=', 'pos');
    }

    public function scopeDineIn($query)
    {
        return $query->where('order_type', '=', 'dine_in');
    }


    public function scopeNotDineIn($query)
    {
        return $query->where('order_type', '!=', 'dine_in');
    }

    public function scopeNotPos($query)
    {
        return $query->where('order_type', '!=', 'pos');
    }

    public function scopeSchedule($query)
    {
        return $query->whereDate('delivery_date', '>', \Carbon\Carbon::now()->format('Y-m-d'));
    }

    public function scopeNotSchedule($query)
    {
        return $query->whereDate('delivery_date', '<=', \Carbon\Carbon::now()->format('Y-m-d'));
    }

    public function scopeEarningReport($query)
    {
        return $query->whereIn('order_status', ['delivered', 'completed']);
    }

    public function transaction(): HasOne
    {
        return $this->hasOne(OrderTransaction::class);
    }

    public function order_partial_payments(): HasMany
    {
        return $this->hasMany(OrderPartialPayment::class)->orderBy('id', 'DESC');
    }

    public function offline_payment()
    {
        return $this->hasOne(OfflinePayment::class, 'order_id');
    }

    public function scopePartial($query)
    {
        return $query->whereHas('partial_payment');
    }

    public function guest()
    {
        return $this->belongsTo(GuestUser::class, 'user_id');
    }

    public function deliveryman_review()
    {
        return $this->hasOne(DMReview::class, 'order_id');
    }

    public function order_area()
    {
        return $this->hasOne(OrderArea::class, 'order_id');
    }

    public function order_change_amount()
    {
        return $this->hasOne(OrderChangeAmount::class, 'order_id');
    }

    /**
     * Get branch review for this order
     */
    public function branchReview()
    {
        return $this->hasOne(BranchReview::class);
    }

    /**
     * Get service review for this order
     */
    public function serviceReview()
    {
        return $this->hasOne(ServiceReview::class);
    }

    /**
     * Check if order has been reviewed for branch
     */
    public function hasBranchReview()
    {
        return $this->branchReview()->exists();
    }

    /**
     * Check if order has been reviewed for service
     */
    public function hasServiceReview()
    {
        return $this->serviceReview()->exists();
    }

    /**
     * Check if order has been reviewed for product
     */
    public function hasProductReviews()
    {
        return $this->details()->whereHas('product.reviews', function ($query) {
            $query->where('order_id', $this->id);
        })->exists();
    }

    /**
     * Check if order has been reviewed for delivery man
     */
    public function hasDeliveryManReview()
    {
        return DMReview::where('order_id', $this->id)->exists();
    }

    /**
     * Get all review statuses for this order
     */
    public function getReviewStatusAttribute()
    {
        return [
            'branch_reviewed' => $this->hasBranchReview(),
            'service_reviewed' => $this->hasServiceReview(),
            'products_reviewed' => $this->hasProductReviews(),
            'delivery_man_reviewed' => $this->hasDeliveryManReview(),
        ];
    }

    public function walletTransactions()
    {
        return $this->hasMany(WalletTransaction::class, 'reference', 'id')
                    ->where('transaction_type', 'order_place');
    }

}