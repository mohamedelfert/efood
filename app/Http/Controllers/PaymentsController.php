<?php

namespace App\Http\Controllers;

use Exception;
use App\Model\Order;
use App\Models\Setting;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use App\Model\OrderTransaction;
use App\Model\WalletTransaction;
use App\Services\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Models\OrderPartialPayment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Services\ReceiptGeneratorService;
use App\Services\Payment\PaymentGatewayFactory;

class PaymentsController extends Controller
{
    protected $gateway;
    private $whatsappService;
    private $receiptGenerator;

    public function __construct(Request $request,WhatsAppService $whatsappService,ReceiptGeneratorService $receiptGenerator)
    {
        $gatewayType = $request->input('gateway', 'paymob');
        $this->gateway = PaymentGatewayFactory::create($gatewayType);
        if (!$this->gateway) {
            throw new Exception('Invalid payment gateway');
        }
        $this->whatsappService = $whatsappService;
        $this->receiptGenerator = $receiptGenerator;
    }

    public function handleCallback(Request $request): JsonResponse
    {
        try {
            $data = $request->all();
            
            // Extract from nested 'obj' structure if present (POST callback)
            if (isset($data['obj']) && is_array($data['obj'])) {
                $obj = $data['obj'];
                $paymobTransactionId = $obj['id'] ?? null;
                $orderId = $obj['order']['id'] ?? null;
                $hmac = $data['hmac'] ?? null;
                $transactionId = null; // Not in POST callback
            } else {
                // Extract from query params (GET callback)
                $paymobTransactionId = $data['id'] ?? $request->query('id');
                $orderId = $data['order'] ?? $request->query('order');
                $hmac = $data['hmac'] ?? $request->query('hmac');
                $transactionId = $data['transaction_id'] ?? $request->query('transaction_id');
            }

            // Log incoming callback data
            Log::info('Callback Data Received', [
                'transaction_id' => $transactionId,
                'order_id' => $orderId,
                'paymob_transaction_id' => $paymobTransactionId,
                'hmac' => $hmac,
                'has_nested_obj' => isset($data['obj']),
                'query' => $request->query(),
                'data_keys' => array_keys($data)
            ]);

            // Find transaction - try multiple approaches
            $transaction = null;
            
            // Try 1: Direct transaction_id match
            if ($transactionId) {
                $transaction = WalletTransaction::where('transaction_id', $transactionId)
                    ->where('status', 'pending')
                    ->first();
            }
            
            // Try 2: Search by paymob_transaction_id in metadata (cast both as strings)
            if (!$transaction && $paymobTransactionId) {
                $transaction = WalletTransaction::where('status', 'pending')
                    ->whereRaw('CAST(JSON_UNQUOTE(JSON_EXTRACT(metadata, "$.paymob_transaction_id")) AS CHAR) = ?', [(string)$paymobTransactionId])
                    ->first();
            }
            
            // Try 3: Search by paymob_order_id in metadata (cast both as strings)
            if (!$transaction && $orderId) {
                $transaction = WalletTransaction::where('status', 'pending')
                    ->whereRaw('CAST(JSON_UNQUOTE(JSON_EXTRACT(metadata, "$.paymob_order_id")) AS CHAR) = ?', [(string)$orderId])
                    ->first();
            }

            if (!$transaction) {
                // Debug: Log all pending transactions
                $pendingTransactions = WalletTransaction::where('status', 'pending')
                    ->get(['transaction_id', 'metadata'])
                    ->map(function($t) {
                        $meta = json_decode($t->metadata, true);
                        return [
                            'transaction_id' => $t->transaction_id,
                            'paymob_order_id' => $meta['paymob_order_id'] ?? null,
                            'paymob_transaction_id' => $meta['paymob_transaction_id'] ?? null,
                        ];
                    })
                    ->toArray();
                    
                Log::error('Callback: Transaction not found', [
                    'search_params' => [
                        'transaction_id' => $transactionId,
                        'order_id' => $orderId,
                        'paymob_transaction_id' => $paymobTransactionId,
                    ],
                    'pending_transactions' => $pendingTransactions
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => translate('Invalid or completed transaction')
                ], 400);
            }

            // Decode metadata
            $metadata = is_string($transaction->metadata) ? json_decode($transaction->metadata, true) : $transaction->metadata;
            if (!is_array($metadata)) {
                Log::error('Callback: Invalid metadata format', [
                    'transaction_id' => $transaction->transaction_id,
                    'metadata' => $transaction->metadata
                ]);
                return response()->json([
                    'success' => false,
                    'message' => translate('Invalid transaction metadata')
                ], 400);
            }

            // Use the gateway stored in the transaction
            $gateway = $transaction->gateway;
            if (!$gateway) {
                Log::error('Callback: Gateway not specified', [
                    'transaction_id' => $transaction->transaction_id,
                    'paymob_order_id' => $orderId
                ]);
                return response()->json([
                    'success' => false,
                    'message' => translate('Gateway not specified for this transaction')
                ], 400);
            }

            $this->gateway = PaymentGatewayFactory::create($gateway);
            if (!$this->gateway) {
                Log::error('Callback: Invalid gateway', ['gateway' => $gateway]);
                return response()->json([
                    'success' => false,
                    'message' => translate('Invalid payment gateway')
                ], 400);
            }

            // Update transaction with Paymob transaction ID if missing
            if ($paymobTransactionId && empty($metadata['paymob_transaction_id'])) {
                $metadata['paymob_transaction_id'] = (string)$paymobTransactionId;
                $transaction->update(['metadata' => json_encode($metadata)]);
                Log::info('Updated transaction metadata with paymob_transaction_id', [
                    'transaction_id' => $transaction->transaction_id,
                    'paymob_transaction_id' => $paymobTransactionId
                ]);
            }

            // Pass the full callback data to the gateway for processing
            $callbackData = isset($data['obj']) ? $data['obj'] : $data;
            $response = $this->gateway->handleCallback($callbackData);

            if (
                isset($response['status']) && 
                $response['status'] === 'success' && 
                isset($response['paymob_transaction_id']) && 
                (string)$response['paymob_transaction_id'] === (string)($metadata['paymob_transaction_id'] ?? $paymobTransactionId)
            ) {
                DB::beginTransaction();
                try {
                    if ($transaction->transaction_type === 'order_payment'){
                        // Update WalletTransaction
                        $transaction->update([
                            'status' => 'completed',
                            'balance' => $transaction->user->wallet_balance,
                            'updated_at' => now(),
                        ]);
                    }else{
                        $transaction->update([
                            'status' => 'completed',
                            'balance' => $transaction->user->wallet_balance + $transaction->credit,
                            'updated_at' => now(),
                        ]);

                        // Increment user wallet balance
                        $transaction->user->increment('wallet_balance', $transaction->credit);
                    }

                    // Update order status if this is an order payment
                    if (isset($metadata['order_id'])) {
                        Order::where('id', $metadata['order_id'])->update([
                            'payment_status' => 'paid',
                            'order_status' => 'confirmed',
                            'updated_at' => now(),
                        ]);

                        // Update OrderPartialPayment if applicable
                        OrderPartialPayment::where('order_id', $metadata['order_id'])
                            ->where('paid_with', $gateway)
                            ->update([
                                'paid_amount' => $transaction->credit,
                                'due_amount' => 0,
                                'updated_at' => now(),
                            ]);
                    }

                    DB::commit();

                    Log::info('Callback: Payment processed successfully', [
                        'transaction_id' => $transaction->transaction_id,
                        'user_id' => $transaction->user_id,
                        'order_id' => $metadata['order_id'] ?? null,
                        'paymob_order_id' => $orderId,
                        'paymob_transaction_id' => $paymobTransactionId,
                        'new_balance' => $transaction->user->wallet_balance
                    ]);

                    // Send notifications
                    if ($transaction->transaction_type === 'order_payment' && isset($metadata['order_id'])) {
                        $order = Order::find($metadata['order_id']);
                        if ($order) {
                            $notificationService = app(\App\Services\NotificationService::class);
                            $notificationService->sendOrderPlacedNotification(
                                $transaction->user,
                                $order,
                                ['currency' => $metadata['currency'] ?? 'EGP']
                            );
                        }
                    } else {
                        $previousBalance = $transaction->balance - $transaction->credit;
                        $notificationService = app(\App\Services\NotificationService::class);
                        $notificationService->sendWalletTopUpNotification(
                            $transaction->user->fresh(),
                            [
                                'transaction_id' => $transaction->transaction_id,
                                'amount' => $transaction->credit,
                                'currency' => $metadata['currency'] ?? 'EGP',
                                'gateway' => $gateway,
                                'previous_balance' => $previousBalance,
                            ]
                        );
                    }

                    return response()->json([
                        'success' => true,
                        'message' => translate('Payment processed successfully'),
                        'transaction_id' => $transaction->transaction_id,
                        'order_id' => $metadata['order_id'] ?? null,
                        'amount' => $transaction->credit,
                        'currency' => $metadata['currency'] ?? 'EGP',
                        'gateway' => $gateway,
                        'new_balance' => $transaction->user->fresh()->wallet_balance,
                        'transaction_type' => $transaction->transaction_type,
                    ], 200);
                } catch (Exception $e) {
                    DB::rollBack();
                    Log::error('Callback: Database update failed', [
                        'error' => $e->getMessage(),
                        'transaction_id' => $transaction->transaction_id,
                        'trace' => $e->getTraceAsString()
                    ]);
                    return response()->json([
                        'success' => false,
                        'message' => translate('Failed to update transaction')
                    ], 500);
                }
            } else {
                Log::error('Callback: Payment verification failed', [
                    'transaction_id' => $transaction->transaction_id,
                    'response_status' => $response['status'] ?? null,
                    'expected_paymob_id' => $metadata['paymob_transaction_id'] ?? $paymobTransactionId,
                    'received_paymob_id' => $response['paymob_transaction_id'] ?? null,
                    'response' => $response
                ]);
                return response()->json([
                    'success' => false,
                    'message' => $response['error'] ?? translate('Payment verification failed')
                ], 400);
            }
        } catch (Exception $e) {
            Log::error('Callback handling failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'errors' => [['code' => 'server_error', 'message' => translate('Callback processing failed')]]
            ], 500);
        }
    }

    // public function handleCallback(Request $request): JsonResponse
    // {
    //     try {
    //         $data = $request->all();
    //         $transactionId = $data['transaction_id'] ?? $request->query('transaction_id');
    //         $orderId = $data['order'] ?? $request->query('order'); // Paymob order ID
    //         $paymobTransactionId = $data['id'] ?? $request->query('id'); // Paymob transaction ID
    //         $hmac = $data['hmac'] ?? $request->query('hmac');

    //         // Log incoming callback data
    //         Log::info('Callback Data Received', [
    //             'transaction_id' => $transactionId,
    //             'order_id' => $orderId,
    //             'paymob_transaction_id' => $paymobTransactionId,
    //             'hmac' => $hmac,
    //             'query' => $request->query(),
    //             'data' => $data
    //         ]);

    //         // Find transaction
    //         $query = WalletTransaction::where('status', 'pending');
    //         if ($transactionId) {
    //             $query->where('transaction_id', $transactionId);
    //         } elseif ($paymobTransactionId) {
    //             $query->orWhereRaw('JSON_UNQUOTE(JSON_EXTRACT(metadata, "$.paymob_transaction_id")) = ?', [$paymobTransactionId]);
    //         } elseif ($orderId) {
    //             $query->orWhereRaw('JSON_UNQUOTE(JSON_EXTRACT(metadata, "$.paymob_order_id")) = ?', [$orderId]);
    //         }
    //         $transaction = $query->first();

    //         if (!$transaction) {
    //             // Debug: Log all pending transactions to check metadata
    //             $pendingTransactions = WalletTransaction::where('status', 'pending')->get(['transaction_id', 'metadata'])->toArray();
    //             Log::error('Callback: Transaction not found', [
    //                 'transaction_id' => $transactionId,
    //                 'order_id' => $orderId,
    //                 'paymob_transaction_id' => $paymobTransactionId,
    //                 'pending_transactions' => $pendingTransactions
    //             ]);
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => translate('Invalid or completed transaction')
    //             ], 400);
    //         }

    //         // Decode metadata
    //         $metadata = is_string($transaction->metadata) ? json_decode($transaction->metadata, true) : $transaction->metadata;
    //         if (!is_array($metadata)) {
    //             Log::error('Callback: Invalid metadata format', [
    //                 'transaction_id' => $transaction->transaction_id,
    //                 'metadata' => $transaction->metadata
    //             ]);
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => translate('Invalid transaction metadata')
    //             ], 400);
    //         }

    //         // Use the gateway stored in the transaction
    //         $gateway = $transaction->gateway;
    //         if (!$gateway) {
    //             Log::error('Callback: Gateway not specified', [
    //                 'transaction_id' => $transaction->transaction_id,
    //                 'paymob_order_id' => $orderId
    //             ]);
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => translate('Gateway not specified for this transaction')
    //             ], 400);
    //         }

    //         $this->gateway = PaymentGatewayFactory::create($gateway);
    //         if (!$this->gateway) {
    //             Log::error('Callback: Invalid gateway', ['gateway' => $gateway]);
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => translate('Invalid payment gateway')
    //             ], 400);
    //         }

    //         // Update transaction with Paymob transaction ID if missing
    //         if ($paymobTransactionId && empty($metadata['paymob_transaction_id'])) {
    //             $metadata['paymob_transaction_id'] = $paymobTransactionId;
    //             $transaction->update(['metadata' => json_encode($metadata)]);
    //         }

    //         $response = $this->gateway->handleCallback($data);

    //         if (
    //             isset($response['status']) && 
    //             $response['status'] === 'success' && 
    //             isset($response['paymob_transaction_id']) && 
    //             $response['paymob_transaction_id'] === 
    //             ($metadata['paymob_transaction_id'])
    //         ) {
    //             DB::beginTransaction();
    //             try {
    //                 if ($transaction->transaction_type === 'order_payment'){
    //                     // Update WalletTransaction
    //                     $transaction->update([
    //                         'status' => 'completed',
    //                         'balance' => $transaction->user->wallet_balance,
    //                         'updated_at' => now(),
    //                     ]);
    //                 }else{
    //                     $transaction->update([
    //                         'status' => 'completed',
    //                         'balance' => $transaction->user->wallet_balance + $transaction->credit,
    //                         'updated_at' => now(),
    //                     ]);

    //                     // Increment user wallet balance
    //                     $transaction->user->increment('wallet_balance', $transaction->credit);
    //                 }

    //                 // Update order status if this is an order payment
    //                 if (isset($metadata['order_id'])) {
    //                     Order::where('id', $metadata['order_id'])->update([
    //                         'payment_status' => 'paid',
    //                         'order_status' => 'confirmed',
    //                         'updated_at' => now(),
    //                     ]);

    //                     // Update OrderPartialPayment if applicable
    //                     OrderPartialPayment::where('order_id', $metadata['order_id'])
    //                         ->where('paid_with', $gateway)
    //                         ->update([
    //                             'paid_amount' => $transaction->credit,
    //                             'due_amount' => 0,
    //                             'updated_at' => now(),
    //                         ]);

    //                     // Update existing OrderTransaction
    //                     OrderTransaction::where('order_id', $metadata['order_id'])
    //                         ->where('status', 'pending')
    //                         ->update([
    //                             'status' => 'completed',
    //                             'balance' => $transaction->user->wallet_balance,
    //                             'updated_at' => now(),
    //                         ]);
    //                 }

    //                 DB::commit();

    //                 Log::info('Callback: Payment processed successfully', [
    //                     'transaction_id' => $transaction->transaction_id,
    //                     'user_id' => $transaction->user_id,
    //                     'order_id' => $metadata['order_id'] ?? null,
    //                     'paymob_order_id' => $orderId,
    //                     'paymob_transaction_id' => $paymobTransactionId,
    //                     'new_balance' => $transaction->user->wallet_balance
    //                 ]);

    //                 $previousBalance = $transaction->balance - $transaction->credit;

    //                 // Send WhatsApp notification with receipt for successful wallet top-up
    //                 if ($transaction->transaction_type === 'order_payment' && isset($metadata['order_id'])) {
    //                     // Send order payment notifications
    //                     $order = Order::find($metadata['order_id']);
    //                     if ($order) {
    //                         $notificationService = app(\App\Services\NotificationService::class);
    //                         $notificationService->sendOrderPlacedNotification(
    //                             $transaction->user,
    //                             $order,
    //                             ['currency' => $metadata['currency'] ?? 'EGP']
    //                         );
    //                     }
    //                 } else {
    //                     // Send wallet top-up notifications (Email + WhatsApp + Push + In-App)
    //                     $notificationService = app(\App\Services\NotificationService::class);
    //                     $notificationService->sendWalletTopUpNotification(
    //                         $transaction->user->fresh(),
    //                         [
    //                             'transaction_id' => $transaction->transaction_id,
    //                             'amount' => $transaction->credit,
    //                             'currency' => $metadata['currency'] ?? 'EGP',
    //                             'gateway' => $gateway,
    //                             'previous_balance' => $previousBalance,
    //                         ]
    //                     );
    //                 }

    //                 return response()->json([
    //                     'success' => true,
    //                     'message' => translate('Payment processed successfully'),
    //                     'transaction_id' => $transaction->transaction_id,
    //                     'order_id' => $metadata['order_id'] ?? null,
    //                     'amount' => $transaction->credit,
    //                     'currency' => $metadata['currency'] ?? 'EGP',
    //                     'gateway' => $gateway,
    //                     'new_balance' => $transaction->user->fresh()->wallet_balance,
    //                     'transaction_type' => $transaction->transaction_type,
    //                 ], 200);
    //             } catch (Exception $e) {
    //                 DB::rollBack();
    //                 Log::error('Callback: Database update failed', [
    //                     'error' => $e->getMessage(),
    //                     'transaction_id' => $transaction->transaction_id,
    //                     'trace' => $e->getTraceAsString()
    //                 ]);
    //                 return response()->json([
    //                     'success' => false,
    //                     'message' => translate('Failed to update transaction')
    //                 ], 500);
    //             }
    //         } else {
    //             Log::error('Callback: Payment failed', [
    //                 'transaction_id' => $transaction->transaction_id,
    //                 'response' => $response
    //             ]);
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => $response['error'] ?? translate('Payment failed')
    //             ], 400);
    //         }
    //     } catch (Exception $e) {
    //         Log::error('Callback handling failed', [
    //             'error' => $e->getMessage(),
    //             'transaction_id' => $transactionId,
    //             'order_id' => $orderId,
    //             'paymob_transaction_id' => $paymobTransactionId,
    //             'trace' => $e->getTraceAsString()
    //         ]);
    //         return response()->json([
    //             'success' => false,
    //             'errors' => [['code' => 'server_error', 'message' => translate('Callback processing failed')]]
    //         ], 500);
    //     }
    // }

    /**
     * Send WhatsApp notification with receipt using template
     */
    private function sendWhatsAppReceiptNotification($transaction, $metadata, $previousBalance): void
    {
        try {
            // Check status
            $whatsappStatus = \App\Model\BusinessSetting::where('key', 'wallet_topup_whatsapp_status_user')->first();
           
            if (!$whatsappStatus || $whatsappStatus->value != '1') {
                Log::info('WhatsApp notifications disabled', [
                    'transaction_id' => $transaction->transaction_id
                ]);
                return;
            }
            $userPhone = '20' . ltrim($transaction->user->phone, '0');
            $amount = number_format($transaction->credit, 2);
            $newBalance = number_format($previousBalance + $transaction->credit, 2);
            $previousBalanceFormatted = number_format($previousBalance, 2);
            $currency = $metadata['currency'] ?? 'EGP';
            $date = now()->format('d/m/Y h:i A');
            // Prepare template data
            $templateData = [
                'customer_name' => $transaction->user->f_name . ' ' . $transaction->user->l_name,
                'amount' => $amount,
                'currency' => $currency,
                'new_balance' => $newBalance,
                'previous_balance' => $previousBalanceFormatted,
                'transaction_id' => $transaction->transaction_id,
                'date' => $date,
                'account_number' => $transaction->user->id,
                'branch' => config('company.branch', 'Main Branch'),
            ];
            // Build message from template
            $message = $this->whatsappService->sendTemplateMessage('wallet_topup', $templateData);
            $receiptData = [
                'transaction_id' => $transaction->transaction_id,
                'date' => now()->format('d/m/Y'),
                'time' => now()->format('h:i A'),
                'customer_name' => $templateData['customer_name'],
                'account_number' => $templateData['account_number'],
                'branch' => $templateData['branch'],
                'amount' => $amount,
                'currency' => $currency,
                'previous_balance' => $previousBalanceFormatted,
                'new_balance' => $newBalance,
                'tax' => '0.00',
            ];
            $receiptPath = $this->receiptGenerator->generateReceiptImage($receiptData);
            $receiptUrl = url(Storage::url(str_replace(storage_path('app/public/'), '', $receiptPath)));
            // Try with media first (URL)
            $whatsappResponse = $this->whatsappService->sendMessage($userPhone, $message, $receiptUrl);
            if (isset($whatsappResponse['success']) && $whatsappResponse['success'] === false) {
                $errorMsg = $whatsappResponse['error'] ?? $whatsappResponse['message'] ?? 'Unknown error';
                Log::warning('WhatsApp media failed, falling back to text', [
                    'user_id' => $transaction->user_id,
                    'transaction_id' => $transaction->transaction_id,
                    'whatsapp_error' => $errorMsg
                ]);
                // Fallback: Send text-only
                $textResponse = $this->whatsappService->sendMessage($userPhone, $message);
                if (isset($textResponse['success']) && $textResponse['success'] === false) {
                    $textError = $textResponse['error'] ?? $textResponse['message'] ?? 'Unknown error';
                    Log::error('WhatsApp text fallback also failed', [
                        'user_id' => $transaction->user_id,
                        'transaction_id' => $transaction->transaction_id,
                        'error' => $textError
                    ]);
                } else {
                    Log::info('WhatsApp text fallback sent successfully', [
                        'user_id' => $transaction->user_id,
                        'transaction_id' => $transaction->transaction_id
                    ]);
                }
            } else {
                Log::info('WhatsApp notification with receipt sent successfully', [
                    'user_id' => $transaction->user_id,
                    'transaction_id' => $transaction->transaction_id,
                    'phone' => $userPhone,
                    'receipt_url' => $receiptUrl
                ]);
            }
            $this->cleanupOldReceipts();
        } catch (Exception $e) {
            Log::error('WhatsApp receipt notification exception', [
                'user_id' => $transaction->user_id ?? null,
                'transaction_id' => $transaction->transaction_id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    private function cleanupOldReceipts(): void
    {
        try {
            $receiptPaths = ['receipts/images/', 'receipts/pdf/'];
            foreach ($receiptPaths as $path) {
                $files = Storage::disk('public')->files($path);
                $now = time();
                $daysToKeep = 7;
                foreach ($files as $file) {
                    $fullPath = storage_path('app/public/' . $path . basename($file));
                    if ($now - filemtime($fullPath) >= 60 * 60 * 24 * $daysToKeep) {
                        Storage::disk('public')->delete($path . basename($file));
                        Log::info('Old receipt deleted', ['file' => $file]);
                    }
                }
            }
        } catch (Exception $e) {
            Log::warning('Failed to cleanup old receipts', [
                'error' => $e->getMessage()
            ]);
        }
    }

   public function getPaymentMethods(): JsonResponse
    {
        $rows = Setting::where('settings_type', 'payment_config')
            ->whereIn('key_name', [
                'ssl_commerz','paypal','stripe','razor_pay','senang_pay',
                'paystack','paymob_accept','flutterwave','bkash','mercadopago',
            ])
            ->get();

        $methods = $rows->map(function ($setting) {
            $mode   = $setting->mode;
            $values = $mode === 'live' ? $setting->live_values : $setting->test_values;
            $enabled = $setting->is_active == 1
                || Arr::get($values, 'status') == '1';

            $extra = $setting->additional_data ? json_decode($setting->additional_data, true) : [];

            return [
                'gateway'     => $setting->key_name,
                'title'       => $extra['gateway_title'] ?? ucwords(str_replace('_', ' ', $setting->key_name)),
                'image'       => $extra['gateway_image'] ?? null,
                'mode'        => $mode,
                'is_enabled'  => $enabled ? 1 : 0,
            ];
        })
        ->filter(fn($m) => $m['is_enabled'] === 1)
        ->values();

        return response()->json([
            'success' => true,
            'methods' => $methods,
        ]);
    }
}