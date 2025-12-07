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

    // public function handleCallback(Request $request): JsonResponse
    // {
    //     try {
    //         $data = $request->all();
            
    //         // Extract IDs from different callback formats
    //         $transactionId = $data['transaction_id'] ?? $request->query('transaction_id');
    //         $orderId = null;
    //         $paymobTransactionId = null;
    //         $hmac = $data['hmac'] ?? $request->query('hmac');

    //         // Handle JSON webhook format (obj.order.id)
    //         if (isset($data['obj']['order']['id'])) {
    //             $orderId = $data['obj']['order']['id'];
    //         }
    //         // Handle JSON webhook format (obj.id for transaction)
    //         if (isset($data['obj']['id'])) {
    //             $paymobTransactionId = $data['obj']['id'];
    //         }
            
    //         // Handle query param format
    //         if (!$orderId && isset($data['order'])) {
    //             $orderId = $data['order'];
    //         }
    //         if (!$orderId) {
    //             $orderId = $request->query('order');
    //         }
            
    //         if (!$paymobTransactionId && isset($data['id'])) {
    //             $paymobTransactionId = $data['id'];
    //         }
    //         if (!$paymobTransactionId) {
    //             $paymobTransactionId = $request->query('id');
    //         }

    //         Log::info('Callback Data Received', [
    //             'transaction_id' => $transactionId,
    //             'order_id' => $orderId,
    //             'paymob_transaction_id' => $paymobTransactionId,
    //             'hmac' => $hmac,
    //             'has_obj' => isset($data['obj']),
    //             'data_keys' => array_keys($data)
    //         ]);

    //         // Find transaction with improved matching
    //         $transaction = null;
            
    //         // Try by transaction_id first
    //         if ($transactionId) {
    //             $transaction = WalletTransaction::where('status', 'pending')
    //                 ->where('transaction_id', $transactionId)
    //                 ->first();
    //         }
            
    //         // Try by paymob_order_id in metadata
    //         if (!$transaction && $orderId) {
    //             $transaction = WalletTransaction::where('status', 'pending')
    //                 ->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(metadata, "$.paymob_order_id")) = ?', [$orderId])
    //                 ->first();
    //         }
            
    //         // Try by paymob_transaction_id in metadata
    //         if (!$transaction && $paymobTransactionId) {
    //             $transaction = WalletTransaction::where('status', 'pending')
    //                 ->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(metadata, "$.paymob_transaction_id")) = ?', [$paymobTransactionId])
    //                 ->first();
    //         }

    //         if (!$transaction) {
    //             $pendingTransactions = WalletTransaction::where('status', 'pending')
    //                 ->get(['transaction_id', 'metadata'])
    //                 ->toArray();
                
    //             Log::error('Callback: Transaction not found', [
    //                 'transaction_id' => $transactionId,
    //                 'order_id' => $orderId,
    //                 'paymob_transaction_id' => $paymobTransactionId,
    //                 'pending_count' => count($pendingTransactions)
    //             ]);
                
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => translate('Invalid or completed transaction')
    //             ], 400);
    //         }

    //         // Decode metadata safely
    //         $metadata = is_string($transaction->metadata) 
    //             ? json_decode($transaction->metadata, true) 
    //             : $transaction->metadata;
            
    //         if (!is_array($metadata)) {
    //             $metadata = [];
    //         }

    //         // Get gateway from transaction
    //         $gateway = $transaction->gateway;
    //         if (!$gateway) {
    //             Log::error('Callback: Gateway not specified', [
    //                 'transaction_id' => $transaction->transaction_id
    //             ]);
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => translate('Gateway not specified')
    //             ], 400);
    //         }

    //         // Create gateway instance
    //         $this->gateway = PaymentGatewayFactory::create($gateway);
    //         if (!$this->gateway) {
    //             Log::error('Callback: Invalid gateway', ['gateway' => $gateway]);
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => translate('Invalid payment gateway')
    //             ], 400);
    //         }

    //         // Update metadata with paymob IDs if missing
    //         $metadataUpdated = false;
    //         if ($paymobTransactionId && empty($metadata['paymob_transaction_id'])) {
    //             $metadata['paymob_transaction_id'] = $paymobTransactionId;
    //             $metadataUpdated = true;
    //         }
    //         if ($orderId && empty($metadata['paymob_order_id'])) {
    //             $metadata['paymob_order_id'] = $orderId;
    //             $metadataUpdated = true;
    //         }
            
    //         if ($metadataUpdated) {
    //             $transaction->update(['metadata' => json_encode($metadata)]);
    //         }

    //         // Handle callback through gateway
    //         $response = $this->gateway->handleCallback($data);

    //         Log::info('Paymob Gateway Response', [
    //             'transaction_id' => $transaction->transaction_id,
    //             'response' => $response
    //         ]);

    //         // Check if payment succeeded
    //         $paymentSucceeded = false;
            
    //         if (isset($response['status']) && $response['status'] === 'success') {
    //             $paymentSucceeded = true;
    //         }

    //         // Additional check: verify paymob_transaction_id matches
    //         if ($paymentSucceeded && isset($response['paymob_transaction_id'])) {
    //             if ($metadata['paymob_transaction_id'] != $response['paymob_transaction_id']) {
    //                 Log::warning('Callback: Transaction ID mismatch', [
    //                     'expected' => $metadata['paymob_transaction_id'],
    //                     'received' => $response['paymob_transaction_id']
    //                 ]);
    //                 $paymentSucceeded = false;
    //             }
    //         }

    //         if ($paymentSucceeded) {
    //             DB::beginTransaction();
    //             try {
    //                 // Handle order payment vs wallet top-up differently
    //                 if ($transaction->transaction_type === 'order_payment') {
    //                     // For order payment, don't add to wallet
    //                     $transaction->update([
    //                         'status' => 'completed',
    //                         'balance' => $transaction->user->wallet_balance,
    //                         'updated_at' => now(),
    //                     ]);
    //                 } else {
    //                     // For wallet top-up, add credit to wallet
    //                     $transaction->update([
    //                         'status' => 'completed',
    //                         'balance' => $transaction->user->wallet_balance + $transaction->credit,
    //                         'updated_at' => now(),
    //                     ]);
    //                     $transaction->user->increment('wallet_balance', $transaction->credit);
    //                 }

    //                 // Update order if this is an order payment
    //                 if (isset($metadata['order_id'])) {
    //                     Order::where('id', $metadata['order_id'])->update([
    //                         'payment_status' => 'paid',
    //                         'order_status' => 'confirmed',
    //                         'updated_at' => now(),
    //                     ]);

    //                     // Update partial payment if exists
    //                     OrderPartialPayment::where('order_id', $metadata['order_id'])
    //                         ->where('paid_with', $gateway)
    //                         ->update([
    //                             'paid_amount' => $transaction->credit,
    //                             'due_amount' => 0,
    //                             'updated_at' => now(),
    //                         ]);

    //                     // Update order transaction
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
    //                     'amount' => $transaction->credit,
    //                     'new_balance' => $transaction->user->fresh()->wallet_balance
    //                 ]);

    //                 // Send notifications
    //                 $previousBalance = $transaction->balance;
                    
    //                 if ($transaction->transaction_type === 'order_payment' && isset($metadata['order_id'])) {
    //                     // Send order notifications
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
    //                     // Send wallet top-up notifications
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
    //             'trace' => $e->getTraceAsString()
    //         ]);
    //         return response()->json([
    //             'success' => false,
    //             'errors' => [['code' => 'server_error', 'message' => translate('Callback processing failed')]]
    //         ], 500);
    //     }
    // }

    public function handleCallback(Request $request): JsonResponse
    {
        try {
            $data = $request->all();
            $transactionId = $data['transaction_id'] ?? $request->query('transaction_id');
            $orderId = $data['order'] ?? $request->query('order'); // Paymob order ID
            $paymobTransactionId = $data['id'] ?? $request->query('id'); // Paymob transaction ID
            $hmac = $data['hmac'] ?? $request->query('hmac');

            // Log incoming callback data
            Log::info('Callback Data Received', [
                'transaction_id' => $transactionId,
                'order_id' => $orderId,
                'paymob_transaction_id' => $paymobTransactionId,
                'hmac' => $hmac,
                'query' => $request->query(),
                'data' => $data
            ]);

            // Find transaction
            $query = WalletTransaction::where('status', 'pending');
            if ($transactionId) {
                $query->where('transaction_id', $transactionId);
            } elseif ($paymobTransactionId) {
                $query->orWhereRaw('JSON_UNQUOTE(JSON_EXTRACT(metadata, "$.paymob_transaction_id")) = ?', [$paymobTransactionId]);
            } elseif ($orderId) {
                $query->orWhereRaw('JSON_UNQUOTE(JSON_EXTRACT(metadata, "$.paymob_order_id")) = ?', [$orderId]);
            }
            $transaction = $query->first();

            if (!$transaction) {
                // Debug: Log all pending transactions to check metadata
                $pendingTransactions = WalletTransaction::where('status', 'pending')->get(['transaction_id', 'metadata'])->toArray();
                Log::error('Callback: Transaction not found', [
                    'transaction_id' => $transactionId,
                    'order_id' => $orderId,
                    'paymob_transaction_id' => $paymobTransactionId,
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
                $metadata['paymob_transaction_id'] = $paymobTransactionId;
                $transaction->update(['metadata' => json_encode($metadata)]);
            }

            $response = $this->gateway->handleCallback($data);

            if (
                isset($response['status']) && 
                $response['status'] === 'success' && 
                isset($response['paymob_transaction_id']) && 
                $response['paymob_transaction_id'] === 
                ($metadata['paymob_transaction_id'])
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

                        // Update existing OrderTransaction
                        OrderTransaction::where('order_id', $metadata['order_id'])
                            ->where('status', 'pending')
                            ->update([
                                'status' => 'completed',
                                'balance' => $transaction->user->wallet_balance,
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

                    $previousBalance = $transaction->balance - $transaction->credit;

                    // Send WhatsApp notification with receipt for successful wallet top-up
                    if ($transaction->transaction_type === 'order_payment' && isset($metadata['order_id'])) {
                        // Send order payment notifications
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
                        // Send wallet top-up notifications (Email + WhatsApp + Push + In-App)
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
                Log::error('Callback: Payment failed', [
                    'transaction_id' => $transaction->transaction_id,
                    'response' => $response
                ]);
                return response()->json([
                    'success' => false,
                    'message' => $response['error'] ?? translate('Payment failed')
                ], 400);
            }
        } catch (Exception $e) {
            Log::error('Callback handling failed', [
                'error' => $e->getMessage(),
                'transaction_id' => $transactionId,
                'order_id' => $orderId,
                'paymob_transaction_id' => $paymobTransactionId,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'errors' => [['code' => 'server_error', 'message' => translate('Callback processing failed')]]
            ], 500);
        }
    }

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