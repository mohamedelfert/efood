<?php

namespace App\Services;

use App\User;
use App\Model\Order;
use App\Model\Notification;
use App\CentralLogics\Helpers;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\WalletTopUpNotification;
use App\Mail\MoneyTransferNotification;
use App\Mail\OrderPlacedNotification;
use App\Mail\PaymentSuccessNotification;

class NotificationService
{
    /**
     * Send wallet top-up notifications
     */
    public function sendWalletTopUpNotification(User $user, array $data): void
    {
        try {
            $notificationData = [
                'user_name' => $user->name,
                'amount' => $data['amount'],
                'currency' => $data['currency'] ?? 'SAR',
                'transaction_id' => $data['transaction_id'],
                'gateway' => $data['gateway'] ?? 'N/A',
                'new_balance' => $user->wallet_balance,
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ];

            // Email notification
            if ($user->email && ($user->wallet_email_notifications ?? true)) {
                $this->sendEmail($user->email, new WalletTopUpNotification($notificationData));
            }

            // Push notification
            if ($user->cm_firebase_token && ($user->wallet_push_notifications ?? true)) {
                $this->sendPushNotification($user->cm_firebase_token, [
                    'title' => translate('Wallet Top-Up Successful'),
                    'description' => translate('Your wallet has been topped up with :amount :currency', [
                        'amount' => number_format($data['amount'], 2),
                        'currency' => $data['currency'] ?? 'SAR'
                    ]),
                    'type' => 'wallet_topup',
                    'transaction_id' => $data['transaction_id'],
                    'image' => '',
                    'order_id' => '',
                ]);
            }

            // SMS notification (if enabled)
            if ($user->phone && ($user->wallet_sms_notifications ?? false)) {
                $this->sendSMS($user->phone, translate('Your wallet has been topped up with :amount :currency. New balance: :balance', [
                    'amount' => number_format($data['amount'], 2),
                    'currency' => $data['currency'] ?? 'SAR',
                    'balance' => number_format($user->wallet_balance, 2)
                ]));
            }

            // Store in-app notification
            $this->storeInAppNotification($user->id, [
                'title' => 'Wallet Top-Up Successful',
                'description' => "Your wallet has been topped up with {$data['amount']} {$data['currency']}",
                'type' => 'wallet_topup',
                'reference_id' => $data['transaction_id'],
                'amount' => $data['amount'],
                'currency' => $data['currency'],
            ]);

            Log::info('Wallet top-up notification sent', [
                'user_id' => $user->id,
                'transaction_id' => $data['transaction_id']
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send wallet top-up notification', [
                'error' => $e->getMessage(),
                'user_id' => $user->id
            ]);
        }
    }

    /**
     * Send money transfer notifications
     */
    public function sendMoneyTransferNotification(User $sender, User $receiver, array $data): void
    {
        try {
            // Notification for sender
            $senderData = [
                'user_name' => $sender->name,
                'recipient_name' => $receiver->name,
                'amount' => $data['amount'],
                'currency' => $data['currency'] ?? 'SAR',
                'transaction_id' => $data['transaction_id'],
                'new_balance' => $sender->wallet_balance,
                'note' => $data['note'] ?? '',
                'type' => 'sent',
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ];

            if ($sender->email) {
                $this->sendEmail($sender->email, new MoneyTransferNotification(
                    $sender,
                    $receiver,    
                    $data,        
                ));
            }

            // Notification for receiver
            $receiverData = [
                'user_name' => $receiver->name,
                'sender_name' => $sender->name,
                'amount' => $data['amount'],
                'currency' => $data['currency'] ?? 'SAR',
                'transaction_id' => $data['transaction_id'],
                'new_balance' => $receiver->wallet_balance,
                'note' => $data['note'] ?? '',
                'type' => 'received',
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ];

            if ($receiver->email) {
                $this->sendEmail($receiver->email, new MoneyTransferNotification(
                    $sender,      
                    $receiver,    
                    $data,        
                ));
            }

            if ($receiver->cm_firebase_token) {
                $this->sendPushNotification($receiver->cm_firebase_token, [
                    'title' => translate('Money Received'),
                    'description' => translate('You received :amount :currency from :name', [
                        'amount' => number_format($data['amount'], 2),
                        'currency' => $data['currency'] ?? 'SAR',
                        'name' => $sender->name
                    ]),
                    'type' => 'money_transfer_received',
                    'transaction_id' => $data['transaction_id'],
                    'image' => '',
                    'order_id' => '',
                ]);
            }

            // Store in-app notifications
            $this->storeInAppNotification($sender->id, [
                'title' => 'Money Transfer Sent',
                'description' => "You sent {$data['amount']} to {$receiver->name}",
                'type' => 'money_transfer_sent',
                'reference_id' => $data['transaction_id'],
                'amount' => $data['amount'],
                'currency' => $data['currency'],
            ]);

            $this->storeInAppNotification($receiver->id, [
                'title' => 'Money Received',
                'description' => "You received {$data['amount']} from {$sender->name}",
                'type' => 'money_transfer_received',
                'reference_id' => $data['transaction_id'],
                'amount' => $data['amount'],
                'currency' => $data['currency'],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send money transfer notification', [
                'error' => $e->getMessage(),
                'sender_id' => $sender->id,
                'receiver_id' => $receiver->id
            ]);
        }
    }

