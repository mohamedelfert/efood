<?php

namespace App\CentralLogics;

use App\User;
use Exception;
use App\Model\WalletBonus;
use Illuminate\Support\Str;
use App\Model\BusinessSetting;
use App\Model\OrderTransaction;
use App\Model\PointTransitions;
use App\Model\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Brian2694\Toastr\Facades\Toastr;

class CustomerLogic
{

    public static function create_wallet_transaction($user_id, float $amount, $transaction_type, $reference, $order_id = null)
    {
        // Validate amount
        if ($amount <= 0) {
            throw new Exception('Transaction amount must be positive.');
        }

        // Start database transaction
        return DB::transaction(function () use ($user_id, $amount, $transaction_type, $reference, $order_id) {
            // Check if wallet system is enabled
            if (BusinessSetting::where('key', 'wallet_status')->first()?->value != 1) {
                throw new Exception('Wallet system is disabled.');
            }

            // Fetch user with lock to prevent race conditions
            $user = User::lockForUpdate()->find($user_id);
            if (!$user) {
                throw new Exception('User not found.');
            }

            $current_balance = $user->wallet_balance;
            $validTransactionTypes = ['add_fund_by_admin', 'add_fund', 'loyalty_point', 'referrer', 'add_fund_bonus', 'order_place', 'add_fund_from_branch'];
            if (!in_array($transaction_type, $validTransactionTypes)) {
                throw new Exception('Invalid transaction type: ' . $transaction_type);
            }

            $debit = 0.0;
            $credit = 0.0;
            $new_balance = $current_balance;
            $wallet_transaction = null;
            $order_transaction = null;

            // Handle transaction types
            if ($transaction_type === 'order_place') {
                if (!$order_id) {
                    throw new Exception('Order ID is required for order_place transaction.');
                }
                if ($current_balance < $amount) {
                    throw new Exception('Insufficient wallet balance.');
                }

                $debit = $amount;
                $new_balance = $current_balance - $debit;

                // Create order transaction
                $order_transaction = new OrderTransaction();
                $order_transaction->user_id = $user->id;
                $order_transaction->order_id = $order_id;
                $order_transaction->transaction_id = Str::uuid();
                $order_transaction->reference = $reference;
                $order_transaction->transaction_type = $transaction_type;
                $order_transaction->debit = $debit;
                $order_transaction->balance = $new_balance;
                $order_transaction->order_amount = $amount;
                $order_transaction->total_amount = $amount;
                $order_transaction->status = 'completed';

                // Create wallet transaction for order_place to track debit
                $wallet_transaction = new WalletTransaction();
                $wallet_transaction->user_id = $user->id;
                $wallet_transaction->transaction_id = Str::uuid();
                $wallet_transaction->reference = $reference;
                $wallet_transaction->transaction_type = $transaction_type;
                $wallet_transaction->credit = $credit;
                $wallet_transaction->debit = $debit;
                $wallet_transaction->balance = $new_balance;
            } else {
                $credit = $amount;

                // Special handling for loyalty points
                if ($transaction_type === 'loyalty_point') {
                    $exchange_rate = BusinessSetting::where('key', 'loyalty_point_exchange_rate')->first()?->value;
                    if (!$exchange_rate || $exchange_rate <= 0) {
                        throw new Exception('Invalid loyalty point exchange rate.');
                    }
                    $credit = round($amount / $exchange_rate, 2); // Preserve precision
                }

                $new_balance = $current_balance + $credit;

                // Create wallet transaction
                $wallet_transaction = new WalletTransaction();
                $wallet_transaction->user_id = $user->id;
                $wallet_transaction->transaction_id = Str::uuid();
                $wallet_transaction->reference = $reference;
                $wallet_transaction->transaction_type = $transaction_type;
                $wallet_transaction->credit = $credit;
                $wallet_transaction->debit = $debit;
                $wallet_transaction->balance = $new_balance;
            }

            // Update user balance and save transactions
            $user->wallet_balance = $new_balance;
            $user->save();

            if ($wallet_transaction) {
                $wallet_transaction->save();
            }
            if ($order_transaction) {
                $order_transaction->save();
            }

            return $wallet_transaction ?? $order_transaction;
        }, 5); // Retry transaction up to 5 times on deadlock
    }


    // public static function create_wallet_transaction($user_id, float $amount, $transaction_type, $reference, $order_id = null)
    // {   
    //     // Validate amount
    //     if ($amount <= 0) {
    //         throw new Exception('Transaction amount must be positive.');
    //     }

    //     // Start database transaction
    //     return DB::transaction(function () use ($user_id, $amount, $transaction_type, $reference, $order_id) {
    //         // Check if wallet system is enabled
    //         if (BusinessSetting::where('key', 'wallet_status')->first()?->value != 1) {
    //             throw new Exception('Wallet system is disabled.');
    //         }

    //         // Fetch user with lock to prevent race conditions
    //         $user = User::lockForUpdate()->find($user_id);
    //         if (!$user) {
    //             throw new Exception('User not found.');
    //         }

