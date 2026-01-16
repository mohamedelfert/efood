<?php

namespace App\Http\Controllers\Api\V1;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Model\Coupon;
use App\Model\Order;
use App\Model\Branch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CouponController extends Controller
{
    public function __construct(
        private Coupon $coupon,
        private Order  $order,
        private Branch $branch
    ) {}

    /**
     * Get list of active coupons
     * @param Request $request
     * @return JsonResponse
     */
    public function list(Request $request): JsonResponse
    {
        try {
            $branchId = $request->header('branch-id') ?? $request->branch_id;
            
            $couponQuery = $this->coupon->active()
                ->with('branch:id,name');

            // Filter by branch if specified
            if ($branchId && $branchId != 'all') {
                $couponQuery->forBranch($branchId);
            }

            // Filter by coupon type based on authentication
            if (auth('api')->user()) {
                $coupons = $couponQuery->get();
            } else {
                $coupons = $couponQuery->default()->get();
            }

            // Transform response to include additional info
            $coupons = $coupons->map(function ($coupon) use ($branchId) {
                return [
                    'id' => $coupon->id,
                    'title' => $coupon->title,
                    'code' => $coupon->code,
                    'discount' => $coupon->discount,
                    'discount_type' => $coupon->discount_type,
                    'min_purchase' => $coupon->min_purchase,
                    'max_discount' => $coupon->max_discount,
                    'start_date' => $coupon->start_date->format('Y-m-d'),
                    'expire_date' => $coupon->expire_date->format('Y-m-d'),
                    'coupon_type' => $coupon->coupon_type,
                    'limit' => $coupon->limit,
                    'branch_id' => $coupon->branch_id,
                    'branch_name' => $coupon->branch ? $coupon->branch->name : 'All Branches',
                    'is_available' => $coupon->isAvailableForBranch($branchId),
                    'remaining_days' => now()->diffInDays($coupon->expire_date, false),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $coupons
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'errors' => [['code' => 'coupon', 'message' => translate('Failed to fetch coupons')]]
            ], 500);
        }
    }

    /**
     * Apply coupon code
     * @param Request $request
     * @return JsonResponse
     */
    public function apply(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:15',
            'guest_id' => auth('api')->user() ? 'nullable' : 'required',
            'order_amount' => 'required|numeric|min:0',
            'branch_id' => 'nullable|exists:branches,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => Helpers::error_processor($validator)
            ], 422);
        }

        try {
            $branchId = $request->branch_id ?? $request->header('branch-id');
            
            $couponQuery = $this->coupon->active()
                ->where('code', strtoupper($request->code));

            // Apply branch filter if specified
            if ($branchId && $branchId != 'all') {
                $couponQuery->forBranch($branchId);
            }

            $coupon = $couponQuery->first();

            if (!$coupon) {
                return response()->json([
                    'success' => false,
                    'errors' => [['code' => 'coupon', 'message' => translate('Coupon not found or expired')]]
                ], 404);
            }

            // Check if coupon is available for the selected branch
            if ($branchId && !$coupon->isAvailableForBranch($branchId)) {
                return response()->json([
                    'success' => false,
                    'errors' => [['code' => 'coupon', 'message' => translate('This coupon is not valid for the selected branch')]]
                ], 401);
            }

            // Check minimum purchase requirement
            if ($coupon->min_purchase > 0 && $request->order_amount < $coupon->min_purchase) {
                return response()->json([
                    'success' => false,
                    'errors' => [
                        [
                            'code' => 'coupon', 
                            'message' => translate('Minimum purchase amount is') . ' ' . Helpers::currency_symbol() . $coupon->min_purchase
                        ]
                    ]
                ], 401);
            }

            // First order coupon validation
            if ($coupon->coupon_type == 'first_order') {
                if (!auth('api')->user()) {
                    return response()->json([
                        'success' => false,
                        'errors' => [['code' => 'coupon', 'message' => translate('Please login to use this coupon')]]
                    ], 401);
                }

                $orderCount = $this->order
                    ->where(['user_id' => auth('api')->user()->id, 'is_guest' => 0])
                    ->count();

                if ($orderCount > 0) {
                    return response()->json([
                        'success' => false,
                        'errors' => [['code' => 'coupon', 'message' => translate('This coupon is only valid for first order')]]
                    ], 401);
                }
            }

            // Default coupon limit validation
            if ($coupon->coupon_type == 'default' && $coupon->limit !== null) {
                $userId = auth('api')->user() ? auth('api')->user()->id : $request->guest_id;
                $userType = auth('api')->user() ? 0 : 1;

                $usageCount = $this->order
                    ->where([
                        'user_id' => $userId,
                        'coupon_code' => strtoupper($request->code),
                        'is_guest' => $userType
                    ])
                    ->count();

                if ($usageCount >= $coupon->limit) {
                    return response()->json([
                        'success' => false,
                        'errors' => [['code' => 'coupon', 'message' => translate('Coupon usage limit exceeded')]]
                    ], 401);
                }

                $remainingUses = $coupon->limit - $usageCount;
            }

            // Calculate discount amount
            $discountAmount = 0;
            if ($coupon->discount_type == 'percent') {
                $discountAmount = ($request->order_amount * $coupon->discount) / 100;
                
                if ($coupon->max_discount > 0 && $discountAmount > $coupon->max_discount) {
                    $discountAmount = $coupon->max_discount;
                }
            } else {
                $discountAmount = $coupon->discount;
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $coupon->id,
                    'title' => $coupon->title,
                    'code' => $coupon->code,
                    'discount' => $coupon->discount,
                    'discount_type' => $coupon->discount_type,
                    'discount_amount' => round($discountAmount, 2),
                    'min_purchase' => $coupon->min_purchase,
                    'max_discount' => $coupon->max_discount,
                    'coupon_type' => $coupon->coupon_type,
                    'branch_id' => $coupon->branch_id,
                    'remaining_uses' => $remainingUses ?? null,
                    'expire_date' => $coupon->expire_date->format('Y-m-d'),
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'errors' => [['code' => 'server', 'message' => translate('Failed to apply coupon. Please try again.')]]
            ], 500);
        }
    }

    /**
     * Get coupon details by code
     * @param Request $request
     * @return JsonResponse
     */
    public function details(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:15'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => Helpers::error_processor($validator)
            ], 422);
        }

        try {
            $branchId = $request->branch_id ?? $request->header('branch-id');
            
            $coupon = $this->coupon->active()
                ->where('code', strtoupper($request->code))
                ->with('branch:id,name')
                ->first();

            if (!$coupon) {
                return response()->json([
                    'success' => false,
                    'errors' => [['code' => 'coupon', 'message' => translate('Coupon not found')]]
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $coupon->id,
                    'title' => $coupon->title,
                    'code' => $coupon->code,
                    'discount' => $coupon->discount,
                    'discount_type' => $coupon->discount_type,
                    'min_purchase' => $coupon->min_purchase,
                    'max_discount' => $coupon->max_discount,
                    'start_date' => $coupon->start_date->format('Y-m-d'),
                    'expire_date' => $coupon->expire_date->format('Y-m-d'),
                    'coupon_type' => $coupon->coupon_type,
                    'limit' => $coupon->limit,
                    'branch_id' => $coupon->branch_id,
                    'branch_name' => $coupon->branch ? $coupon->branch->name : 'All Branches',
                    'is_available' => $coupon->isAvailableForBranch($branchId),
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'errors' => [['code' => 'server', 'message' => translate('Failed to fetch coupon details')]]
            ], 500);
        }
    }

    /**
     * Get available coupons for specific order amount
     * @param Request $request
     * @return JsonResponse
     */
    public function availableCoupons(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_amount' => 'required|numeric|min:0',
            'branch_id' => 'nullable|exists:branches,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => Helpers::error_processor($validator)
            ], 422);
        }

        try {
            $branchId = $request->branch_id ?? $request->header('branch-id');
            $orderAmount = $request->order_amount;

            $couponQuery = $this->coupon->active()
                ->with('branch:id,name')
                ->where('min_purchase', '<=', $orderAmount);

            if ($branchId && $branchId != 'all') {
                $couponQuery->forBranch($branchId);
            }

            if (auth('api')->user()) {
                $coupons = $couponQuery->get();
            } else {
                $coupons = $couponQuery->default()->get();
            }

            $availableCoupons = $coupons->map(function ($coupon) use ($orderAmount) {
                $discountAmount = 0;
                if ($coupon->discount_type == 'percent') {
                    $discountAmount = ($orderAmount * $coupon->discount) / 100;
                    if ($coupon->max_discount > 0 && $discountAmount > $coupon->max_discount) {
                        $discountAmount = $coupon->max_discount;
                    }
                } else {
                    $discountAmount = $coupon->discount;
                }

                return [
                    'id' => $coupon->id,
                    'title' => $coupon->title,
                    'code' => $coupon->code,
                    'discount' => $coupon->discount,
                    'discount_type' => $coupon->discount_type,
                    'discount_amount' => round($discountAmount, 2),
                    'min_purchase' => $coupon->min_purchase,
                    'max_discount' => $coupon->max_discount,
                    'coupon_type' => $coupon->coupon_type,
                    'branch_name' => $coupon->branch ? $coupon->branch->name : 'All Branches',
                    'expire_date' => $coupon->expire_date->format('Y-m-d'),
                    'remaining_days' => now()->diffInDays($coupon->expire_date, false),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $availableCoupons
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'errors' => [['code' => 'server', 'message' => translate('Failed to fetch available coupons')]]
            ], 500);
        }
    }
}