    /**
     * Send order placed notifications
     */
    public function sendOrderPlacedNotification(User $user, Order $order, array $data): void
    {
        try {
            $notificationData = [
                'user_name' => $user->name,
                'order_id' => $order->id,
                'order_amount' => $order->order_amount,
                'currency' => $data['currency'] ?? 'SAR',
                'payment_method' => $order->payment_method,
                'order_type' => $order->order_type,
                'delivery_date' => $order->delivery_date,
                'delivery_time' => $order->delivery_time,
                'branch_name' => $order->branch->name ?? 'N/A',
                'items_count' => $order->details->count(),
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ];

            // Email notification
            if ($user->email && ($user->order_email_notifications ?? true)) {
                $this->sendEmail($user->email, new OrderPlacedNotification($notificationData));
            }

            // Push notification
            if ($user->cm_firebase_token && ($user->order_push_notifications ?? true)) {
                $this->sendPushNotification($user->cm_firebase_token, [
                    'title' => translate('Order Placed Successfully'),
                    'description' => translate('Your order #:order_id has been placed successfully', [
                        'order_id' => $order->id
                    ]),
                    'type' => 'order_placed',
                    'order_id' => $order->id,
                    'image' => '',
                ]);
            }

            // SMS notification
            if ($user->phone && ($user->order_sms_notifications ?? true)) {
                $this->sendSMS($user->phone, translate('Your order #:order_id worth :amount :currency has been placed successfully', [
                    'order_id' => $order->id,
                    'amount' => number_format($order->order_amount, 2),
                    'currency' => $data['currency'] ?? 'SAR'
                ]));
            }

            // Store in-app notification
            $this->storeInAppNotification($user->id, [
                'title' => 'Order Placed Successfully',
                'description' => "Your order #{$order->id} has been placed",
                'type' => 'order_placed',
                'reference_id' => $order->id,
                'amount' => $order->order_amount,
                'currency' => $data['currency'],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send order placed notification', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'order_id' => $order->id
            ]);
        }
    }

    /**
     * Send payment success notifications
     */
    public function sendPaymentSuccessNotification(User $user, array $data): void
    {
        try {
            $notificationData = [
                'user_name' => $user->name,
                'transaction_id' => $data['transaction_id'],
                'amount' => $data['amount'],
                'currency' => $data['currency'] ?? 'SAR',
                'payment_method' => $data['gateway'] ?? 'N/A',
                'purpose' => $data['purpose'] ?? 'payment',
                'reference_id' => $data['reference_id'] ?? null,
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ];

            // Email notification
            if ($user->email) {
                $this->sendEmail($user->email, new PaymentSuccessNotification($notificationData));
            }

            // Push notification
            if ($user->cm_firebase_token) {
                $this->sendPushNotification($user->cm_firebase_token, [
                    'title' => translate('Payment Successful'),
                    'description' => translate('Your payment of :amount :currency was successful', [
                        'amount' => number_format($data['amount'], 2),
                        'currency' => $data['currency'] ?? 'SAR'
                    ]),
                    'type' => 'payment_success',
                    'transaction_id' => $data['transaction_id'],
                    'image' => '',
                    'order_id' => '',
                ]);
            }

            // Store in-app notification
            $this->storeInAppNotification($user->id, [
                'title' => 'Payment Successful',
                'description' => "Your payment of {$data['amount']} was successful",
                'type' => 'payment_success',
                'reference_id' => $data['transaction_id'],
                'amount' => $data['amount'],
                'currency' => $data['currency'],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send payment success notification', [
                'error' => $e->getMessage(),
                'user_id' => $user->id
            ]);
        }
    }