    //         $current_balance = $user->wallet_balance;
    //         $validTransactionTypes = ['add_fund_by_admin', 'add_fund', 'loyalty_point', 'referrer', 'add_fund_bonus', 'order_place'];
    //         if (!in_array($transaction_type, $validTransactionTypes)) {
    //             throw new Exception('Invalid transaction type: ' . $transaction_type);
    //         }

    //         $debit = 0.0;
    //         $credit = 0.0;
    //         $new_balance = $current_balance;
    //         $wallet_transaction = null;
    //         $order_transaction = null;

    //         // Handle transaction types
    //         if ($transaction_type === 'order_place') {
    //             if (!$order_id) {
    //                 throw new Exception('Order ID is required for order_place transaction.');
    //             }
    //             if ($current_balance < $amount) {
    //                 throw new Exception('Insufficient wallet balance.');
    //             }

    //             $debit = $amount;
    //             $new_balance = $current_balance - $debit;

    //             // Create only order transaction for order_place
    //             $order_transaction = new OrderTransaction();
    //             $order_transaction->user_id = $user->id;
    //             $order_transaction->order_id = $order_id;
    //             $order_transaction->transaction_id = Str::uuid();
    //             $order_transaction->reference = $reference;
    //             $order_transaction->transaction_type = $transaction_type;
    //             $order_transaction->debit = $debit;
    //             $order_transaction->balance = $new_balance;
    //             $order_transaction->order_amount = $amount;
    //             $order_transaction->total_amount = $amount;
    //             $order_transaction->status = 'completed';
    //         } else {
    //             $credit = $amount;

    //             // Special handling for loyalty points
    //             if ($transaction_type === 'loyalty_point') {
    //                 $exchange_rate = BusinessSetting::where('key', 'loyalty_point_exchange_rate')->first()?->value;
    //                 if (!$exchange_rate || $exchange_rate <= 0) {
    //                     throw new Exception('Invalid loyalty point exchange rate.');
    //                 }
    //                 $credit = round($amount / $exchange_rate, 2); // Preserve precision
    //             }

    //             $new_balance = $current_balance + $credit;

    //             // Create wallet transaction
    //             $wallet_transaction = new WalletTransaction();
    //             $wallet_transaction->user_id = $user->id;
    //             $wallet_transaction->transaction_id = Str::uuid();
    //             $wallet_transaction->reference = $reference;
    //             $wallet_transaction->transaction_type = $transaction_type;
    //             $wallet_transaction->credit = $credit;
    //             $wallet_transaction->debit = $debit;
    //             $wallet_transaction->balance = $new_balance;
    //         }

    //         // Update user balance and save transactions
    //         $user->wallet_balance = $new_balance;
    //         $user->save();

    //         if ($wallet_transaction) {
    //             $wallet_transaction->save();
    //         }
    //         if ($order_transaction) {
    //             $order_transaction->save();
    //         }

    //         return $wallet_transaction ?? $order_transaction;
    //     }, 5); // Retry transaction up to 5 times on deadlock
    // }


    public static function create_loyalty_point_transaction($user_id, $referance, $amount, $transaction_type)
    {
        $settings = array_column(BusinessSetting::whereIn('key', ['loyalty_point_status', 'loyalty_point_exchange_rate', 'loyalty_point_item_purchase_point'])->get()->toArray(), 'value', 'key');
        if ($settings['loyalty_point_status'] != 1) {
            return true;
        }

        $credit = 0;
        $debit = 0;
        $user = User::find($user_id);

        if (!isset($user)) {
            return false;
        }

        $loyalty_point_transaction = new PointTransitions();
        $loyalty_point_transaction->user_id = $user->id;
        $loyalty_point_transaction->transaction_id = Str::random('30');
        $loyalty_point_transaction->reference = $referance;
        $loyalty_point_transaction->type = $transaction_type;

        if ($transaction_type == 'order_place') {
            $credit = (int) ($amount * $settings['loyalty_point_item_purchase_point'] / 100);
        } else if ($transaction_type == 'point_to_wallet') {
            $debit = $amount;
        }

        $current_balance = $user->point + $credit - $debit;
        $loyalty_point_transaction->amount = $current_balance;
        $loyalty_point_transaction->credit = $credit;
        $loyalty_point_transaction->debit = $debit;
        $loyalty_point_transaction->created_at = now();
        $loyalty_point_transaction->updated_at = now();
        $user->point = $current_balance;

        try {
            DB::beginTransaction();
            $user->save();
            $loyalty_point_transaction->save();
            DB::commit();
            return true;
        } catch (\Exception $ex) {
            info($ex);
            DB::rollback();

            return false;
        }
        return false;
    }


