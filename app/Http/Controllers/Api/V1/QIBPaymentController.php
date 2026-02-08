<?php

namespace App\Http\Controllers\Api\V1;

use Exception;
use App\User;
use App\Model\Order;
use App\CentralLogics\Helpers;
use Illuminate\Http\Request;
use App\Model\WalletTransaction;
use App\Model\OrderTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use App\Models\OrderPartialPayment;
use Illuminate\Support\Facades\Validator;
use App\Services\Payment\PaymentGatewayFactory;

class QIBPaymentController extends Controller
{
    /**
     * Confirm wallet top-up with OTP
     */
    public function confirmTopUp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required|string',
            'otp' => 'required|integer|digits:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        try {
            $user = $request->user();

            // Find pending wallet transaction
            $transaction = WalletTransaction::where('transaction_id', $request->transaction_id)
                ->where('user_id', $user->id)
                ->where('status', 'pending')
                ->where('gateway', 'qib')
                ->first();

            if (!$transaction) {
                return response()->json([
                    'errors' => [['code' => 'transaction_not_found', 'message' => translate('Transaction not found or already processed')]]
                ], 404);
            }

            // Decode metadata
            $metadata = is_string($transaction->metadata)
                ? json_decode($transaction->metadata, true)
                : $transaction->metadata;

            // Prepare QIB confirmation data
            $confirmData = [
                'payment_CustomerNo' => $metadata['payment_CustomerNo'],
                'payment_DestNation' => 44124478,
                'payment_Code' => $metadata['payment_Code'],
                'payment_Amount' => $transaction->credit,
                'payment_Curr' => $metadata['currency'] ?? 'YER',
                'Payment_OTP' => $request->otp,
            ];

            // Confirm payment with QIB
            $gateway = PaymentGatewayFactory::create('qib');
            $response = $gateway->confirmPayment($confirmData);

            if (!isset($response['status']) || !$response['status']) {
                Log::error('QIB Confirmation Failed', [
                    'transaction_id' => $transaction->transaction_id,
                    'response' => $response
                ]);

                return response()->json([
                    'success' => false,
                    'errors' => [['code' => 'otp_failed', 'message' => $response['error'] ?? translate('Invalid OTP or confirmation failed')]]
                ], 400);
            }

