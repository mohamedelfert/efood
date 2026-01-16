<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Model\Coupon;
use App\Model\Branch;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class CouponController extends Controller
{
    public function __construct(
        private Coupon $coupon,
        private Branch $branch
    )
    {}

    /**
     * Display coupon list with search and branch filter
     * @param Request $request
     * @return Renderable
     */
    public function index(Request $request): Renderable
    {
        $queryParam = [];
        $search = $request['search'];
        $branchFilter = $request['branch_id'] ?? 'all';

        $query = $this->coupon->with('branch');

        // Search functionality
        if ($request->has('search') && !empty($search)) {
            $key = explode(' ', $request['search']);
            $query->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('title', 'like', "%{$value}%")
                        ->orWhere('code', 'like', "%{$value}%");
                }
            });
            $queryParam['search'] = $request['search'];
        }

        // Branch filter functionality
        if ($request->has('branch_id') && $request['branch_id'] != 'all') {
            $query->where(function ($q) use ($branchFilter) {
                $q->where('branch_id', $branchFilter)
                  ->orWhereNull('branch_id'); // Include global coupons
            });
            $queryParam['branch_id'] = $request['branch_id'];
        }

        $coupons = $query->latest()->paginate(Helpers::getPagination())->appends($queryParam);
        $branches = $this->branch->active()->get();

        return view('admin-views.coupon.index', compact('coupons', 'search', 'branches', 'branchFilter'));
    }

    /**
     * Store new coupon
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        // Custom validation rules
        $rules = [
            'code' => 'required|unique:coupons,code|max:15',
            'title' => 'required|max:255',
            'start_date' => 'required|date',
            'expire_date' => 'required|date|after_or_equal:start_date',
            'discount' => 'required|numeric|min:0',
            'min_purchase' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'coupon_type' => 'required|in:default,first_order',
            'discount_type' => 'required|in:percent,amount',
            'limit' => 'nullable|integer|min:1'
        ];

        // Only validate branch_id if it's not 'all'
        if ($request->branch_id != 'all' && !empty($request->branch_id)) {
            $rules['branch_id'] = 'required|exists:branches,id';
        }

        $messages = [
            'title.required' => translate('Coupon title is required'),
            'title.max' => translate('Title is too long!'),
            'code.required' => translate('Coupon code is required'),
            'code.unique' => translate('Coupon code already exists!'),
            'code.max' => translate('Coupon code is too long!'),
            'expire_date.after_or_equal' => translate('Expire date must be after or equal to start date!'),
            'discount.required' => translate('Discount amount is required'),
            'discount.numeric' => translate('Discount must be a number'),
            'branch_id.exists' => translate('Selected branch is invalid'),
        ];

        $request->validate($rules, $messages);

        // Validate percentage discount
        if ($request->discount_type == 'percent' && (float)$request->discount > 100) {
            Toastr::error(translate('discount_can_not_be_more_than_100%'));
            return back()->withInput();
        }

        // Validate percentage discount is at least 1
        if ($request->discount_type == 'percent' && (float)$request->discount < 1) {
            Toastr::error(translate('Discount percentage must be at least 1%'));
            return back()->withInput();
        }

        try {
            $this->coupon->create([
                'branch_id' => ($request->branch_id == 'all' || empty($request->branch_id)) ? null : $request->branch_id,
                'title' => $request->title,
                'code' => strtoupper($request->code),
                'limit' => $request->coupon_type == 'first_order' ? null : ($request->limit ?? null),
                'coupon_type' => $request->coupon_type,
                'start_date' => $request->start_date,
                'expire_date' => $request->expire_date,
                'min_purchase' => $request->min_purchase ?? 0,
                'max_discount' => $request->discount_type == 'percent' ? ($request->max_discount ?? 0) : 0,
                'discount' => $request->discount,
                'discount_type' => $request->discount_type,
                'status' => 1,
            ]);

            Toastr::success(translate('Coupon added successfully!'));
            return redirect()->route('admin.coupon.add-new');
        } catch (\Exception $e) {
            Toastr::error(translate('Failed to add coupon. Please try again.'));
            return back()->withInput();
        }
    }

    /**
     * Show edit coupon form
     * @param $id
     * @return Renderable
     */
    public function edit($id): Renderable
    {
        $coupon = $this->coupon->with('branch')->findOrFail($id);
        $branches = $this->branch->active()->get();
        return view('admin-views.coupon.edit', compact('coupon', 'branches'));
    }

    /**
     * Update existing coupon
     * @param Request $request
     * @param $id
     * @return RedirectResponse
     */
    public function update(Request $request, $id): RedirectResponse
    {
        // Custom validation rules
        $rules = [
            'code' => 'required|unique:coupons,code,' . $id . '|max:15',
            'title' => 'required|max:255',
            'start_date' => 'required|date',
            'expire_date' => 'required|date|after_or_equal:start_date',
            'discount' => 'required|numeric|min:0',
            'min_purchase' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'coupon_type' => 'required|in:default,first_order',
            'discount_type' => 'required|in:percent,amount',
            'limit' => 'nullable|integer|min:1'
        ];

        // Only validate branch_id if it's not 'all'
        if ($request->branch_id != 'all' && !empty($request->branch_id)) {
            $rules['branch_id'] = 'required|exists:branches,id';
        }

        $messages = [
            'title.required' => translate('Coupon title is required'),
            'title.max' => translate('Title is too long!'),
            'code.required' => translate('Coupon code is required'),
            'code.unique' => translate('Coupon code already exists!'),
            'code.max' => translate('Coupon code is too long!'),
            'expire_date.after_or_equal' => translate('Expire date must be after or equal to start date!'),
            'discount.required' => translate('Discount amount is required'),
            'discount.numeric' => translate('Discount must be a number'),
            'branch_id.exists' => translate('Selected branch is invalid'),
        ];

        $request->validate($rules, $messages);

        // Validate percentage discount
        if ($request->discount_type == 'percent' && (float)$request->discount > 100) {
            Toastr::error(translate('discount_can_not_be_more_than_100%'));
            return back()->withInput();
        }

        // Validate percentage discount is at least 1
        if ($request->discount_type == 'percent' && (float)$request->discount < 1) {
            Toastr::error(translate('Discount percentage must be at least 1%'));
            return back()->withInput();
        }

        try {
            $this->coupon->where(['id' => $id])->update([
                'branch_id' => ($request->branch_id == 'all' || empty($request->branch_id)) ? null : $request->branch_id,
                'title' => $request->title,
                'code' => strtoupper($request->code),
                'limit' => $request->coupon_type == 'first_order' ? null : ($request->limit ?? null),
                'coupon_type' => $request->coupon_type,
                'start_date' => $request->start_date,
                'expire_date' => $request->expire_date,
                'min_purchase' => $request->min_purchase ?? 0,
                'max_discount' => $request->discount_type == 'percent' ? ($request->max_discount ?? 0) : 0,
                'discount' => $request->discount,
                'discount_type' => $request->discount_type,
                'updated_at' => now()
            ]);

            Toastr::success(translate('Coupon updated successfully!'));
            return redirect()->route('admin.coupon.add-new');
        } catch (\Exception $e) {
            Toastr::error(translate('Failed to update coupon. Please try again.'));
            return back()->withInput();
        }
    }

    /**
     * Update coupon status
     * @param Request $request
     * @return RedirectResponse
     */
    public function status(Request $request): RedirectResponse
    {
        try {
            $coupon = $this->coupon->findOrFail($request->id);
            $coupon->status = $request->status;
            $coupon->save();

            Toastr::success(translate('Coupon status updated!'));
            return back();
        } catch (\Exception $e) {
            Toastr::error(translate('Failed to update status. Please try again.'));
            return back();
        }
    }

    /**
     * Delete coupon
     * @param Request $request
     * @return RedirectResponse
     */
    public function delete(Request $request): RedirectResponse
    {
        try {
            $coupon = $this->coupon->findOrFail($request->id);
            $coupon->delete();

            Toastr::success(translate('Coupon removed!'));
            return back();
        } catch (\Exception $e) {
            Toastr::error(translate('Failed to delete coupon. Please try again.'));
            return back();
        }
    }

    /**
     * Generate random coupon code
     * @return JsonResponse
     */
    public function generateCouponCode(): JsonResponse
    {
        $code = strtoupper(Str::random(10));
        
        // Ensure code is unique
        while ($this->coupon->where('code', $code)->exists()) {
            $code = strtoupper(Str::random(10));
        }

        return response()->json($code);
    }

    /**
     * Get coupon details for modal view
     * @param Request $request
     * @return JsonResponse
     */
    public function couponDetails(Request $request): JsonResponse
    {
        try {
            $coupon = $this->coupon->with('branch')->findOrFail($request->id);

            return response()->json([
                'success' => 1,
                'view' => view('admin-views.coupon.partials._coupon-view', compact('coupon'))->render(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => 0,
                'message' => translate('Coupon not found'),
            ], 404);
        }
    }
}