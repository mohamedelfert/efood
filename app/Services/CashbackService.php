<?php

namespace App\Services;

use App\Models\CashbackSetting;
use App\Model\WalletTransaction;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CashbackService
{
    /**
     * Process cashback for wallet top-up
     */
    public function processWalletTopupCashback(User $user, float $amount, string $transactionId): ?float
    {
        $setting = CashbackSetting::active()
            ->forWalletTopup()
            ->first();

        if (!$setting || !$setting->isApplicable($amount)) {
            return null;
        }

        $cashbackAmount = $setting->calculateCashback($amount);
        
        if ($cashbackAmount <= 0) {
            return null;
        }

        return $this->creditCashback(
            $user,
            $cashbackAmount,
            'wallet_topup_cashback',
            "Cashback for wallet top-up #{$transactionId}",
            $transactionId
        );
    }

    /**
     * Process cashback for order
     */
    public function processOrderCashback(User $user, float $orderAmount, int $orderId): ?float
    {
        $setting = CashbackSetting::active()
            ->forOrder()
            ->first();

        if (!$setting || !$setting->isApplicable($orderAmount)) {
            return null;
        }

        $cashbackAmount = $setting->calculateCashback($orderAmount);
        
        if ($cashbackAmount <= 0) {
            return null;
        }

        return $this->creditCashback(
            $user,
            $cashbackAmount,
            'order_cashback',
            "Cashback for order #{$orderId}",
            'ORDER_' . $orderId
        );
    }

    /**
     * Credit cashback to user wallet
     */
    private function creditCashback(
        User $user,
        float $amount,
        string $type,
        string $reference,
        string $relatedId
    ): ?float {
        try {
            DB::beginTransaction();

            $transactionId = 'CASHBACK_' . time() . '_' . $user->id;
            $newBalance = $user->wallet_balance + $amount;

            WalletTransaction::create([
                'user_id' => $user->id,
                'transaction_id' => $transactionId,
                'credit' => $amount,
                'debit' => 0,
                'transaction_type' => $type,
                'reference' => $reference,
                'status' => 'completed',
                'balance' => $newBalance,
                'metadata' => json_encode([
                    'related_transaction' => $relatedId,
                    'cashback_type' => $type,
                    'processed_at' => now()->toDateTimeString()
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $user->increment('wallet_balance', $amount);

            DB::commit();

            Log::info('Cashback credited successfully', [
                'user_id' => $user->id,
                'amount' => $amount,
                'type' => $type,
                'transaction_id' => $transactionId
            ]);

            return $amount;

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Cashback credit failed', [
                'user_id' => $user->id,
                'amount' => $amount,
                'type' => $type,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Get active cashback settings for display
     */
    public function getActiveCashbackSettings(): array
    {
        return [
            'wallet_topup' => CashbackSetting::active()->forWalletTopup()->first(),
            'order' => CashbackSetting::active()->forOrder()->first(),
        ];
    }

    /**
     * Calculate potential cashback (for preview)
     */
    public function calculatePotentialCashback(string $type, float $amount): array
    {
        $setting = CashbackSetting::active()
            ->where('type', $type)
            ->first();

        if (!$setting) {
            return [
                'eligible' => false,
                'amount' => 0,
                'message' => 'No active cashback available'
            ];
        }

        if (!$setting->isApplicable($amount)) {
            return [
                'eligible' => false,
                'amount' => 0,
                'message' => "Minimum amount required: {$setting->min_amount}"
            ];
        }

        $cashbackAmount = $setting->calculateCashback($amount);

        return [
            'eligible' => true,
            'amount' => $cashbackAmount,
            'percentage' => $setting->cashback_type === 'percentage' ? $setting->cashback_value : null,
            'max_cashback' => $setting->max_cashback,
            'message' => "You will receive {$cashbackAmount} cashback"
        ];
    }

    /**
     * Get user's total cashback earned
     */
    public function getUserTotalCashback(int $userId): array
    {
        $walletCashback = WalletTransaction::where('user_id', $userId)
            ->where('transaction_type', 'wallet_topup_cashback')
            ->sum('credit');

        $orderCashback = WalletTransaction::where('user_id', $userId)
            ->where('transaction_type', 'order_cashback')
            ->sum('credit');

        return [
            'wallet_topup_cashback' => $walletCashback,
            'order_cashback' => $orderCashback,
            'total_cashback' => $walletCashback + $orderCashback
        ];
    }
}