            // Update transaction and wallet
            DB::beginTransaction();
            try {
                // Update transaction status
                $transaction->update([
                    'status' => 'completed',
                    'balance' => $user->wallet_balance + $transaction->credit,
                    'metadata' => json_encode(array_merge($metadata, [
                        'qib_transaction_id' => $response['transaction_id'] ?? null,
                        'confirmed_at' => now()->toDateTimeString(),
                    ])),
                    'updated_at' => now(),
                ]);

                // Update user wallet balance
                $user->increment('wallet_balance', $transaction->credit);

                DB::commit();

                Log::info('QIB Top-up Confirmed', [
                    'user_id' => $user->id,
                    'transaction_id' => $transaction->transaction_id,
                    'amount' => $transaction->credit,
                    'new_balance' => $user->wallet_balance
                ]);

                // Send success notification
                try {
                    $notificationService = app(\App\Services\QIBNotificationService::class);
                    $notificationService->sendPaymentSuccessNotification($user, [
                        'transaction_id' => $transaction->transaction_id,
                        'amount' => $transaction->credit,
                        'currency' => $metadata['currency'] ?? 'YER',
                        'new_balance' => $user->wallet_balance,
                    ]);
                } catch (\Exception $e) {
                    Log::warning('QIB success notification failed', [
                        'error' => $e->getMessage(),
                        'transaction_id' => $transaction->transaction_id
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => translate('Wallet topped up successfully'),
                    'transaction_id' => $transaction->transaction_id,
                    'amount' => $transaction->credit,
                    'new_balance' => $user->wallet_balance,
                ], 200);

            } catch (Exception $e) {
                DB::rollBack();
                Log::error('QIB Top-up DB Update Failed', [
                    'error' => $e->getMessage(),
                    'transaction_id' => $transaction->transaction_id
                ]);

                return response()->json([
                    'success' => false,
                    'errors' => [['code' => 'update_failed', 'message' => translate('Failed to update wallet')]]
                ], 500);
            }

        } catch (Exception $e) {
            Log::error('QIB confirmTopUp Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'errors' => [['code' => 'server_error', 'message' => translate('Confirmation failed')]]
            ], 500);
        }
    }

    /**
     * Confirm order payment with OTP
     */
    public function confirmOrder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|integer',
            'transaction_id' => 'required|string',
            'otp' => 'required|integer|digits:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        try {
            $user = $request->user();

            // Find pending wallet transaction
            $transaction = WalletTransaction::where('transaction_id', $request->transaction_id)
                ->where('user_id', $user->id)
                ->where('status', 'pending')
                ->where('gateway', 'qib')
                ->first();

            if (!$transaction) {
                return response()->json([
                    'errors' => [['code' => 'transaction_not_found', 'message' => translate('Transaction not found or already processed')]]
                ], 404);
            }

            // Find order
            $order = Order::where('id', $request->order_id)
                ->where('user_id', $user->id)
                ->first();

            if (!$order) {
                return response()->json([
                    'errors' => [['code' => 'order_not_found', 'message' => translate('Order not found')]]
                ], 404);
            }

            // Decode metadata
            $metadata = is_string($transaction->metadata)
                ? json_decode($transaction->metadata, true)
                : $transaction->metadata;

            // Prepare QIB confirmation data
            $confirmData = [
                'payment_CustomerNo' => $metadata['payment_CustomerNo'],
                'payment_DestNation' => 44124478,
                'payment_Code' => $metadata['payment_Code'],
                'payment_Amount' => $transaction->credit,
                'payment_Curr' => $metadata['currency'] ?? 'YER',
                'Payment_OTP' => $request->otp,
            ];

            // Confirm payment with QIB
            $gateway = PaymentGatewayFactory::create('qib');
            $response = $gateway->confirmPayment($confirmData);

            if (!isset($response['status']) || !$response['status']) {
                Log::error('QIB Order Confirmation Failed', [
                    'transaction_id' => $transaction->transaction_id,
                    'order_id' => $order->id,
                    'response' => $response
                ]);

                return response()->json([
                    'success' => false,
                    'errors' => [['code' => 'otp_failed', 'message' => $response['error'] ?? translate('Invalid OTP or confirmation failed')]]
                ], 400);
            }

            // Update transaction, order, and wallet
            DB::beginTransaction();
            try {
                // Update transaction status
                $transaction->update([
                    'status' => 'completed',
                    'balance' => $user->wallet_balance,
                    'metadata' => json_encode(array_merge($metadata, [
                        'qib_transaction_id' => $response['transaction_id'] ?? null,
                        'confirmed_at' => now()->toDateTimeString(),
                    ])),
                    'updated_at' => now(),
                ]);

                // Update order
                $order->update([
                    'payment_status' => 'paid',
                    'order_status' => 'confirmed',
                    'updated_at' => now(),
                ]);

                // Update OrderTransaction
                OrderTransaction::where('order_id', $order->id)
                    ->where('status', 'pending')
                    ->update([
                        'status' => 'completed',
                        'balance' => $user->wallet_balance,
                        'updated_at' => now(),
                    ]);

                // Update OrderPartialPayment if exists
                OrderPartialPayment::where('order_id', $order->id)
                    ->where('paid_with', 'qib')
                    ->update([
                        'paid_amount' => $transaction->credit,
                        'due_amount' => 0,
                        'updated_at' => now(),
                    ]);

                DB::commit();

                Log::info('QIB Order Payment Confirmed', [
                    'user_id' => $user->id,
                    'order_id' => $order->id,
                    'transaction_id' => $transaction->transaction_id,
                    'amount' => $transaction->credit
                ]);

                return response()->json([
                    'success' => true,
                    'message' => translate('Order payment confirmed successfully'),
                    'order_id' => $order->id,
                    'transaction_id' => $transaction->transaction_id,
                    'amount' => $transaction->credit,
                ], 200);

            } catch (Exception $e) {
                DB::rollBack();
                Log::error('QIB Order Confirmation DB Update Failed', [
                    'error' => $e->getMessage(),
                    'transaction_id' => $transaction->transaction_id,
                    'order_id' => $order->id
                ]);

                return response()->json([
                    'success' => false,
                    'errors' => [['code' => 'update_failed', 'message' => translate('Failed to update order')]]
                ], 500);
            }

        } catch (Exception $e) {
            Log::error('QIB confirmOrder Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'errors' => [['code' => 'server_error', 'message' => translate('Confirmation failed')]]
            ], 500);
        }
    }

    /**
     * Resend OTP
     */
    public function resendOTP(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required|string',
            'expired_otp' => 'required|integer|digits:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        try {
            $user = $request->user();

            // Find pending transaction
            $transaction = WalletTransaction::where('transaction_id', $request->transaction_id)
                ->where('user_id', $user->id)
                ->where('status', 'pending')
                ->where('gateway', 'qib')
                ->first();

            if (!$transaction) {
                return response()->json([
                    'errors' => [['code' => 'transaction_not_found', 'message' => translate('Transaction not found')]]
                ], 404);
            }

            // Decode metadata
            $metadata = is_string($transaction->metadata)
                ? json_decode($transaction->metadata, true)
                : $transaction->metadata;

            // Prepare resend data
            $resendData = [
                'payment_CustomerNo' => $metadata['payment_CustomerNo'],
                'payment_DestNation' => 44124478,
                'payment_Code' => $metadata['payment_Code'],
                'payment_Amount' => $transaction->credit,
                'payment_Curr' => $metadata['currency'] ?? 'YER',
                'Payment_OTP' => $request->expired_otp,
            ];

            // Resend OTP
            $gateway = PaymentGatewayFactory::create('qib');
            $response = $gateway->resendOTP($resendData);

            if (!isset($response['status']) || !$response['status']) {
                return response()->json([
                    'success' => false,
                    'errors' => [['code' => 'resend_failed', 'message' => $response['error'] ?? translate('Failed to resend OTP')]]
                ], 400);
            }

            Log::info('QIB OTP Resent', [
                'user_id' => $user->id,
                'transaction_id' => $transaction->transaction_id
            ]);

            return response()->json([
                'success' => true,
                'message' => translate('OTP resent successfully'),
                'transaction_id' => $transaction->transaction_id,
            ], 200);

        } catch (Exception $e) {
            Log::error('QIB resendOTP Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'errors' => [['code' => 'server_error', 'message' => translate('Resend failed')]]
            ], 500);
        }
    }
}