    public static function referral_earning_wallet_transaction($user_id, $transaction_type, $referance)
    {
        $user = User::find($referance);
        $current_balance = $user->wallet_balance;

        $debit = 0.0;
        $credit = 0.0;
        $amount = BusinessSetting::where('key', 'ref_earning_exchange_rate')->first()->value ?? 0;
        $credit = $amount;

        $wallet_transaction = new WalletTransaction();
        $wallet_transaction->user_id = $user->id;
        $wallet_transaction->transaction_id = Str::random('30');
        $wallet_transaction->reference = $user_id;
        $wallet_transaction->transaction_type = $transaction_type;
        $wallet_transaction->credit = $credit;
        $wallet_transaction->debit = $debit;
        $wallet_transaction->balance = $current_balance + $credit;
        $wallet_transaction->created_at = now();
        $wallet_transaction->updated_at = now();
        $user->wallet_balance = $current_balance + $credit;

        try {
            DB::beginTransaction();
            $user->save();
            $wallet_transaction->save();
            DB::commit();
        } catch (\Exception $ex) {
            info($ex);
            DB::rollback();

            return false;
        }
    }

    public static function loyalty_point_wallet_transfer_transaction($user_id, $point, $amount)
    {

        DB::transaction(function () use ($user_id, $point, $amount) {

            //Customer (loyalty_point update)
            $user = User::find($user_id);
            $current_wallet_balance = $user->wallet_balance;
            $current_point = $user->point;
            //dd($current_wallet_balance);

            $user->point -= $point;
            $user->wallet_balance += $amount;
            $user->save();

            WalletTransaction::create([
                'user_id' => $user_id,
                'transaction_id' => Str::random('30'),
                'reference' => null,
                'transaction_type' => 'loyalty_point_to_wallet',
                'debit' => 0,
                'credit' => $amount,
                'balance' => $current_wallet_balance + $amount,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            PointTransitions::create([
                'user_id' => $user_id,
                'transaction_id' => Str::random('30'),
                'reference' => null,
                'type' => 'loyalty_point_to_wallet',
                'debit' => $point,
                'credit' => 0,
                'amount' => $current_point - $point,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    public static function add_to_wallet($customer_id, float $amount)
    {
        $customer = User::find($customer_id);
        $fcm_token = $customer ? $customer->cm_firebase_token : '';
        $bonus_amount = self::add_to_wallet_bonus($customer_id, $amount);
        $reference = 'add-fund';

        $wallet_transaction = self::create_wallet_transaction($customer_id, $amount, 'add_fund', $reference);

        if ($wallet_transaction) {
            $local = $customer ? $customer->language_code : 'en';
            $restaurant_name = Helpers::get_business_settings('restaurant_name');
            $bonus_value = '';

            if ($bonus_amount > 0) {
                $bonus_transaction = self::create_wallet_transaction($customer_id, $bonus_amount, 'add_fund_bonus', 'add-fund-bonus');

                if ($bonus_transaction) {
                    $bonus_message = Helpers::order_status_update_message(ADD_WALLET_BONUS_MESSAGE);

                    if ($local != 'en') {
                        $translated_message = BusinessSetting::with('translations')->where(['key' => ADD_WALLET_BONUS_MESSAGE])->first();
                        if (isset($translated_message->translations)) {
                            foreach ($translated_message->translations as $translation) {
                                if ($local == $translation->locale) {
                                    $bonus_message = $translation->value;
                                }
                            }
                        }
                    }
                    $bonus_value = Helpers::text_variable_data_format(value: $bonus_message, user_name: $customer->name, restaurant_name: $restaurant_name);
                }
            }

            $message = Helpers::order_status_update_message(ADD_WALLET_MESSAGE);

            if ($local != 'en') {
                $translated_message = BusinessSetting::with('translations')->where(['key' => ADD_WALLET_MESSAGE])->first();
                if (isset($translated_message->translations)) {
                    foreach ($translated_message->translations as $translation) {
                        if ($local == $translation->locale) {
                            $message = $translation->value;
                        }
                    }
                }
            }
            $value = Helpers::text_variable_data_format(value: $message, user_name: $customer->name, restaurant_name: $restaurant_name);

            try {
                if ($value) {
                    $data = [
                        'title' => translate('wallet'),
                        'description' => $bonus_amount > 0 ? Helpers::set_symbol($amount) . ' ' . $value . ', ' . Helpers::set_symbol($bonus_amount) . ' ' . $bonus_value : Helpers::set_symbol($amount) . ' ' . $value,
                        'order_id' => '',
                        'image' => '',
                        'type' => 'order_status',
                    ];
                    if (isset($fcm_token)) {
                        Helpers::send_push_notif_to_device($fcm_token, $data);
                    }
                }
                return true;
            } catch (\Exception $e) {
                Toastr::warning(translate('Push notification send failed for Customer!'));
            }
        }

        return false;

    }

    public static function add_to_wallet_bonus($customer_id, float $amount)
    {
        $bonuses = WalletBonus::active()
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->where('minimum_add_amount', '<=', $amount)
            ->get();

        $bonuses = $bonuses->where('minimum_add_amount', $bonuses->max('minimum_add_amount'));

        foreach ($bonuses as $key => $item) {
            $item->applied_bonus_amount = $item->bonus_type == 'percentage' ? ($amount * $item->bonus_amount) / 100 : $item->bonus_amount;

            //max bonus check
            if ($item->bonus_type == 'percentage' && $item->applied_bonus_amount > $item->maximum_bonus_amount) {
                $item->applied_bonus_amount = $item->maximum_bonus_amount;
            }
        }

        return $bonuses->max('applied_bonus_amount') ?? 0;
    }

}
