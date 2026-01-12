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
        // Get best cashback setting (branch-specific or global)
        $setting = CashbackSetting::getBestCashback('wallet_topup', $user->branch_id ?? null, $amount);

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
        // Get best cashback setting (branch-specific or global)
        $setting = CashbackSetting::getBestCashback('order', $user->branch_id ?? null, $orderAmount);

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
    public function calculatePotentialCashback(string $type, float $amount, ?int $branchId = null): array
    {
        $setting = CashbackSetting::getBestCashback($type, $branchId, $amount);

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
            'cashback_type' => $setting->cashback_type,
            'cashback_value' => $setting->cashback_value,
            'percentage' => $setting->cashback_type === 'percentage' ? $setting->cashback_value : null,
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
            'wallet_topup_cashback' => (float) $walletCashback,
            'order_cashback' => (float) $orderCashback,
            'total_cashback' => (float) ($walletCashback + $orderCashback)
        ];
    }

    /**
     * Get cashback transaction history
     */
    public function getCashbackHistory(int $userId, int $limit = 20, int $offset = 0): array
    {
        $query = WalletTransaction::where('user_id', $userId)
            ->whereIn('transaction_type', ['wallet_topup_cashback', 'order_cashback'])
            ->orderBy('created_at', 'desc');

        $total = $query->count();
        
        $transactions = $query->offset($offset)
            ->limit($limit)
            ->get()
            ->map(function ($transaction) {
                $metadata = json_decode($transaction->metadata, true) ?? [];
                
                return [
                    'id' => $transaction->id,
                    'transaction_id' => $transaction->transaction_id,
                    'type' => $transaction->transaction_type,
                    'amount' => (float) $transaction->credit,
                    'balance' => (float) $transaction->balance,
                    'reference' => $transaction->reference,
                    'related_transaction' => $metadata['related_transaction'] ?? null,
                    'cashback_type' => $metadata['cashback_type'] ?? null,
                    'status' => $transaction->status ?? 'completed',
                    'created_at' => $transaction->created_at->toDateTimeString(),
                    'formatted_date' => $transaction->created_at->format('d M Y, h:i A'),
                ];
            });

        return [
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
            'transactions' => $transactions->toArray()
        ];
    }
}