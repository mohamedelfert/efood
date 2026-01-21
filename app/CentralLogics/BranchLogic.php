<?php

namespace App\CentralLogics;

use App\Model\Branch;
use App\Model\BranchWalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;

class BranchLogic
{
    /**
     * @param int $branch_id
     * @param float $amount
     * @param string $transaction_type
     * @param string|null $reference
     * @return BranchWalletTransaction
     * @throws Exception
     */
    public static function create_wallet_transaction(int $branch_id, float $amount, string $transaction_type, string $reference = null)
    {
        if ($amount <= 0) {
            throw new Exception(translate('Transaction amount must be positive.'));
        }

        return DB::transaction(function () use ($branch_id, $amount, $transaction_type, $reference) {
            $branch = Branch::lockForUpdate()->find($branch_id);
            if (!$branch) {
                throw new Exception(translate('Branch not found.'));
            }

            $current_balance = $branch->wallet_balance;
            $debit = 0.0;
            $credit = 0.0;
            $new_balance = $current_balance;

            if ($transaction_type === 'add_fund_by_admin') {
                $credit = $amount;
                $new_balance = $current_balance + $credit;
            } elseif ($transaction_type === 'order_payment') {
                // This would be used when an order is paid and amount should be added to branch wallet
                $credit = $amount;
                $new_balance = $current_balance + $credit;
            } elseif ($transaction_type === 'cash_out' || $transaction_type === 'fund_customer_wallet') {
                if ($current_balance < $amount) {
                    throw new Exception(translate('Insufficient wallet balance.'));
                }
                $debit = $amount;
                $new_balance = $current_balance - $debit;
            } else {
                throw new Exception(translate('Invalid transaction type.'));
            }

            $wallet_transaction = new BranchWalletTransaction();
            $wallet_transaction->branch_id = $branch->id;
            $wallet_transaction->transaction_id = (string) Str::uuid();
            $wallet_transaction->reference = $reference;
            $wallet_transaction->transaction_type = $transaction_type;
            $wallet_transaction->credit = $credit;
            $wallet_transaction->debit = $debit;
            $wallet_transaction->balance = $new_balance;
            $wallet_transaction->save();

            $branch->wallet_balance = $new_balance;
            $branch->save();

            return $wallet_transaction;
        }, 5);
    }
}