    /**
     * Send wallet payment used notification
     */
    public function sendWalletPaymentNotification(User $user, array $data): void
    {
        try {
            // Push notification
            if ($user->cm_firebase_token) {
                $this->sendPushNotification($user->cm_firebase_token, [
                    'title' => translate('Wallet Payment'),
                    'description' => translate(':amount :currency deducted from your wallet for :purpose', [
                        'amount' => number_format($data['amount'], 2),
                        'currency' => $data['currency'] ?? 'SAR',
                        'purpose' => $data['purpose'] ?? 'payment'
                    ]),
                    'type' => 'wallet_payment',
                    'transaction_id' => $data['transaction_id'],
                    'image' => '',
                    'order_id' => '',
                ]);
            }

            // Store in-app notification
            $this->storeInAppNotification($user->id, [
                'title' => 'Wallet Payment',
                'description' => "{$data['amount']} deducted from your wallet",
                'type' => 'wallet_payment',
                'reference_id' => $data['transaction_id'],
                'amount' => $data['amount'],
                'currency' => $data['currency'],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send wallet payment notification', [
                'error' => $e->getMessage(),
                'user_id' => $user->id
            ]);
        }
    }

    /**
     * Send loyalty points conversion notification
     */
    public function sendLoyaltyConversionNotification(User $user, array $data): void
    {
        try {
            if ($user->cm_firebase_token) {
                $this->sendPushNotification($user->cm_firebase_token, [
                    'title' => translate('Loyalty Points Converted'),
                    'description' => translate(':points points converted to :amount :currency', [
                        'points' => $data['points_used'],
                        'amount' => number_format($data['converted_amount'], 2),
                        'currency' => $data['currency'] ?? 'SAR'
                    ]),
                    'type' => 'loyalty_conversion',
                    'transaction_id' => $data['transaction_id'],
                    'image' => '',
                    'order_id' => '',
                ]);
            }

            $this->storeInAppNotification($user->id, [
                'title' => 'Loyalty Points Converted',
                'description' => "{$data['points_used']} points converted to {$data['converted_amount']}",
                'type' => 'loyalty_conversion',
                'reference_id' => $data['transaction_id'],
                'amount' => $data['converted_amount'],
                'currency' => $data['currency'],
                'extra' => ['points' => $data['points_used']],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send loyalty conversion notification', [
                'error' => $e->getMessage(),
                'user_id' => $user->id
            ]);
        }
    }

    /**
     * Send email helper
     */
    private function sendEmail(string $email, $mailable): void
    {
        try {
            $emailServices = Helpers::get_business_settings('mail_config');
            if (isset($emailServices['status']) && $emailServices['status'] == 1) {
                Mail::to($email)->send($mailable);
                Log::info('Email sent successfully', ['to' => $email]);
            }
        } catch (\Exception $e) {
            Log::error('Email sending failed', [
                'to' => $email,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send push notification helper
     */
    private function sendPushNotification(string $fcmToken, array $data): void
    {
        try {
            Helpers::send_push_notif_to_device($fcmToken, $data);
            Log::info('Push notification sent', ['fcm_token' => substr($fcmToken, 0, 20) . '...']);
        } catch (\Exception $e) {
            Log::error('Push notification failed', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send SMS helper
     */
    private function sendSMS(string $phone, string $message): void
    {
        try {
            // Implement your SMS gateway integration here
            // Example: Twilio, Nexmo, etc.
            Log::info('SMS sent', ['phone' => $phone, 'message' => $message]);
        } catch (\Exception $e) {
            Log::error('SMS sending failed', [
                'phone' => $phone,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Store in-app notification
     */
    private function storeInAppNotification(int $userId, array $data): void
    {
        try {
            Notification::create([
                'user_id' => $userId,
                'title' => $data['title'],
                'description' => $data['description'],
                'notification_type' => $data['type'],
                'reference_id' => $data['reference_id'] ?? null,
                'status' => 1,
                'is_read' => false,
                'data' => json_encode([
                    'amount' => $data['amount'] ?? null,
                    'currency' => $data['currency'] ?? null,
                    'extra' => $data['extra'] ?? null,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to store in-app notification', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
        }
    }
}