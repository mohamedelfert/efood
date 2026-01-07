<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\Models\CashbackSetting;
use App\Model\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\View\View;
use App\Http\Controllers\Controller;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\Foundation\Application;

class CashbackSettingController extends Controller
{
    public function __construct(
        private CashbackSetting $cashbackSetting
    ) {}

    /**
     * Display cashback settings list
     */
    public function index(Request $request): View|Factory|Application
    {
        $queryParam = [];
        $search = $request['search'] ?? '';

        if ($request->has('search')) {
            $key = explode(' ', $request['search']);
            $settings = $this->cashbackSetting->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('title', 'like', "%{$value}%")
                      ->orWhere('type', 'like', "%{$value}%")
                      ->orWhereHas('branch', function($query) use ($value) {
                          $query->where('name', 'like', "%{$value}%");
                      });
                }
            });
            $queryParam = ['search' => $request['search']];
        } else {
            $settings = $this->cashbackSetting;
        }

        $settings = $settings->with('branch')->latest()->paginate(Helpers::getPagination())->appends($queryParam);
        
        return view('admin-views.customer.cashback.index', compact('settings', 'search'));
    }

    /**
     * Store new cashback setting
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'title' => 'required|max:191',
            'branch_id' => 'nullable|exists:branches,id',
            'type' => 'required|in:wallet_topup,order',
            'cashback_type' => 'required|in:percentage,fixed',
            'cashback_value' => 'required|numeric|min:0',
            'min_amount' => 'required|numeric|min:0',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
        ], [
            'title.required' => translate('Title is required'),
            'type.required' => translate('Cashback type is required'),
            'branch_id.exists' => translate('Selected branch is invalid'),
            'start_date.after_or_equal' => translate('Start date must be today or later'),
            'end_date.after_or_equal' => translate('End date must be after or equal to start date'),
            'cashback_value.required' => translate('Cashback value is required'),
            'cashback_value.min' => translate('Cashback value must be greater than 0'),
            'min_amount.required' => translate('Minimum amount is required'),
            'min_amount.min' => translate('Minimum amount must be greater than 0'),
        ]);

        // Additional validation for percentage
        if ($request->cashback_type === 'percentage' && $request->cashback_value > 100) {
            Toastr::error(translate('Percentage cashback cannot exceed 100%'));
            return back()->withInput();
        }

        // Check for duplicate settings
        $exists = $this->cashbackSetting
            ->where('type', $request->type)
            ->where('branch_id', $request->branch_id)
            ->where('status', 1)
            ->where(function($q) use ($request) {
                $q->whereBetween('start_date', [$request->start_date, $request->end_date])
                  ->orWhereBetween('end_date', [$request->start_date, $request->end_date])
                  ->orWhere(function($q) use ($request) {
                      $q->where('start_date', '<=', $request->start_date)
                        ->where('end_date', '>=', $request->end_date);
                  });
            })
            ->exists();

        if ($exists) {
            Toastr::error(translate('A similar cashback setting already exists for this branch and period'));
            return back()->withInput();
        }

        try {
            $setting = new CashbackSetting();
            $setting->title = $request->title;
            $setting->description = $request->description;
            $setting->branch_id = $request->branch_id;
            $setting->type = $request->type;
            $setting->cashback_type = $request->cashback_type;
            $setting->cashback_value = $request->cashback_value;
            $setting->min_amount = $request->min_amount;
            $setting->start_date = $request->start_date;
            $setting->end_date = $request->end_date;
            $setting->status = 1;
            $setting->save();

            Toastr::success(translate('Cashback setting added successfully'));
            return back();
        } catch (\Exception $e) {
            Toastr::error(translate('Failed to create cashback setting: ') . $e->getMessage());
            return back()->withInput();
        }
    }

    /**
     * Show edit form
     */
    public function edit($id): View|Factory|Application
    {
        $setting = $this->cashbackSetting->with('branch')->find($id);
        
        if (!$setting) {
            Toastr::error(translate('Cashback setting not found'));
            return redirect()->route('admin.customer.cashback.index');
        }
        
        return view('admin-views.customer.cashback.edit', compact('setting'));
    }

    /**
     * Update cashback setting
     */
    public function update(Request $request, $id): RedirectResponse
    {
        $request->validate([
            'title' => 'required|max:191',
            'branch_id' => 'nullable|exists:branches,id',
            'type' => 'required|in:wallet_topup,order',
            'cashback_type' => 'required|in:percentage,fixed',
            'cashback_value' => 'required|numeric|min:0',
            'min_amount' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ], [
            'title.required' => translate('Title is required'),
            'end_date.after_or_equal' => translate('End date must be after or equal to start date'),
        ]);

        // Additional validation for percentage
        if ($request->cashback_type === 'percentage' && $request->cashback_value > 100) {
            Toastr::error(translate('Percentage cashback cannot exceed 100%'));
            return back()->withInput();
        }

        // Check for duplicate settings (excluding current)
        $exists = $this->cashbackSetting
            ->where('id', '!=', $id)
            ->where('type', $request->type)
            ->where('branch_id', $request->branch_id)
            ->where('status', 1)
            ->where(function($q) use ($request) {
                $q->whereBetween('start_date', [$request->start_date, $request->end_date])
                  ->orWhereBetween('end_date', [$request->start_date, $request->end_date])
                  ->orWhere(function($q) use ($request) {
                      $q->where('start_date', '<=', $request->start_date)
                        ->where('end_date', '>=', $request->end_date);
                  });
            })
            ->exists();

        if ($exists) {
            Toastr::error(translate('A similar cashback setting already exists for this branch and period'));
            return back()->withInput();
        }

        try {
            $setting = $this->cashbackSetting->find($id);
            
            if (!$setting) {
                Toastr::error(translate('Cashback setting not found'));
                return redirect()->route('admin.customer.cashback.index');
            }
            
            $setting->title = $request->title;
            $setting->description = $request->description;
            $setting->branch_id = $request->branch_id;
            $setting->type = $request->type;
            $setting->cashback_type = $request->cashback_type;
            $setting->cashback_value = $request->cashback_value;
            $setting->min_amount = $request->min_amount;
            $setting->start_date = $request->start_date;
            $setting->end_date = $request->end_date;
            $setting->save();

            Toastr::success(translate('Cashback setting updated successfully'));
            return redirect()->route('admin.customer.cashback.index');
        } catch (\Exception $e) {
            Toastr::error(translate('Failed to update cashback setting: ') . $e->getMessage());
            return back()->withInput();
        }
    }

    /**
     * Toggle status
     */
    public function status(Request $request): RedirectResponse
    {
        try {
            $setting = $this->cashbackSetting->find($request->id);
            
            if (!$setting) {
                Toastr::error(translate('Cashback setting not found'));
                return back();
            }
            
            $setting->status = $request->status;
            $setting->save();

            Toastr::success(translate('Cashback status updated!'));
            return back();
        } catch (\Exception $e) {
            Toastr::error(translate('Failed to update status: ') . $e->getMessage());
            return back();
        }
    }

    /**
     * Delete setting
     */
    public function delete(Request $request): RedirectResponse
    {
        try {
            $setting = $this->cashbackSetting->find($request->id);
            
            if (!$setting) {
                Toastr::error(translate('Cashback setting not found'));
                return back();
            }
            
            $setting->delete();

            Toastr::success(translate('Cashback setting removed!'));
            return back();
        } catch (\Exception $e) {
            Toastr::error(translate('Failed to delete cashback setting: ') . $e->getMessage());
            return back();
        }
    }

    /**
     * Get cashback statistics
     */
    public function statistics(): View|Factory|Application
    {
        $totalCashbackGiven = WalletTransaction::whereIn('transaction_type', ['wallet_topup_cashback', 'order_cashback'])
            ->sum('credit');

        $walletTopupCashback = WalletTransaction::where('transaction_type', 'wallet_topup_cashback')
            ->sum('credit');

        $orderCashback = WalletTransaction::where('transaction_type', 'order_cashback')
            ->sum('credit');

        $thisMonthCashback = WalletTransaction::whereIn('transaction_type', ['wallet_topup_cashback', 'order_cashback'])
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('credit');

        // Cashback by branch
        $branchCashback = WalletTransaction::whereIn('transaction_type', ['wallet_topup_cashback', 'order_cashback'])
            ->selectRaw('JSON_EXTRACT(metadata, "$.branch_id") as branch_id, SUM(credit) as total')
            ->groupBy('branch_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $topUsers = WalletTransaction::whereIn('transaction_type', ['wallet_topup_cashback', 'order_cashback'])
            ->select('user_id', DB::raw('SUM(credit) as total_cashback'))
            ->groupBy('user_id')
            ->orderBy('total_cashback', 'desc')
            ->with('user')
            ->limit(10)
            ->get();

        return view('admin-views.customer.cashback.statistics', compact(
            'totalCashbackGiven',
            'walletTopupCashback',
            'orderCashback',
            'thisMonthCashback',
            'branchCashback',
            'topUsers'
        ));
    }
}