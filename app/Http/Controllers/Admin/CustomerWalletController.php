<?php

namespace App\Http\Controllers\Admin;

use PDF;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\Model\BusinessSetting;
use App\Model\WalletTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\CentralLogics\CustomerLogic;
use App\Http\Controllers\Controller;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Contracts\Support\Renderable;
use App\Model\Branch;
use App\CentralLogics\BranchLogic;
use App\Services\CashbackService;

class CustomerWalletController extends Controller
{
    public function __construct(
        private WalletTransaction $walletTransaction,
        private User $user,
        private CashbackService $cashbackService,
    ) {
    }

    /**
     * @return Renderable|RedirectResponse
     */
    public function addFundView(): Renderable|RedirectResponse
    {
        if (BusinessSetting::where('key', 'wallet_status')->first()->value != 1) {
            Toastr::error(translate('customer_wallet_status_is_disable'));
            return back();
        }

        $branches = Branch::active()->get();
        return view('admin-views.customer.wallet.add-fund', compact('branches'));
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function addFund(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0.01',
            'branch_id' => 'required|exists:branches,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)]);
        }

        try {
            DB::beginTransaction();

            // Get the customer
            $customer = User::findOrFail($request->customer_id);

            // Store previous balance before transaction
            $previousBalance = $customer->wallet_balance;

            // 1. Deduct from branch wallet
            $branchTransaction = BranchLogic::create_wallet_transaction(
                $request->branch_id,
                $request->amount,
                'fund_customer_wallet',
                translate('Fund customer wallet: ') . $customer->name . ($request->referance ? ' (' . $request->referance . ')' : '')
            );

            // 2. Create customer wallet transaction
            $walletTransaction = CustomerLogic::create_wallet_transaction(
                $request->customer_id,
                $request->amount,
                'add_fund_from_branch',
                $request->referance . (isset($branchTransaction) ? ' [Branch ID: ' . $request->branch_id . ']' : '')
            );

            if ($walletTransaction) {
                // Apply cashback if applicable
                $cashbackAmount = $this->cashbackService->processWalletTopupCashback(
                    $customer,
                    $request->amount,
                    $walletTransaction->transaction_id ?? $walletTransaction->id,
                    $request->branch_id,
                    'add_fund'
                );

                DB::commit();

                // Refresh customer to get updated balance
                $customer->refresh();

                // Send notifications via NotificationService
                $notificationService = app(\App\Services\NotificationService::class);

                $notificationService->sendWalletTopUpNotification($customer, [
                    'amount' => $request->amount,
                    'currency' => 'YER',
                    'transaction_id' => $walletTransaction->transaction_id ?? $walletTransaction->id,
                    'gateway' => 'Admin Panel',
                    'previous_balance' => $previousBalance,
                    'added_by' => 'admin',
                    'admin_reference' => $request->referance ?? null,
                    'cashback_amount' => $cashbackAmount ?? 0,
                ]);

                return response()->json([
                    'message' => translate('Funds added successfully and notifications sent'),
                    'transaction_id' => $walletTransaction->id,
                    'cashback_amount' => $cashbackAmount ?? 0
                ], 200);
            }

            return response()->json([
                'errors' => [
                    'message' => translate('failed_to_create_transaction')
                ]
            ], 400);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Admin add fund failed', [
                'customer_id' => $request->customer_id,
                'amount' => $request->amount,
                'branch_id' => $request->branch_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'errors' => [
                    ['message' => $e->getMessage()]
                ]
            ], 400);
        }
    }

    /**
     * @param Request $request
     * @return Renderable
     */
    public function report(Request $request): Renderable
    {
        $data = $this->walletTransaction
            ->selectRaw('sum(credit) as total_credit, sum(debit) as total_debit')
            ->when(($request->from && $request->to), function ($query) use ($request) {
                $query->whereBetween('created_at', [$request->from . ' 00:00:00', $request->to . ' 23:59:59']);
            })
            ->when($request->transaction_type, function ($query) use ($request) {
                $query->where('transaction_type', $request->transaction_type);
            })
            ->when($request->customer_id, function ($query) use ($request) {
                $query->where('user_id', $request->customer_id);
            })
            ->get();

        $transactions = $this->walletTransaction
            ->when(($request->from && $request->to), function ($query) use ($request) {
                $query->whereBetween('created_at', [$request->from . ' 00:00:00', $request->to . ' 23:59:59']);
            })
            ->when($request->transaction_type, function ($query) use ($request) {
                $query->where('transaction_type', $request->transaction_type);
            })
            ->when($request->customer_id, function ($query) use ($request) {
                $query->where('user_id', $request->customer_id);
            })
            ->latest()
            ->paginate(Helpers::getPagination());

        return view('admin-views.customer.wallet.report', compact('data', 'transactions'));
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getCustomers(Request $request): JsonResponse
    {
        $key = explode(' ', $request['q']);
        $data = $this->user
            ->where('user_type', null)
            ->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('name', 'like', "%{$value}%")
                        ->orWhere('phone', 'like', "%{$value}%");
                }
            })
            ->limit(8)
            ->get([DB::raw('id, CONCAT(name, " (", phone ,")") as text')]);
        if ($request->all)
            $data[] = (object) ['id' => false, 'text' => translate('all')];

        return response()->json($data);
    }

    /**
     * Customer Balance Summary Report
     * Shows all customers with their wallet balances
     * 
     * @param Request $request
     * @return Renderable
     */
    public function balanceSummary(Request $request): Renderable
    {
        $query = $this->user->where('user_type', null)
            ->withCount('orders')
            ->with([
                'walletTransactions' => function ($q) {
                    $q->latest()->limit(1);
                }
            ]); // Eager load orders count

        // Search functionality
        if ($request->search) {
            $key = explode(' ', $request->search);
            $query->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('name', 'like', "%{$value}%")
                        ->orWhere('phone', 'like', "%{$value}%")
                        ->orWhere('email', 'like', "%{$value}%");
                }
            });
        }

        // Filter by balance
        if ($request->balance_filter) {
            switch ($request->balance_filter) {
                case 'positive':
                    $query->where('wallet_balance', '>', 0);
                    break;
                case 'zero':
                    $query->where('wallet_balance', '=', 0);
                    break;
                case 'negative':
                    $query->where('wallet_balance', '<', 0);
                    break;
            }
        }

        // Filter by minimum balance
        if ($request->min_balance !== null && $request->min_balance !== '') {
            $query->where('wallet_balance', '>=', $request->min_balance);
        }

        // Filter by maximum balance
        if ($request->max_balance !== null && $request->max_balance !== '') {
            $query->where('wallet_balance', '<=', $request->max_balance);
        }

        // Sorting
        $sortBy = $request->sort_by ?? 'name';
        $sortOrder = $request->sort_order ?? 'asc';

        if ($sortBy === 'balance') {
            $query->orderBy('wallet_balance', $sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $customers = $query->paginate(Helpers::getPagination());

        // Calculate summary statistics
        $allCustomers = $this->user->where('user_type', null);

        $statistics = [
            'total_customers' => $allCustomers->count(),
            'total_balance' => $allCustomers->sum('wallet_balance') ?? 0,
            'positive_balance_count' => $allCustomers->where('wallet_balance', '>', 0)->count(),
            'positive_balance_sum' => $allCustomers->where('wallet_balance', '>', 0)->sum('wallet_balance') ?? 0,
            'zero_balance_count' => $allCustomers->where('wallet_balance', '=', 0)->count(),
            'negative_balance_count' => $allCustomers->where('wallet_balance', '<', 0)->count(),
            'negative_balance_sum' => $allCustomers->where('wallet_balance', '<', 0)->sum('wallet_balance') ?? 0,
            'average_balance' => round($allCustomers->avg('wallet_balance') ?? 0, 2),
            'max_balance' => $allCustomers->max('wallet_balance') ?? 0,
            'min_balance' => $allCustomers->min('wallet_balance') ?? 0,
        ];

        return view('admin-views.customer.wallet.balance-summary', compact('customers', 'statistics'));
    }

    /**
     * Print Balance Summary Report (FIXED VERSION)
     * 
     * @param Request $request
     * @return mixed
     */
    public function printBalanceSummary(Request $request)
    {
        $query = $this->user->where('user_type', null)
            ->withCount('orders')
            ->with([
                'walletTransactions' => function ($q) {
                    $q->latest()->limit(1);
                }
            ]);

        // Apply same filters as balance summary
        if ($request->search) {
            $key = explode(' ', $request->search);
            $query->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('name', 'like', "%{$value}%")
                        ->orWhere('phone', 'like', "%{$value}%")
                        ->orWhere('email', 'like', "%{$value}%");
                }
            });
        }

        if ($request->balance_filter) {
            switch ($request->balance_filter) {
                case 'positive':
                    $query->where('wallet_balance', '>', 0);
                    break;
                case 'zero':
                    $query->where('wallet_balance', '=', 0);
                    break;
                case 'negative':
                    $query->where('wallet_balance', '<', 0);
                    break;
            }
        }

        if ($request->min_balance !== null && $request->min_balance !== '') {
            $query->where('wallet_balance', '>=', $request->min_balance);
        }

        if ($request->max_balance !== null && $request->max_balance !== '') {
            $query->where('wallet_balance', '<=', $request->max_balance);
        }

        $sortBy = $request->sort_by ?? 'name';
        $sortOrder = $request->sort_order ?? 'asc';

        if ($sortBy === 'balance') {
            $query->orderBy('wallet_balance', $sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $customers = $query->get();

        $statistics = [
            'total_customers' => $customers->count(),
            'total_balance' => $customers->sum('wallet_balance') ?? 0,
            'positive_balance_count' => $customers->where('wallet_balance', '>', 0)->count(),
            'positive_balance_sum' => $customers->where('wallet_balance', '>', 0)->sum('wallet_balance') ?? 0,
            'zero_balance_count' => $customers->where('wallet_balance', '=', 0)->count(),
            'negative_balance_count' => $customers->where('wallet_balance', '<', 0)->count(),
            'negative_balance_sum' => $customers->where('wallet_balance', '<', 0)->sum('wallet_balance') ?? 0,
            'average_balance' => round($customers->avg('wallet_balance') ?? 0, 2),
        ];

        $businessInfo = BusinessSetting::whereIn('key', ['restaurant_name', 'restaurant_phone', 'restaurant_email', 'restaurant_address'])
            ->pluck('value', 'key')
            ->toArray();

        return view('admin-views.customer.wallet.print-balance-summary', compact('customers', 'statistics', 'businessInfo'));
    }

    /**
     * Customer Wallet Statement (FIXED VERSION)
     * Shows detailed transaction history for a specific customer
     * 
     * @param Request $request
     * @param int $customer_id
     * @return Renderable
     */
    public function customerStatement(Request $request, $customer_id): Renderable
    {
        $customer = $this->user->withCount('orders')->findOrFail($customer_id);

        $query = $this->walletTransaction->where('user_id', $customer_id);

        // Date range filter
        $fromDate = null;
        $toDate = null;

        if ($request->from && $request->to) {
            $fromDate = Carbon::parse($request->from)->startOfDay();
            $toDate = Carbon::parse($request->to)->endOfDay();

            $query->whereBetween('created_at', [$fromDate, $toDate]);
        }

        // Transaction type filter
        if ($request->transaction_type) {
            $query->where('transaction_type', $request->transaction_type);
        }

        $transactions = $query->orderBy('created_at', 'desc')
            ->paginate(Helpers::getPagination());

        // Calculate period statistics
        $periodQuery = $this->walletTransaction->where('user_id', $customer_id);

        if ($fromDate && $toDate) {
            $periodQuery->whereBetween('created_at', [$fromDate, $toDate]);
        }

        $periodTransactions = $periodQuery->get();

        $periodStats = [
            'opening_balance' => $this->calculateOpeningBalance($customer_id, $fromDate),
            'total_credit' => $periodTransactions->sum('credit') ?? 0,
            'total_debit' => $periodTransactions->sum('debit') ?? 0,
            'closing_balance' => $customer->wallet_balance ?? 0,
            'transaction_count' => $transactions->total(),
        ];

        // Transaction type breakdown
        $transactionBreakdown = $this->walletTransaction
            ->where('user_id', $customer_id)
            ->when($fromDate && $toDate, function ($q) use ($fromDate, $toDate) {
                $q->whereBetween('created_at', [$fromDate, $toDate]);
            })
            ->select(
                'transaction_type',
                DB::raw('SUM(credit) as total_credit'),
                DB::raw('SUM(debit) as total_debit'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('transaction_type')
            ->get();

        return view('admin-views.customer.wallet.customer-statement', compact(
            'customer',
            'transactions',
            'periodStats',
            'transactionBreakdown',
            'fromDate',
            'toDate'
        ));
    }

    /**
     * Print Customer Statement (FIXED VERSION)
     * 
     * @param Request $request
     * @param int $customer_id
     * @return mixed
     */
    public function printCustomerStatement(Request $request, $customer_id)
    {
        $customer = $this->user->withCount('orders')->findOrFail($customer_id);

        $query = $this->walletTransaction->where('user_id', $customer_id);

        $fromDate = null;
        $toDate = null;

        if ($request->from && $request->to) {
            $fromDate = Carbon::parse($request->from)->startOfDay();
            $toDate = Carbon::parse($request->to)->endOfDay();

            $query->whereBetween('created_at', [$fromDate, $toDate]);
        }

        if ($request->transaction_type) {
            $query->where('transaction_type', $request->transaction_type);
        }

        $transactions = $query->orderBy('created_at', 'desc')->get();

        $periodStats = [
            'opening_balance' => $this->calculateOpeningBalance($customer_id, $fromDate),
            'total_credit' => $transactions->sum('credit') ?? 0,
            'total_debit' => $transactions->sum('debit') ?? 0,
            'closing_balance' => $customer->wallet_balance ?? 0,
            'transaction_count' => $transactions->count(),
        ];

        $transactionBreakdown = $transactions
            ->groupBy('transaction_type')
            ->map(function ($group) {
                return [
                    'type' => $group->first()->transaction_type,
                    'total_credit' => $group->sum('credit'),
                    'total_debit' => $group->sum('debit'),
                    'count' => $group->count(),
                ];
            });

        $businessInfo = BusinessSetting::whereIn('key', ['restaurant_name', 'restaurant_phone', 'restaurant_email', 'restaurant_address'])
            ->pluck('value', 'key')
            ->toArray();

        return view('admin-views.customer.wallet.print-customer-statement', compact(
            'customer',
            'transactions',
            'periodStats',
            'transactionBreakdown',
            'businessInfo',
            'fromDate',
            'toDate'
        ));
    }

    /**
     * Calculate opening balance for a customer at a specific date (FIXED VERSION)
     * 
     * @param int $customer_id
     * @param Carbon|null $date
     * @return float
     */
    private function calculateOpeningBalance($customer_id, $date = null): float
    {
        if (!$date) {
            return 0;
        }

        $transactions = $this->walletTransaction
            ->where('user_id', $customer_id)
            ->where('created_at', '<', $date)
            ->selectRaw('SUM(credit) as total_credit, SUM(debit) as total_debit')
            ->first();

        $credit = $transactions->total_credit ?? 0;
        $debit = $transactions->total_debit ?? 0;

        return $credit - $debit;
    }

    /**
     * Export balance summary to Excel (FIXED VERSION)
     * 
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportBalanceSummary(Request $request)
    {
        $query = $this->user->where('user_type', null)
            ->withCount('orders');

        // Apply filters
        if ($request->search) {
            $key = explode(' ', $request->search);
            $query->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('name', 'like', "%{$value}%")
                        ->orWhere('phone', 'like', "%{$value}%")
                        ->orWhere('email', 'like', "%{$value}%");
                }
            });
        }

        if ($request->balance_filter) {
            switch ($request->balance_filter) {
                case 'positive':
                    $query->where('wallet_balance', '>', 0);
                    break;
                case 'zero':
                    $query->where('wallet_balance', '=', 0);
                    break;
                case 'negative':
                    $query->where('wallet_balance', '<', 0);
                    break;
            }
        }

        if ($request->min_balance !== null && $request->min_balance !== '') {
            $query->where('wallet_balance', '>=', $request->min_balance);
        }

        if ($request->max_balance !== null && $request->max_balance !== '') {
            $query->where('wallet_balance', '<=', $request->max_balance);
        }

        $customers = $query->orderBy('name')->get();

        $data = [];
        foreach ($customers as $index => $customer) {
            $lastTransaction = $customer->walletTransactions()->latest()->first();

            $data[] = [
                'SL' => $index + 1,
                'Name' => $customer->name,
                'Phone' => $customer->phone,
                'Email' => $customer->email ?? 'N/A',
                'Wallet Balance' => number_format($customer->wallet_balance ?? 0, 2),
                'Total Orders' => $customer->orders_count ?? 0,
                'Last Transaction' => $lastTransaction ?
                    date('Y-m-d H:i:s', strtotime($lastTransaction->created_at)) : 'N/A',
            ];
        }

        return (new \Rap2hpoutre\FastExcel\FastExcel($data))
            ->download('wallet_balance_summary_' . date('Y-m-d') . '.xlsx');
    }

    /**
     * Export customer statement to Excel (FIXED VERSION)
     * 
     * @param Request $request
     * @param int $customer_id
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportCustomerStatement(Request $request, $customer_id)
    {
        $customer = $this->user->findOrFail($customer_id);

        $query = $this->walletTransaction->where('user_id', $customer_id);

        if ($request->from && $request->to) {
            $fromDate = Carbon::parse($request->from)->startOfDay();
            $toDate = Carbon::parse($request->to)->endOfDay();

            $query->whereBetween('created_at', [$fromDate, $toDate]);
        }

        if ($request->transaction_type) {
            $query->where('transaction_type', $request->transaction_type);
        }

        $transactions = $query->orderBy('created_at', 'desc')->get();

        $data = [];
        foreach ($transactions as $index => $transaction) {
            $data[] = [
                'SL' => $index + 1,
                'Transaction ID' => $transaction->transaction_id,
                'Date' => date('Y-m-d H:i:s', strtotime($transaction->created_at)),
                'Type' => translate($transaction->transaction_type),
                'Reference' => $transaction->reference ?? 'N/A',
                'Credit' => number_format($transaction->credit ?? 0, 2),
                'Debit' => number_format($transaction->debit ?? 0, 2),
                'Balance' => number_format($transaction->balance ?? 0, 2),
            ];
        }
        return (new \Rap2hpoutre\FastExcel\FastExcel($data))
            ->download('wallet_statement_' . str_replace(' ', '_', $customer->name) . '_' . date('Y-m-d') . '.xlsx');
    }

    /**
     * @param $id
     * @return Renderable
     */
    public function print_vouchers($id): Renderable
    {
        $transaction = $this->walletTransaction->findOrFail($id);
        return view('admin-views.customer.wallet.print-voucher', compact('transaction'));
    }
}
