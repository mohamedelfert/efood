<?php

namespace App\Http\Controllers\Api\V1;

use App\User;
use Exception;
use App\Mail\TransferOtp;
use App\Model\WalletBonus;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\Model\BusinessSetting;
use App\Model\WalletTransaction;
use App\Services\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\CentralLogics\CustomerLogic;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Services\NotificationService;
use App\Services\PaymentGatewayHelper;
use App\Mail\MoneyTransferNotification;
use Illuminate\Support\Facades\Validator;
use App\Services\Payment\PaymentGatewayFactory;

class CustomerWalletController extends Controller
{
    private NotificationService $notificationService;

    public function __construct(
        private User              $user,
        private BusinessSetting   $businessSetting,
        private WalletTransaction $walletTransaction,
        private WalletBonus       $walletBonus,
        NotificationService       $notificationService,
        private WhatsAppService   $whatsapp
    ){
        $this->notificationService = $notificationService;
        $this->whatsapp = $whatsapp;
    }

    public function addFund(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'exists:users,id',
            'amount' => 'numeric|min:0|not_in:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)]);
        }

        $walletTransaction = CustomerLogic::create_wallet_transaction($request->customer_id, $request->amount, 'add_fund_by_admin', $request->referance);

        if ($walletTransaction) {
            return response()->json([
                    'success' => true,
                    'message' => translate('Payment initiated successfully'),
                ], 200);
        }

