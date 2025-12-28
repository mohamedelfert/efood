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
        $search = $request['search'];

        if ($request->has('search')) {
            $key = explode(' ', $request['search']);
            $settings = $this->cashbackSetting->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('title', 'like', "%{$value}%")
                      ->orWhere('type', 'like', "%{$value}%");
                }
            });
            $queryParam = ['search' => $request['search']];
        } else {
            $settings = $this->cashbackSetting;
        }

        $settings = $settings->latest()->paginate(Helpers::getPagination())->appends($queryParam);
        
        return view('admin-views.customer.cashback.index', compact('settings', 'search'));
    }

    /**
     * Store new cashback setting
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'title' => 'required|max:191',
            'type' => 'required|in:wallet_topup,order',
            'cashback_type' => 'required|in:percentage,fixed',
            'cashback_value' => 'required|numeric|min:0',
            'min_amount' => 'required|numeric|min:0',
            'max_cashback' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ], [
            'title.required' => translate('title_is_required'),
            'type.required' => translate('type_is_required'),
        ]);

        $setting = $this->cashbackSetting;
        $setting->title = $request->title;
        $setting->description = $request->description;
        $setting->type = $request->type;
        $setting->cashback_type = $request->cashback_type;
        $setting->cashback_value = $request->cashback_value;
        $setting->min_amount = $request->min_amount;
        $setting->max_cashback = $request->max_cashback;
        $setting->start_date = $request->start_date;
        $setting->end_date = $request->end_date;
        $setting->status = 1;
        $setting->save();

        Toastr::success(translate('Cashback setting added successfully'));
        return back();
    }

    /**
     * Show edit form
     */
    public function edit($id): View|Factory|Application
    {
        $setting = $this->cashbackSetting->find($id);
        return view('admin-views.customer.cashback.edit', compact('setting'));
    }

    /**
     * Update cashback setting
     */
    public function update(Request $request, $id): RedirectResponse
    {
        $request->validate([
            'title' => 'required|max:191',
            'type' => 'required|in:wallet_topup,order',
            'cashback_type' => 'required|in:percentage,fixed',
            'cashback_value' => 'required|numeric|min:0',
            'min_amount' => 'required|numeric|min:0',
            'max_cashback' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $setting = $this->cashbackSetting->find($id);
        $setting->title = $request->title;
        $setting->description = $request->description;
        $setting->type = $request->type;
        $setting->cashback_type = $request->cashback_type;
        $setting->cashback_value = $request->cashback_value;
        $setting->min_amount = $request->min_amount;
        $setting->max_cashback = $request->max_cashback;
        $setting->start_date = $request->start_date;
        $setting->end_date = $request->end_date;
        $setting->save();

        Toastr::success(translate('Cashback setting updated successfully'));
        return redirect()->route('admin.customer.cashback.index');
    }

    /**
     * Toggle status
     */
    public function status(Request $request): RedirectResponse
    {
        $setting = $this->cashbackSetting->find($request->id);
        $setting->status = $request->status;
        $setting->save();

        Toastr::success(translate('Cashback status updated!'));
        return back();
    }

    /**
     * Delete setting
     */
    public function delete(Request $request): RedirectResponse
    {
        $setting = $this->cashbackSetting->find($request->id);
        $setting->delete();

        Toastr::success(translate('Cashback setting removed!'));
        return back();
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
            'topUsers'
        ));
    }
}