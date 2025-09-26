<?php

namespace App\Http\Controllers;

use Exception;
use App\Model\Order;
use Illuminate\Http\Request;
use App\Model\OrderTransaction;
use App\Model\WalletTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Models\OrderPartialPayment;
use Illuminate\Support\Facades\Log;
use App\Services\Payment\PaymentGatewayFactory;

class PaymentsController extends Controller
{
    protected $gateway;

    public function __construct(Request $request)
    {
        $gatewayType = $request->input('gateway', 'paymob');
        $this->gateway = PaymentGatewayFactory::create($gatewayType);
        if (!$this->gateway) {
            throw new Exception('Invalid payment gateway');
        }
    }

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

                    return response()->json([
                        'success' => true,
                        'message' => translate('Payment processed successfully'),
                        'transaction_id' => $transaction->transaction_id
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
}