        return response()->json(['errors' => [
            'message' => translate('failed_to_create_transaction')
        ]], 200);
    }

    /**
     * Transfer loyalty points to wallet
     */
    public function transferLoyaltyPointToWallet(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'point' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $user = $this->user->find($request->user()->id);
        if ($request->point > $user->point) {
            return response()->json(['errors' => [['code' => 'wallet', 'message' => translate('Insufficient loyalty points')]]], 401);
        }

        $minimumPoint = $this->businessSetting->where('key', 'loyalty_point_minimum_point')->first()->value ?? 0;
        if ($request->point < $minimumPoint) {
            return response()->json(['errors' => [['code' => 'wallet', 'message' => translate('Minimum point requirement not met')]]], 401);
        }

        $loyaltyPointExchangeRate = $this->businessSetting->where('key', 'loyalty_point_exchange_rate')->first()->value ?? 1;
        $loyaltyAmount = $request->point / $loyaltyPointExchangeRate;

        DB::beginTransaction();
        try {
            $transactionId = 'LP_' . time() . '_' . $user->id;
            
            $this->walletTransaction->create([
                'user_id' => $user->id,
                'transaction_id' => $transactionId,
                'credit' => $loyaltyAmount,
                'debit' => 0,
                'transaction_type' => 'loyalty_point_to_wallet',
                'reference' => 'Loyalty points conversion',
                'status' => 'completed',
                'balance' => $user->wallet_balance + $loyaltyAmount,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $user->decrement('point', $request->point);
            $user->increment('wallet_balance', $loyaltyAmount);
            
            DB::commit();

            // ✅ Send notification
            $this->notificationService->sendLoyaltyConversionNotification($user->fresh(), [
                'transaction_id' => $transactionId,
                'points_used' => $request->point,
                'converted_amount' => $loyaltyAmount,
                'currency' => 'SAR',
            ]);

            return response()->json([
                'message' => translate('Transfer successful'),
                'transaction_id' => $transactionId,
                'converted_amount' => $loyaltyAmount,
                'points_used' => $request->point,
                'new_balance' => $user->fresh()->wallet_balance,
                'remaining_points' => $user->fresh()->point,
            ], 200);
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Loyalty transfer failed: ' . $e->getMessage());
            return response()->json(['errors' => [['code' => 'server_error', 'message' => translate('Transfer failed')]]], 500);
        }
    }

    /**
     * Get wallet transactions with enhanced filtering
     */
    public function walletTransactions(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required|integer|min:1|max:100',
            'offset' => 'required|integer|min:0',
            'transaction_type' => 'nullable|string',
            'status' => 'nullable|in:pending,completed,failed,cancelled',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $userId = $request->user()->id;
        $query = $this->walletTransaction->where('user_id', $userId);

        // Apply filters
        if ($request->has('transaction_type') && $request->transaction_type) {
            $query->where('transaction_type', $request->transaction_type);
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('start_date') && $request->start_date) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->has('end_date') && $request->end_date) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $paginator = $query->latest()->paginate($request->limit, ['*'], 'page', $request->offset);

        // Calculate summary for the filtered results
        $summary = [
            'total_credit' => $query->sum('credit'),
            'total_debit' => $query->sum('debit'),
            'pending_amount' => $query->where('status', 'pending')->sum('credit'),
        ];

        $data = [
            'total_size' => $paginator->total(),
            'limit' => $request->limit,
            'offset' => $request->offset,
            'summary' => $summary,
            'data' => $paginator->items()
        ];

        return response()->json($data, 200);
    }

    /**
     * Get wallet bonus list
     */
    public function walletBonusList(): JsonResponse
    {
        $bonuses = $this->walletBonus->active()
            ->where('start_date', '<=', now()->format('Y-m-d'))
            ->where('end_date', '>=', now()->format('Y-m-d'))
            ->latest()
            ->get();

        return response()->json([
            'bonuses' => $bonuses,
            'count' => $bonuses->count()
        ], 200);
    }

    /**
     * Get comprehensive wallet balance information
     */
    public function getWalletBalance(Request $request): JsonResponse
    {
        $user = $this->user->find($request->user()->id);
        
        $totalEarned = $this->walletTransaction
            ->where('user_id', $user->id)
            ->where('credit', '>', 0)
            ->sum('credit');
            
        $totalSpent = $this->walletTransaction
            ->where('user_id', $user->id)
            ->where('debit', '>', 0)
            ->sum('debit');
            
        $pendingTransactions = $this->walletTransaction
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->count();

        $pendingAmount = $this->walletTransaction
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->sum('credit');

        // Get recent transactions
        $recentTransactions = $this->walletTransaction
            ->where('user_id', $user->id)
            ->latest()
            ->limit(5)
            ->get();

        try {
            $currency = Helpers::currency_symbol();
        } catch (Exception $e) {
            $currency = 'SAR';
        }

        $data = [
            'balance' => (float) $user->wallet_balance,
            'total_earned' => (float) $totalEarned,
            'total_spent' => (float) $totalSpent,
            'pending_transactions' => $pendingTransactions,
            'pending_amount' => (float) $pendingAmount,
            'loyalty_points' => (float) $user->point,
            'currency' => $currency,
            'recent_transactions' => $recentTransactions,
            'last_updated' => now()->toDateTimeString(),
        ];

        return response()->json($data, 200);
    }

    /**
     * Get available payment methods for wallet top-up
     */
    public function getPaymentMethods(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1|max:50000',
            'currency' => 'sometimes|string|in:SAR,EGP,USD,YER',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $amount = $request->amount;
        $currency = $request->currency ?? 'SAR';

        try {
            // Use Factory to get methods from all gateways
            $gateways = ['paymob', 'qib'];
            $allMethods = [];

            foreach ($gateways as $gatewayType) {
                $gateway = PaymentGatewayFactory::create($gatewayType);
                if ($gateway) {
                    $methods = $gateway->getPaymentMethods();
                    foreach ($methods as &$method) {
                        $method['gateway'] = $gatewayType;
                        $method['supported_currencies'] = $gatewayType === 'paymob' ? ['EGP', 'SAR', 'USD'] : ['YER', 'SAR', 'USD'];
                        $method['min_amount'] = $gatewayType === 'paymob' ? env('PAYMOB_MIN_AMOUNT', 1) : 1;
                        $method['max_amount'] = $gatewayType === 'paymob' ? env('PAYMOB_MAX_AMOUNT', 100000) : 50000;
                        $method['fees'] = 0; // Or calculate based on settings
                    }
                    $allMethods = array_merge($allMethods, $methods);
                }
            }

            // Add loyalty points method
            $allMethods[] = [
                'gateway' => 'loyalty_points',
                'name' => 'Loyalty Points',
                'name_ar' => 'نقاط الولاء',
                'description' => 'Convert loyalty points to wallet balance',
                'description_ar' => 'تحويل نقاط الولاء إلى رصيد المحفظة',
                'icon' => 'loyalty',
                'supported_currencies' => ['SAR'],
                'available_points' => (float) $request->user()->point,
                'conversion_rate' => (float) ($this->businessSetting->where('key', 'loyalty_point_exchange_rate')->first()->value ?? 1),
                'fees' => 0
            ];

            return response()->json([
                'success' => true,
                'payment_methods' => $allMethods,
                'currency' => $currency,
                'amount' => $amount
            ], 200);

        } catch (Exception $e) {
            Log::error('Get payment methods failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'errors' => [['code' => 'payment_methods_error', 'message' => $e->getMessage()]]
            ], 500);
        }
    }

    /**
     * Process wallet top-up 
     */
    // public function topUpWallet(Request $request): JsonResponse
    // {
    //     $validator = Validator::make($request->all(), [
    //         'amount' => 'required|numeric|min:1|max:50000',
    //         'gateway' => 'required|in:paymob,qib,loyalty_points',
    //         'currency' => 'sometimes|string|in:SAR,USD,EUR,EGP,YER',
    //         'callback_url' => 'sometimes|url',
            
    //         // QIB specific fields
    //         'payment_CustomerNo' => 'required_if:gateway,qib|string',
    //         'payment_DestNation' => 'required_if:gateway,qib|integer',
    //         'payment_Code' => 'required_if:gateway,qib|integer',
            
    //         // Loyalty points field
    //         'points' => 'required_if:gateway,loyalty_points|integer|min:1',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json(['errors' => Helpers::error_processor($validator)], 403);
    //     }

    //     $user = $request->user();
    //     $amount = $request->amount;
    //     $gateway = $request->gateway;
    //     $currency = $request->currency ?? 'EGP';

    //     // Check daily limits
    //     $dailyLimit = $this->businessSetting->where('key', 'wallet_daily_top_up_limit')->first()->value ?? 50000;
    //     $todayTopUps = $this->walletTransaction
    //         ->where('user_id', $user->id)
    //         ->where('transaction_type', 'add_fund')
    //         ->whereDate('created_at', today())
    //         ->sum('credit');

    //     if (($todayTopUps + $amount) > $dailyLimit) {
    //         return response()->json([
    //             'errors' => [['code' => 'limit_exceeded', 'message' => translate('Daily top-up limit exceeded')]]
    //         ], 400);
    //     }

    //     // Handle loyalty points top-up
    //     if ($gateway === 'loyalty_points') {
    //         return $this->handleLoyaltyTopUp($request, $user);
    //     }

    //     try {
    //         $gatewayInstance = PaymentGatewayFactory::create($gateway);
    //         if (!$gatewayInstance) {
    //             return response()->json([
    //                 'success' => false,
    //                 'errors' => [['code' => 'invalid_gateway', 'message' => 'Unsupported gateway']]
    //             ], 400);
    //         }

    //         $transactionId = 'PAY_' . time() . '_' . $user->id;
    //         $callbackUrl = $gateway === 'qib' ? null : (env('APP_URL') . config('payment.callback_url') ?? null);

    //         $data = [
    //             'gateway' => $gateway,
    //             'amount' => $amount,
    //             'currency' => $currency,
    //             'purpose' => 'wallet_topup',
    //             'customer_data' => [
    //                 'user_id' => $user->id,
    //                 'name' => $user->name,
    //                 'email' => $user->email,
    //                 'phone' => $user->phone,
    //             ],
    //             'callback_url' => $callbackUrl,
    //         ];

    //         // Add QIB-specific fields
    //         if ($gateway === 'qib') {
    //             $data['payment_CustomerNo'] = $request->payment_CustomerNo;
    //             $data['payment_DestNation'] = $request->payment_DestNation;
    //             $data['payment_Code'] = $request->payment_Code;
    //         }

    //         $response = $gatewayInstance->requestPayment($data);

    //         if (isset($response['status']) && $response['status']) {
    //             $currentBalance = $user->wallet_balance;
                
    //             $metadata = [
    //                 'gateway' => $gateway,
    //                 'currency' => $currency,
    //             ];

    //             if ($gateway === 'qib') {
    //                 $metadata = array_merge($metadata, [
    //                     'payment_CustomerNo' => $request->payment_CustomerNo,
    //                     'payment_DestNation' => $request->payment_DestNation,
    //                     'payment_Code' => $request->payment_Code,
    //                 ]);
    //             } else {
    //                 $metadata = array_merge($metadata, [
    //                     'paymob_order_id' => (string) ($response['order_id'] ?? null),
    //                     'payment_key' => $response['payment_key'] ?? null,
    //                     'paymob_transaction_id' => (string) ($response['id'] ?? null),
    //                 ]);
    //             }

    //             try {
    //                 $this->walletTransaction->create([
    //                     'user_id' => $user->id,
    //                     'transaction_id' => $transactionId,
    //                     'credit' => $amount,
    //                     'debit' => 0,
    //                     'transaction_type' => 'add_fund',
    //                     'reference' => 'Top-up via ' . $gateway,
    //                     'status' => 'pending',
    //                     'gateway' => $gateway,
    //                     'balance' => $currentBalance,
    //                     'admin_bonus' => $request->admin_bonus ? json_encode($request->admin_bonus) : null,
    //                     'metadata' => json_encode($metadata),
    //                     'created_at' => now(),
    //                     'updated_at' => now(),
    //                 ]);
    //                 Log::info('WalletTransaction created successfully', [
    //                     'transaction_id' => $transactionId,
    //                     'user_id' => $user->id,
    //                     'metadata' => $metadata
    //                 ]);
    //             } catch (\Exception $e) {
    //                 Log::error('Failed to create WalletTransaction', [
    //                     'error' => $e->getMessage(),
    //                     'trace' => $e->getTraceAsString(),
    //                     'data' => [
    //                         'transaction_id' => $transactionId,
    //                         'user_id' => $user->id,
    //                         'metadata' => $metadata
    //                     ]
    //                 ]);
    //                 return response()->json([
    //                     'success' => false,
    //                     'message' => 'Failed to create transaction record'
    //                 ], 500);
    //             }

    //             // Send OTP notifications for QIB
    //             if ($gateway === 'qib') {
    //                 try {
    //                     $notificationService = app(\App\Services\QIBNotificationService::class);
    //                     $notificationService->sendOTPNotification($user, [
    //                         'transaction_id' => $transactionId,
    //                         'amount' => $amount,
    //                         'currency' => $currency,
    //                         'gateway' => 'QIB Bank',
    //                     ]);
    //                 } catch (\Exception $e) {
    //                     Log::warning('QIB OTP notification failed', [
    //                         'error' => $e->getMessage(),
    //                         'transaction_id' => $transactionId,
    //                     ]);
    //                 }
    //             }

    //             // Get gateway type information
    //             $gatewayInfo = PaymentGatewayHelper::getGatewayInfo($gateway);

    //             $responseData = [
    //                 'success' => true,
    //                 'message' => translate($gateway === 'qib' ? 'OTP sent to your WhatsApp' : 'Payment initiated successfully'),
    //                 'transaction_id' => $transactionId,
    //                 'gateway' => $gateway,
    //                 'amount' => $amount,
    //                 'currency' => $currency,
    //                 'requires_online_url' => $gatewayInfo['requires_online_url'],
    //                 'payment_type' => $gatewayInfo['payment_type'],
    //             ];

    //             if ($gateway === 'qib') {
    //                 $responseData['requires_otp'] = true;
    //                 $responseData['otp_sent'] = true;
    //             } else {
    //                 $responseData['payment_url'] = $response['iframe_url'] ?? null;
    //                 $responseData['requires_otp'] = false;
    //                 $responseData['redirect_required'] = $gatewayInfo['requires_online_url']; // Use flag instead of hardcoded true
    //             }

    //             return response()->json($responseData, 200);
    //         }

    //         return response()->json([
    //             'success' => false,
    //             'errors' => $response['errors'] ?? [['code' => 'payment_failed', 'message' => 'Payment initiation failed']]
    //         ], 400);

    //     } catch (Exception $e) {
    //         Log::error('Wallet top-up failed', [
    //             'user_id' => $user->id,
    //             'amount' => $amount,
    //             'gateway' => $gateway,
    //             'error' => $e->getMessage(),
    //         ]);
    //         return response()->json([
    //             'success' => false,
    //             'errors' => [['code' => 'server_error', 'message' => translate('Top-up failed. Please try again.')]]
    //         ], 500);
    //     }
    // }

    // public function topUpWallet(Request $request): JsonResponse
    // {
    //     $validator = Validator::make($request->all(), [
    //         'amount' => 'required|numeric|min:1|max:50000',
    //         'gateway' => 'required|in:paymob,qib,loyalty_points',
    //         'currency' => 'sometimes|string|in:SAR,USD,EUR,EGP,YER',
    //         'callback_url' => 'sometimes|url',
            
    //         // QIB specific fields
    //         'payment_CustomerNo' => 'required_if:gateway,qib|string',
    //         'payment_DestNation' => 'required_if:gateway,qib|integer',
    //         'payment_Code' => 'required_if:gateway,qib|integer',
            
    //         // Loyalty points field
    //         'points' => 'required_if:gateway,loyalty_points|integer|min:1',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json(['errors' => Helpers::error_processor($validator)], 403);
    //     }

    //     $user = $request->user();
    //     $amount = $request->amount;
    //     $gateway = $request->gateway;
    //     $currency = $request->currency ?? 'EGP';

    //     // Check daily limits
    //     $dailyLimit = $this->businessSetting->where('key', 'wallet_daily_top_up_limit')->first()->value ?? 50000;
    //     $todayTopUps = $this->walletTransaction
    //         ->where('user_id', $user->id)
    //         ->where('transaction_type', 'add_fund')
    //         ->whereDate('created_at', today())
    //         ->sum('credit');

    //     if (($todayTopUps + $amount) > $dailyLimit) {
    //         return response()->json([
    //             'errors' => [['code' => 'limit_exceeded', 'message' => translate('Daily top-up limit exceeded')]]
    //         ], 400);
    //     }

    //     // Handle loyalty points top-up
    //     if ($gateway === 'loyalty_points') {
    //         return $this->handleLoyaltyTopUp($request, $user);
    //     }

    //     try {
    //         $gatewayInstance = PaymentGatewayFactory::create($gateway);
    //         if (!$gatewayInstance) {
    //             return response()->json([
    //                 'success' => false,
    //                 'errors' => [['code' => 'invalid_gateway', 'message' => 'Unsupported gateway']]
    //             ], 400);
    //         }

    //         $transactionId = 'PAY_' . time() . '_' . $user->id;
    //         $callbackUrl = $gateway === 'qib' ? null : (env('APP_URL') . config('payment.callback_url') ?? null);

    //         $data = [
    //             'gateway' => $gateway,
    //             'amount' => $amount,
    //             'currency' => $currency,
    //             'purpose' => 'wallet_topup',
    //             'customer_data' => [
    //                 'user_id' => $user->id,
    //                 'name' => $user->name,
    //                 'email' => $user->email,
    //                 'phone' => $user->phone,
    //             ],
    //             'callback_url' => $callbackUrl,
    //         ];

    //         // Add QIB-specific fields
    //         if ($gateway === 'qib') {
    //             $data['payment_CustomerNo'] = $request->payment_CustomerNo;
    //             $data['payment_DestNation'] = $request->payment_DestNation;
    //             $data['payment_Code'] = $request->payment_Code;
    //         }

    //         $response = $gatewayInstance->requestPayment($data);

    //         if (isset($response['status']) && $response['status']) {
    //             $currentBalance = $user->wallet_balance;
                
    //             $metadata = [
    //                 'gateway' => $gateway,
    //                 'currency' => $currency,
    //             ];

    //             if ($gateway === 'qib') {
    //                 $metadata = array_merge($metadata, [
    //                     'payment_CustomerNo' => $request->payment_CustomerNo,
    //                     'payment_DestNation' => $request->payment_DestNation,
    //                     'payment_Code' => $request->payment_Code,
    //                 ]);
    //             } else {
    //                 $metadata = array_merge($metadata, [
    //                     'paymob_order_id' => (string) ($response['order_id'] ?? null),
    //                     'payment_key' => $response['payment_key'] ?? null,
    //                     'paymob_transaction_id' => (string) ($response['id'] ?? null),
    //                 ]);
    //             }

    //             try {
    //                 $this->walletTransaction->create([
    //                     'user_id' => $user->id,
    //                     'transaction_id' => $transactionId,
    //                     'credit' => $amount,
    //                     'debit' => 0,
    //                     'transaction_type' => 'add_fund',
    //                     'reference' => 'Top-up via ' . $gateway,
    //                     'status' => 'pending',
    //                     'gateway' => $gateway,
    //                     'balance' => $currentBalance,
    //                     'admin_bonus' => $request->admin_bonus ? json_encode($request->admin_bonus) : null,
    //                     'metadata' => json_encode($metadata),
    //                     'created_at' => now(),
    //                     'updated_at' => now(),
    //                 ]);
    //                 Log::info('WalletTransaction created successfully', [
    //                     'transaction_id' => $transactionId,
    //                     'user_id' => $user->id,
    //                     'metadata' => $metadata
    //                 ]);
    //             } catch (\Exception $e) {
    //                 Log::error('Failed to create WalletTransaction', [
    //                     'error' => $e->getMessage(),
    //                     'trace' => $e->getTraceAsString(),
    //                     'data' => [
    //                         'transaction_id' => $transactionId,
    //                         'user_id' => $user->id,
    //                         'metadata' => $metadata
    //                     ]
    //                 ]);
    //                 return response()->json([
    //                     'success' => false,
    //                     'message' => 'Failed to create transaction record'
    //                 ], 500);
    //             }

    //             // Send OTP notifications for QIB
    //             if ($gateway === 'qib') {
    //                 try {
    //                     $notificationService = app(\App\Services\QIBNotificationService::class);
    //                     $notificationService->sendOTPNotification($user, [
    //                         'transaction_id' => $transactionId,
    //                         'amount' => $amount,
    //                         'currency' => $currency,
    //                         'gateway' => 'QIB Bank',
    //                     ]);
    //                 } catch (\Exception $e) {
    //                     Log::warning('QIB OTP notification failed', [
    //                         'error' => $e->getMessage(),
    //                         'transaction_id' => $transactionId,
    //                     ]);
    //                 }
    //             }

    //             // Get gateway type information
    //             $gatewayInfo = PaymentGatewayHelper::getGatewayInfo($gateway);

    //             $responseData = [
    //                 'success' => true,
    //                 'message' => translate($gateway === 'qib' ? 'OTP sent to your WhatsApp' : 'Payment initiated successfully'),
    //                 'transaction_id' => $transactionId,
    //                 'gateway' => $gateway,
    //                 'amount' => $amount,
    //                 'currency' => $currency,
    //                 'requires_online_url' => $gatewayInfo['requires_online_url'],
    //                 'payment_type' => $gatewayInfo['payment_type'],
    //             ];

    //             if ($gateway === 'qib') {
    //                 $responseData['requires_otp'] = true;
    //                 $responseData['otp_sent'] = true;
    //             } else {
    //                 $responseData['payment_url'] = $response['iframe_url'] ?? null;
    //                 $responseData['requires_otp'] = false;
    //                 $responseData['redirect_required'] = $gatewayInfo['requires_online_url']; // Use flag instead of hardcoded true
    //             }

    //             return response()->json($responseData, 200);
    //         }

    //         return response()->json([
    //             'success' => false,
    //             'errors' => $response['errors'] ?? [['code' => 'payment_failed', 'message' => 'Payment initiation failed']]
    //         ], 400);

    //     } catch (Exception $e) {
    //         Log::error('Wallet top-up failed', [
    //             'user_id' => $user->id,
    //             'amount' => $amount,
    //             'gateway' => $gateway,
    //             'error' => $e->getMessage(),
    //         ]);
    //         return response()->json([
    //             'success' => false,
    //             'errors' => [['code' => 'server_error', 'message' => translate('Top-up failed. Please try again.')]]
    //         ], 500);
    //     }
    // }
    
    public function topUpWallet(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1|max:50000',
            'gateway' => 'required|in:paymob,qib,loyalty_points',
            'currency' => 'sometimes|string|in:SAR,USD,EUR,EGP,YER',
            'callback_url' => 'sometimes|url',
            
            // QIB specific fields
            'payment_CustomerNo' => 'required_if:gateway,qib|string',
            'payment_DestNation' => 'required_if:gateway,qib|integer',
            'payment_Code' => 'required_if:gateway,qib|integer',
            
            // Loyalty points field
            'points' => 'required_if:gateway,loyalty_points|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $user = $request->user();
        $amount = $request->amount;
        $gateway = $request->gateway;
        $currency = $request->currency ?? 'EGP';

        // Check daily limits
        $dailyLimit = $this->businessSetting->where('key', 'wallet_daily_top_up_limit')->first()->value ?? 50000;
        $todayTopUps = $this->walletTransaction
            ->where('user_id', $user->id)
            ->where('transaction_type', 'add_fund')
            ->whereDate('created_at', today())
            ->sum('credit');

        if (($todayTopUps + $amount) > $dailyLimit) {
            return response()->json([
                'errors' => [['code' => 'limit_exceeded', 'message' => translate('Daily top-up limit exceeded')]]
            ], 400);
        }

        // Handle loyalty points top-up
        if ($gateway === 'loyalty_points') {
            return $this->handleLoyaltyTopUp($request, $user);
        }

        try {
            $gatewayInstance = PaymentGatewayFactory::create($gateway);
            if (!$gatewayInstance) {
                return response()->json([
                    'success' => false,
                    'errors' => [['code' => 'invalid_gateway', 'message' => 'Unsupported gateway']]
                ], 400);
            }

            $transactionId = 'PAY_' . time() . '_' . $user->id;
            $callbackUrl = $gateway === 'qib' ? null : (env('APP_URL') . config('payment.callback_url') ?? null);

            $data = [
                'gateway' => $gateway,
                'amount' => $amount,
                'currency' => $currency,
                'purpose' => 'wallet_topup',
                'customer_data' => [
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                ],
                'callback_url' => $callbackUrl,
            ];

            // Add QIB-specific fields
            if ($gateway === 'qib') {
                $data['payment_CustomerNo'] = $request->payment_CustomerNo;
                $data['payment_DestNation'] = $request->payment_DestNation;
                $data['payment_Code'] = $request->payment_Code;
            }

            $response = $gatewayInstance->requestPayment($data);

            if (isset($response['status']) && $response['status']) {
                $currentBalance = $user->wallet_balance;
                
                // ✅ BUILD METADATA WITH STRING CASTING
                $metadata = [
                    'gateway' => $gateway,
                    'currency' => $currency,
                ];

                if ($gateway === 'qib') {
                    $metadata = array_merge($metadata, [
                        'payment_CustomerNo' => $request->payment_CustomerNo,
                        'payment_DestNation' => $request->payment_DestNation,
                        'payment_Code' => $request->payment_Code,
                    ]);
                } else {
                    // ✅ For Paymob - Cast IDs to strings
                    // Note: paymob_transaction_id comes later in callback
                    $metadata = array_merge($metadata, [
                        'paymob_order_id' => (string)($response['order_id'] ?? ''),
                        'payment_key' => $response['payment_key'] ?? null,
                    ]);
                }

                try {
                    $this->walletTransaction->create([
                        'user_id' => $user->id,
                        'transaction_id' => $transactionId,
                        'credit' => $amount,
                        'debit' => 0,
                        'transaction_type' => 'add_fund',
                        'reference' => 'Top-up via ' . $gateway,
                        'status' => 'pending',
                        'gateway' => $gateway,
                        'balance' => $currentBalance,
                        'admin_bonus' => $request->admin_bonus ? json_encode($request->admin_bonus) : null,
                        'metadata' => json_encode($metadata),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    
                    Log::info('WalletTransaction created successfully', [
                        'transaction_id' => $transactionId,
                        'user_id' => $user->id,
                        'metadata' => $metadata
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to create WalletTransaction', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'data' => [
                            'transaction_id' => $transactionId,
                            'user_id' => $user->id,
                            'metadata' => $metadata
                        ]
                    ]);
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to create transaction record'
                    ], 500);
                }

                // Send OTP notifications for QIB
                if ($gateway === 'qib') {
                    try {
                        $notificationService = app(\App\Services\QIBNotificationService::class);
                        $notificationService->sendOTPNotification($user, [
                            'transaction_id' => $transactionId,
                            'amount' => $amount,
                            'currency' => $currency,
                            'gateway' => 'QIB Bank',
                        ]);
                    } catch (\Exception $e) {
                        Log::warning('QIB OTP notification failed', [
                            'error' => $e->getMessage(),
                            'transaction_id' => $transactionId,
                        ]);
                    }
                }

                // Get gateway type information
                $gatewayInfo = PaymentGatewayHelper::getGatewayInfo($gateway);

                $responseData = [
                    'success' => true,
                    'message' => translate($gateway === 'qib' ? 'OTP sent to your WhatsApp' : 'Payment initiated successfully'),
                    'transaction_id' => $transactionId,
                    'gateway' => $gateway,
                    'amount' => $amount,
                    'currency' => $currency,
                    'requires_online_url' => $gatewayInfo['requires_online_url'],
                    'payment_type' => $gatewayInfo['payment_type'],
                ];

                if ($gateway === 'qib') {
                    $responseData['requires_otp'] = true;
                    $responseData['otp_sent'] = true;
                } else {
                    $responseData['payment_url'] = $response['iframe_url'] ?? null;
                    $responseData['requires_otp'] = false;
                    $responseData['redirect_required'] = $gatewayInfo['requires_online_url'];
                }

                return response()->json($responseData, 200);
            }

            return response()->json([
                'success' => false,
                'errors' => $response['errors'] ?? [['code' => 'payment_failed', 'message' => 'Payment initiation failed']]
            ], 400);

        } catch (Exception $e) {
            Log::error('Wallet top-up failed', [
                'user_id' => $user->id,
                'amount' => $amount,
                'gateway' => $gateway,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'errors' => [['code' => 'server_error', 'message' => translate('Top-up failed. Please try again.')]]
            ], 500);
        }
    }

    /**
     * Handle loyalty points top-up (internal method)
     */
    private function handleLoyaltyTopUp(Request $request, User $user): JsonResponse
    {
        $points = $request->points;
        $loyaltyPointExchangeRate = $this->businessSetting->where('key', 'loyalty_point_exchange_rate')->first()->value ?? 1;
        $amount = $points / $loyaltyPointExchangeRate;

        if ($points > $user->point) {
            return response()->json(['errors' => [['code' => 'insufficient_points', 'message' => translate('Insufficient loyalty points')]]], 400);
        }

        DB::beginTransaction();
        try {
            $transactionId = 'LP_TOPUP_' . time() . '_' . $user->id;
            
            $this->walletTransaction->create([
                'user_id' => $user->id,
                'transaction_id' => $transactionId,
                'credit' => $amount,
                'debit' => 0,
                'transaction_type' => 'loyalty_topup',
                'reference' => 'Loyalty points top-up',
                'status' => 'completed',
                'balance' => $user->wallet_balance + $amount,
                'gateway' => 'loyalty_points',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $user->decrement('point', $points);
            $user->increment('wallet_balance', $amount);
            
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => translate('Top-up successful'),
                'transaction_id' => $transactionId,
                'amount' => $amount,
                'points_used' => $points,
                'new_balance' => $user->fresh()->wallet_balance,
                'remaining_points' => $user->fresh()->point,
            ], 200);
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Loyalty top-up failed: ' . $e->getMessage());
            return response()->json(['errors' => [['code' => 'server_error', 'message' => translate('Top-up failed')]]], 500);
        }
    }

    /**
     * Send OTP for wallet transfer
     */
    public function sendTransferOTP(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'receiver_phone' => 'required|string|exists:users,phone',
            'amount' => 'required|numeric|min:1',
            'note' => 'nullable|string|max:255',
            'pin' => 'required|string|min:4',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $sender = $request->user();
        $receiver = $this->user->where('phone', $request->receiver_phone)->first();
        $amount = $request->amount;

        if (!$sender->wallet_pin || !Hash::check($request->pin, $sender->wallet_pin)) {
            return response()->json([
                'errors' => [['code' => 'invalid_pin', 'message' => translate('Invalid PIN')]]
            ], 401);
        }

        if ($sender->wallet_balance < $amount) {
            return response()->json([
                'errors' => [['code' => 'insufficient_balance', 'message' => translate('Insufficient wallet balance')]]
            ], 400);
        }

        $dailyTransferLimit = Helpers::get_business_settings('wallet_daily_transfer_limit')['value'] ?? 25000;
        $todayTransfers = $this->walletTransaction
            ->where('user_id', $sender->id)
            ->where('transaction_type', 'transfer_sent')
            ->whereDate('created_at', today())
            ->sum('debit');

        if (($todayTransfers + $amount) > $dailyTransferLimit) {
            return response()->json([
                'errors' => [['code' => 'limit_exceeded', 'message' => translate('Daily transfer limit exceeded')]]
            ], 400);
        }

        try {
            $otp = mt_rand(100000, 999999);
            $otpExpiry = now()->addMinutes(5);

            $sender->update([
                'transfer_otp' => Hash::make($otp),
                'transfer_otp_expires_at' => $otpExpiry,
                'pending_transfer_data' => json_encode([
                    'receiver_id' => $receiver->id,
                    'receiver_phone' => $receiver->phone,
                    'amount' => $amount,
                    'note' => $request->note,
                    'receiver_name' => trim($receiver->name)
                ])
            ]);

            $this->sendTransferOTPNotification($sender, $otp, $amount, $receiver);

            return response()->json([
                'success' => true,
                'message' => translate('OTP sent successfully'),
                'otp_expires_in' => 5,
                'receiver_name' => trim($receiver->name),
                'receiver_phone' => $receiver->phone,
                'amount' => $amount,
                'sent_at' => now()->toDateTimeString(),
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'errors' => [['code' => 'server_error', 'message' => translate('Failed to send OTP: ' . $e->getMessage())]]
            ], 500);
        }
    }

    /**
     * Verify transfer OTP and complete transaction
     */
    public function verifyTransferOTP(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'otp' => 'required|string|min:6|max:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $sender = $request->user();

        if (!$sender->transfer_otp || !$sender->transfer_otp_expires_at || now()->isAfter($sender->transfer_otp_expires_at)) {
            return response()->json([
                'errors' => [['code' => 'otp_expired', 'message' => translate('OTP has expired')]]
            ], 400);
        }

        if (!Hash::check($request->otp, $sender->transfer_otp)) {
            return response()->json([
                'errors' => [['code' => 'invalid_otp', 'message' => translate('Invalid OTP')]]
            ], 401);
        }

        $transferData = json_decode($sender->pending_transfer_data, true);
        if (!$transferData) {
            return response()->json([
                'errors' => [['code' => 'no_pending_transfer', 'message' => translate('No pending transfer found')]]
            ], 400);
        }

        $receiver = $this->user->find($transferData['receiver_id']);
        $amount = $transferData['amount'];

        if ($sender->wallet_balance < $amount) {
            return response()->json([
                'errors' => [['code' => 'insufficient_balance', 'message' => translate('Insufficient wallet balance')]]
            ], 400);
        }

        DB::beginTransaction();
        try {
            $transactionId = 'TRF_' . time() . '_' . $sender->id;

            // Create sender transaction
            $this->walletTransaction->create([
                'user_id' => $sender->id,
                'transaction_id' => $transactionId,
                'credit' => 0,
                'debit' => $amount,
                'transaction_type' => 'transfer_sent',
                'reference' => 'Transfer to ' . $transferData['receiver_name'],
                'status' => 'completed',
                'balance' => $sender->wallet_balance - $amount,
                'admin_bonus' => $transferData['note'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create receiver transaction
            $this->walletTransaction->create([
                'user_id' => $receiver->id,
                'transaction_id' => $transactionId,
                'credit' => $amount,
                'debit' => 0,
                'transaction_type' => 'transfer_received',
                'reference' => 'Transfer from ' . trim($sender->name),
                'status' => 'completed',
                'balance' => $receiver->wallet_balance + $amount,
                'admin_bonus' => $transferData['note'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Update balances
            $sender->decrement('wallet_balance', $amount);
            $receiver->increment('wallet_balance', $amount);

            // Clear OTP and pending transfer data
            $sender->update([
                'transfer_otp' => null,
                'transfer_otp_expires_at' => null,
                'pending_transfer_data' => null
            ]);

            DB::commit();

            // Send transfer notifications
            $this->notificationService->sendMoneyTransferNotification(
                $sender->fresh(), 
                $receiver->fresh(), 
                [
                    'transaction_id' => $transactionId,
                    'amount' => $amount,
                    'currency' => 'YER',
                    'note' => $transferData['note'],
                ]
            );

            return response()->json([
                'success' => true,
                'message' => translate('Transfer completed successfully'),
                'transaction_id' => $transactionId,
                'amount' => $amount,
                'receiver_name' => $transferData['receiver_name'],
                'receiver_phone' => $transferData['receiver_phone'],
                'new_balance' => $sender->fresh()->wallet_balance,
                'completed_at' => now()->toDateTimeString(),
            ], 200);

        } catch (Exception $e) {
            DB::rollback();
            Log::error('Transfer verification failed', [
                'sender_id' => $sender->id,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'errors' => [['code' => 'server_error', 'message' => translate('Transfer failed')]]
            ], 500);
        }
    }

    /**
     * Search users for money transfer
     */
    public function searchUsers(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required|string|min:2',
            'limit' => 'nullable|integer|min:1|max:20'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $currentUserId = $request->user()->id;
        $query = $request->query('query'); 
        $limit = $request->input('limit', 10);

        $users = $this->user
            ->where('id', '!=', $currentUserId)
            ->where('is_active', 1)
            ->where(function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                ->orWhere('email', 'LIKE', "%{$query}%")
                ->orWhere('phone', 'LIKE', "%{$query}%");
            })
            ->select('id', 'name', 'email', 'phone', 'image', 'is_active')
            ->limit($limit)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => trim($user->name),
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'image' => $user->image,
                    'is_active' => $user->is_active
                ];
            });

        return response()->json([
            'success' => true,
            'users' => $users,
            'count' => $users->count()
        ], 200);
    }

    /**
     * Get user details for transfer confirmation
     */
    public function getUserDetailsForTransfer(Request $request, $userId): JsonResponse
    {
        $user = $this->user->where('id', $userId)->where('is_active', 1)->first();
        
        if (!$user) {
            return response()->json([
                'errors' => [['code' => 'user_not_found', 'message' => translate('User not found or inactive')]]
            ], 404);
        }

        // Don't allow transfer to self
        if ($user->id == $request->user()->id) {
            return response()->json([
                'errors' => [['code' => 'invalid_recipient', 'message' => translate('Cannot transfer to yourself')]]
            ], 400);
        }

        $data = [
            'id' => $user->id,
            'name' => trim($user->name),
            'email' => $user->email,
            'phone' => $user->phone,
            'image' => $user->image,
            'is_active' => $user->is_active,
        ];

        return response()->json([
            'success' => true,
            'user' => $data
        ], 200);
    }

    /**
     * Update wallet PIN
     */
    public function updateWalletPin(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'current_pin' => 'nullable|string|min:4',
            'new_pin' => 'required|string|min:4|max:8|regex:/^[0-9]+$/',
            'confirm_pin' => 'required|same:new_pin',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $user = $request->user();

        // If user has existing PIN, validate it
        if ($user->wallet_pin && !Hash::check($request->current_pin ?? '', $user->wallet_pin)) {
            return response()->json([
                'errors' => [['code' => 'invalid_current_pin', 'message' => translate('Current PIN is incorrect')]]
            ], 401);
        }

        try {
            $user->update([
                'wallet_pin' => Hash::make($request->new_pin),
                'wallet_pin_updated_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => translate('Wallet PIN updated successfully')
            ], 200);
            
        } catch (Exception $e) {
            return response()->json([
                'errors' => [['code' => 'server_error', 'message' => translate('Failed to update PIN')]]
            ], 500);
        }
    }

    /**
     * Verify wallet PIN
     */
    public function verifyWalletPin(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'pin' => 'required|string|min:4',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $user = $request->user();
        
        if (!$user->wallet_pin) {
            return response()->json([
                'errors' => [['code' => 'no_pin_set', 'message' => translate('Please set up your wallet PIN first')]]
            ], 400);
        }

        $isValid = Hash::check($request->pin, $user->wallet_pin);

        return response()->json([
            'valid' => $isValid,
            'message' => $isValid ? translate('PIN verified successfully') : translate('Invalid PIN')
        ], 200);
    }

    /**
     * Get wallet summary with analytics
     */
    public function getWalletSummary(Request $request): JsonResponse
    {
        $user = $request->user();
        $userId = $user->id;

        // Current month transactions
        $thisMonth = now();
        $thisMonthCredit = $this->walletTransaction
            ->where('user_id', $userId)
            ->whereMonth('created_at', $thisMonth->month)
            ->whereYear('created_at', $thisMonth->year)
            ->sum('credit');

        $thisMonthDebit = $this->walletTransaction
            ->where('user_id', $userId)
            ->whereMonth('created_at', $thisMonth->month)
            ->whereYear('created_at', $thisMonth->year)
            ->sum('debit');

        // Last 30 days daily breakdown
        $dailyTransactions = $this->walletTransaction
            ->where('user_id', $userId)
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, SUM(credit) as credit, SUM(debit) as debit, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Transaction type breakdown
        $transactionTypes = $this->walletTransaction
            ->where('user_id', $userId)
            ->selectRaw('transaction_type, COUNT(*) as count, SUM(credit) as total_credit, SUM(debit) as total_debit')
            ->groupBy('transaction_type')
            ->orderBy('count', 'desc')
            ->get();

        // Most recent transactions
        $recentTransactions = $this->walletTransaction
            ->where('user_id', $userId)
            ->latest()
            ->limit(10)
            ->get();

        $data = [
            'current_balance' => (float) $user->wallet_balance,
            'loyalty_points' => (float) $user->point,
            'this_month' => [
                'earned' => (float) $thisMonthCredit,
                'spent' => (float) $thisMonthDebit,
                'net' => (float) ($thisMonthCredit - $thisMonthDebit),
                'transactions_count' => $this->walletTransaction
                    ->where('user_id', $userId)
                    ->whereMonth('created_at', $thisMonth->month)
                    ->whereYear('created_at', $thisMonth->year)
                    ->count()
            ],
            'daily_breakdown' => $dailyTransactions,
            'transaction_types' => $transactionTypes,
            'recent_transactions' => $recentTransactions,
            'total_transactions' => $this->walletTransaction->where('user_id', $userId)->count(),
            'account_age_days' => $user->created_at->diffInDays(now()),
            'last_updated' => now()->toDateTimeString(),
        ];

        return response()->json($data, 200);
    }

    /**
     * Get transaction limits and current usage
     */
    public function getTransactionLimits(Request $request): JsonResponse
    {
        $user = $request->user();
        $userId = $user->id;

        // Get limits from settings
        $dailyTopUpLimit = $this->businessSetting->where('key', 'wallet_daily_top_up_limit')->first()?->value ?? 50000;
        $dailyTransferLimit = $this->businessSetting->where('key', 'wallet_daily_transfer_limit')->first()?->value ?? 25000;
        $monthlyTopUpLimit = $this->businessSetting->where('key', 'wallet_monthly_top_up_limit')->first()?->value ?? 500000;
        $monthlyTransferLimit = $this->businessSetting->where('key', 'wallet_monthly_transfer_limit')->first()?->value ?? 250000;

        // Calculate today's usage
        $todayTopUps = $this->walletTransaction
            ->where('user_id', $userId)
            ->where('transaction_type', 'add_fund')
            ->whereDate('created_at', today())
            ->sum('credit');

        $todayTransfers = $this->walletTransaction
            ->where('user_id', $userId)
            ->where('transaction_type', 'transfer_sent')
            ->whereDate('created_at', today())
            ->sum('debit');

        // Calculate this month's usage
        $thisMonthTopUps = $this->walletTransaction
            ->where('user_id', $userId)
            ->where('transaction_type', 'add_fund')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('credit');

        $thisMonthTransfers = $this->walletTransaction
            ->where('user_id', $userId)
            ->where('transaction_type', 'transfer_sent')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('debit');

        $data = [
            'daily_limits' => [
                'top_up' => [
                    'limit' => (float) $dailyTopUpLimit,
                    'used' => (float) $todayTopUps,
                    'remaining' => (float) max(0, $dailyTopUpLimit - $todayTopUps)
                ],
                'transfer' => [
                    'limit' => (float) $dailyTransferLimit,
                    'used' => (float) $todayTransfers,
                    'remaining' => (float) max(0, $dailyTransferLimit - $todayTransfers)
                ]
            ],
            'monthly_limits' => [
                'top_up' => [
                    'limit' => (float) $monthlyTopUpLimit,
                    'used' => (float) $thisMonthTopUps,
                    'remaining' => (float) max(0, $monthlyTopUpLimit - $thisMonthTopUps)
                ],
                'transfer' => [
                    'limit' => (float) $monthlyTransferLimit,
                    'used' => (float) $thisMonthTransfers,
                    'remaining' => (float) max(0, $monthlyTransferLimit - $thisMonthTransfers)
                ]
            ],
            'transaction_limits' => [
                'min_top_up' => 1,
                'max_top_up' => 50000,
                'min_transfer' => 1,
                'max_transfer' => 5000
            ],
            'currency' => 'SAR'
        ];

        return response()->json($data, 200);
    }

    /**
     * Cancel pending transfer
     */
    public function cancelPendingTransfer(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->pending_transfer_data) {
            return response()->json([
                'errors' => [['code' => 'no_pending_transfer', 'message' => translate('No pending transfer found')]]
            ], 400);
        }

        try {
            $user->update([
                'transfer_otp' => null,
                'transfer_otp_expires_at' => null,
                'pending_transfer_data' => null
            ]);

            return response()->json([
                'success' => true,
                'message' => translate('Pending transfer cancelled successfully')
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'errors' => [['code' => 'server_error', 'message' => translate('Failed to cancel pending transfer')]]
            ], 500);
        }
    }

    /**
     * Get pending transfer details
     */
    public function getPendingTransfer(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->pending_transfer_data) {
            return response()->json([
                'has_pending_transfer' => false,
                'message' => translate('No pending transfer found')
            ], 200);
        }

        $transferData = json_decode($user->pending_transfer_data, true);
        $otpExpiresAt = $user->transfer_otp_expires_at;

        return response()->json([
            'has_pending_transfer' => true,
            'receiver_name' => $transferData['receiver_name'],
            'receiver_phone' => $transferData['receiver_phone'],
            'amount' => $transferData['amount'],
            'note' => $transferData['note'],
            'otp_expires_at' => $otpExpiresAt,
            'otp_expires_in_seconds' => $otpExpiresAt ? max(0, now()->diffInSeconds($otpExpiresAt, false)) : 0
        ], 200);
    }

    /**
     * Export wallet transactions
     */
    public function exportTransactions(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'format' => 'required|in:json,csv',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'transaction_type' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $userId = $request->user()->id;
        $startDate = $request->start_date;
        $endDate = $request->end_date;
        $format = $request->format;

        $query = $this->walletTransaction
            ->where('user_id', $userId)
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($request->transaction_type) {
            $query->where('transaction_type', $request->transaction_type);
        }

        $transactions = $query->orderBy('created_at', 'desc')->get();

        if ($format === 'json') {
            return response()->json([
                'success' => true,
                'transactions' => $transactions,
                'summary' => [
                    'total_credit' => $transactions->sum('credit'),
                    'total_debit' => $transactions->sum('debit'),
                    'count' => $transactions->count(),
                    'period' => ['start' => $startDate, 'end' => $endDate]
                ],
            ], 200);
        } else {
            // CSV export
            $csvData = "Transaction ID,Date,Type,Credit,Debit,Status,Reference,Balance\n";
            foreach ($transactions as $transaction) {
                $csvData .= sprintf(
                    "%s,%s,%s,%.2f,%.2f,%s,%s,%.2f\n",
                    $transaction->transaction_id,
                    $transaction->created_at->format('Y-m-d H:i:s'),
                    $transaction->transaction_type,
                    $transaction->credit,
                    $transaction->debit,
                    $transaction->status ?? 'completed',
                    str_replace(',', ';', $transaction->reference ?? ''),
                    $transaction->balance
                );
            }

            return response()->json([
                'success' => true,
                'message' => translate('CSV data generated'),
                'data' => base64_encode($csvData),
                'filename' => 'wallet_transactions_' . $startDate . '_to_' . $endDate . '.csv',
                'mime_type' => 'text/csv'
            ], 200);
        }
    }

    // Private helper methods
    private function sendTransferOTPNotification($sender, $otp, $amount, $receiver)
    {
        try {
            Log::debug('Mail configuration', config('mail'));
            
            Log::info("Transfer OTP sent to user {$sender->id}", [
                'phone' => $sender->phone,
                'otp' => $otp,
                'amount' => $amount,
                'receiver' => $receiver->phone,
                'timestamp' => now()->toDateTimeString()
            ]);
            
            // Email notification
            if ($sender->email) {
                try {
                    $localization = app()->getLocale();
                    Mail::to($sender->email)->send(new TransferOtp($otp, $localization));
                    // Mail::to($sender->email)->send(new MoneyTransferNotification($otp, $localization));
                    Log::info("Transfer OTP email sent to {$sender->email}");
                } catch (\Exception $e) {
                    Log::error("Transfer OTP email failed", [
                        'user_id' => $sender->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // WhatsApp notification
            if ($sender->phone) {
                try {
                    $message = $this->whatsapp->sendTemplateMessage('transfer_otp', [
                        'user_name' => $sender->name,
                        'otp' => $otp,
                        'amount' => $amount,
                        'currency' => 'YER',
                        'receiver_name' => $receiver->name,
                        'expiry_minutes' => '5',
                        'timestamp' => now()->format('Y-m-d H:i:s')
                    ]);
                    
                    $response = $this->whatsapp->sendMessage($sender->phone, $message);
                    
                    Log::info("Transfer OTP WhatsApp sent", [
                        'user_id' => $sender->id,
                        'phone' => $sender->phone,
                        'response' => $response
                    ]);
                } catch (\Exception $e) {
                    Log::error("Transfer OTP WhatsApp failed", [
                        'user_id' => $sender->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // In-app notification
            $this->storeInAppNotification($sender->id, [
                'title' => 'Transfer OTP',
                'description' => "Your transfer OTP is: {$otp}. Valid for 5 minutes.",
                'type' => 'transfer_otp',
                'reference_id' => 'TRANSFER_OTP_' . time(),
            ]);
            
        } catch (Exception $e) {
            Log::error("Failed to send transfer OTP", [
                'user_id' => $sender->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function storeInAppNotification(int $userId, array $data): void
    {
        try {
            \App\Model\Notification::create([
                'user_id' => $userId,
                'title' => $data['title'],
                'description' => $data['description'],
                'notification_type' => $data['type'],
                'reference_id' => $data['reference_id'] ?? null,
                'status' => 1,
                'is_read' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            Log::info('In-app notification stored', [
                'user_id' => $userId,
                'type' => $data['type']
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to store in-app notification', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function sendTransferSuccessNotification($sender, $receiver, $amount, $transactionId)
    {
        try {
            Log::info("Transfer completed successfully", [
                'transaction_id' => $transactionId,
                'sender' => $sender->id,
                'receiver' => $receiver->id,
                'amount' => $amount
            ]);

            // Send success notifications to both parties
            // Implementation would depend on your notification system
            
        } catch (Exception $e) {
            Log::error("Failed to send transfer success notification", [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get wallet analytics
     */
    public function getWalletAnalytics(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'period' => 'nullable|string|in:week,month,quarter,year',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $userId = $request->user()->id;
        $period = $request->period ?? 'month';

        $analytics = [
            'total_transactions' => $this->walletTransaction->where('user_id', $userId)->count(),
            'total_credits' => $this->walletTransaction->where('user_id', $userId)->where('credit', '>', 0)->sum('credit'),
            'total_debits' => $this->walletTransaction->where('user_id', $userId)->where('debit', '>', 0)->sum('debit'),
            'average_transaction' => $this->walletTransaction->where('user_id', $userId)->where('credit', '>', 0)->avg('credit'),
            'top_gateways' => $this->walletTransaction
                ->where('user_id', $userId)
                ->where('transaction_type', 'add_fund')
                ->selectRaw('gateway, COUNT(*) as count, SUM(credit) as total')
                ->groupBy('gateway')
                ->limit(5)
                ->get()
        ];

        return response()->json(['analytics' => $analytics], 200);
    }

    /**
     * Get transaction details
     */
    public function getTransactionDetails(Request $request, $transactionId): JsonResponse
    {
        $transaction = $this->walletTransaction
            ->where('user_id', $request->user()->id)
            ->where('transaction_id', $transactionId)
            ->first();

        if (!$transaction) {
            return response()->json([
                'errors' => [['code' => 'transaction_not_found', 'message' => 'Transaction not found']]
            ], 404);
        }

        $adminBonus = json_decode($transaction->admin_bonus, true) ?? [];
        
        return response()->json([
            'transaction' => [
                'id' => $transaction->id,
                'transaction_id' => $transaction->transaction_id,
                'type' => $transaction->transaction_type,
                'amount' => $transaction->credit ?: $transaction->debit,
                'credit' => $transaction->credit,
                'debit' => $transaction->debit,
                'balance' => $transaction->balance,
                'status' => $transaction->status,
                'reference' => $transaction->reference,
                'gateway' => $adminBonus['gateway'] ?? null,
                'created_at' => $transaction->created_at,
                'updated_at' => $transaction->updated_at,
                'metadata' => $adminBonus
            ]
        ], 200);
    }

    /**
     * Request refund for a transaction
     */
    public function requestRefund(Request $request, $transactionId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500',
            'refund_amount' => 'required|numeric|min:0.01'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $transaction = $this->walletTransaction
            ->where('user_id', $request->user()->id)
            ->where('transaction_id', $transactionId)
            ->where('status', 'completed')
            ->first();

        if (!$transaction) {
            return response()->json([
                'errors' => [['code' => 'invalid_transaction', 'message' => 'Invalid or non-completed transaction']]
            ], 404);
        }

        // Check if refund is already processed
        if ($transaction->refund_requested) {
            return response()->json([
                'errors' => [['code' => 'already_requested', 'message' => 'Refund already requested']]
            ], 400);
        }

        try {
            $transaction->update([
                'refund_requested' => true,
                'refund_amount' => $request->refund_amount,
                'refund_reason' => $request->reason,
                'refund_status' => 'pending',
                'refund_requested_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Refund request submitted successfully',
                'transaction_id' => $transactionId
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'errors' => [['code' => 'server_error', 'message' => 'Failed to request refund']]
            ], 500);
        }
    }

    /**
     * Check transfer eligibility
     */
    public function checkTransferEligibility(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $eligibility = [
            'wallet_pin_set' => !empty($user->wallet_pin),
            'phone_verified' => !empty($user->phone_verified_at),
            'email_verified' => !empty($user->email_verified_at),
            'kyc_completed' => !empty($user->kyc_verified_at), // Assuming KYC field exists
            'daily_limit_remaining' => true,
            'monthly_limit_remaining' => true,
            'minimum_balance_met' => $user->wallet_balance >= 1
        ];

        // Check daily/monthly limits
        $dailyUsed = $this->walletTransaction
            ->where('user_id', $user->id)
            ->where('transaction_type', 'transfer_sent')
            ->whereDate('created_at', today())
            ->sum('debit');

        $monthlyUsed = $this->walletTransaction
            ->where('user_id', $user->id)
            ->where('transaction_type', 'transfer_sent')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('debit');

        $dailyLimit = Helpers::get_business_settings('wallet_daily_transfer_limit')['value'] ?? 25000;
        $monthlyLimit = Helpers::get_business_settings('wallet_monthly_transfer_limit')['value'] ?? 250000;

        $eligibility['daily_limit_remaining'] = ($dailyUsed < $dailyLimit);
        $eligibility['monthly_limit_remaining'] = ($monthlyUsed < $monthlyLimit);

        $eligibility['can_transfer'] = collect($eligibility)->every(fn($value) => $value === true);

        return response()->json(['eligibility' => $eligibility], 200);
    }

    /**
     * Report dispute for transaction
     */
    public function reportDispute(Request $request, $transactionId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:1000',
            'category' => 'required|string|in:unauthorized,fraud,wrong_amount,other',
            'evidence' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $transaction = $this->walletTransaction
            ->where('user_id', $request->user()->id)
            ->where('transaction_id', $transactionId)
            ->where('status', 'completed')
            ->first();

        if (!$transaction) {
            return response()->json([
                'errors' => [['code' => 'invalid_transaction', 'message' => 'Transaction not found']]
            ], 404);
        }

        try {
            // Create dispute record (assuming you have a disputes table)
            DB::table('wallet_disputes')->insert([
                'transaction_id' => $transaction->id,
                'user_id' => $request->user()->id,
                'category' => $request->category,
                'reason' => $request->reason,
                'evidence' => json_encode($request->evidence ?? []),
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Dispute reported successfully',
                'dispute_id' => DB::getPdo()->lastInsertId()
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'errors' => [['code' => 'server_error', 'message' => 'Failed to report dispute']]
            ], 500);
        }
    }

    /**
     * Get wallet settings
     */
    public function getWalletSettings(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $settings = [
            'pin_enabled' => !empty($user->wallet_pin),
            'notifications_enabled' => $user->wallet_notifications ?? true,
            'two_factor_enabled' => $user->wallet_2fa_enabled ?? false,
            'auto_topup_enabled' => $user->auto_topup_enabled ?? false,
            'transfer_limits' => $this->getTransactionLimits($request)->getData(true),
            'supported_currencies' => ['SAR'],
            'fee_structure' => [
                'domestic_transfer' => 0.5,
                'international_transfer' => 2.0,
                'topup_fee' => 0
            ]
        ];

        return response()->json(['settings' => $settings], 200);
    }

    /**
     * Update notification settings
     */
    public function updateNotificationSettings(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'sms_notifications' => 'boolean',
            'email_notifications' => 'boolean',
            'push_notifications' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $user = $request->user();
        
        $user->update([
            'wallet_sms_notifications' => $request->sms_notifications ?? $user->wallet_sms_notifications,
            'wallet_email_notifications' => $request->email_notifications ?? $user->wallet_email_notifications,
            'wallet_push_notifications' => $request->push_notifications ?? $user->wallet_push_notifications
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Notification settings updated'
        ], 200);
    }

    /**
     * Check wallet status (public endpoint)
     */
    public function checkWalletStatus(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $user = $this->user->find($request->user_id);

        return response()->json([
            'wallet_active' => $user->wallet_active ?? true,
            'wallet_balance' => (float) $user->wallet_balance,
            'pin_set' => !empty($user->wallet_pin),
            'kyc_verified' => !empty($user->kyc_verified_at)
        ], 200);
    }

    /**
     * Verify payment by reference (public)
     */
    public function verifyPayment(Request $request, $reference): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'gateway' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $transaction = $this->walletTransaction
            ->where('transaction_id', $reference)
            ->orWhere('reference', 'LIKE', "%{$reference}%")
            ->first();

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'status' => $transaction->status,
            'amount' => $transaction->credit ?: $transaction->debit,
            'gateway' => $transaction->gateway ?? 'unknown',
            'verified' => in_array($transaction->status, ['completed', 'paid'])
        ], 200);
    }

    /**
     * Get cashback history
     */
    public function getCashbackHistory(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'nullable|integer|min:1|max:100',
            'offset' => 'nullable|integer|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $userId = $request->user()->id;
        $limit = $request->limit ?? 20;
        $offset = $request->offset ?? 0;

        $cashbacks = $this->walletTransaction
            ->where('user_id', $userId)
            ->where('transaction_type', 'cashback')
            ->latest()
            ->offset($offset)
            ->limit($limit)
            ->get();

        return response()->json([
            'cashbacks' => $cashbacks,
            'total' => $this->walletTransaction->where('user_id', $userId)->where('transaction_type', 'cashback')->count()
        ], 200);
    }
}