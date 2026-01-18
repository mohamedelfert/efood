<?php

namespace App\Services;

use App\User;
use PinResetOTP;
use App\Model\Order;
use PinResetSuccess;
use App\Model\Notification;
use App\CentralLogics\Helpers;
use App\Mail\EmailVerification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\WalletTopUpNotification;
use App\Mail\MoneyTransferNotification;
use App\Mail\LoyaltyConversionNotification;

class NotificationService
{
    protected $whatsapp;

    public function __construct(WhatsAppService $whatsapp)
    {
        $this->whatsapp = $whatsapp;
    }

    /**
     * Helper function to replace placeholders in translations
     */
    private function replaceTranslationPlaceholders(string $text, array $replacements): string
    {
        foreach ($replacements as $key => $value) {
            $text = str_replace([':' . $key, '{' . $key . '}'], $value, $text);
        }
        return $text;
    }

    /**
     * Send login OTP via Email and WhatsApp
     * Removed transaction_id (doesn't exist for login)
     */
    public function sendLoginOTP(User $user, string $otp): array
    {
        $results = ['email' => false, 'whatsapp' => false, 'sms' => false];

        // Email OTP
        if ($user->email) {
            try {
                Mail::to($user->email)->send(new EmailVerification($otp, $user->language_code ?? 'en'));
                $results['email'] = true;

                Log::info('Login OTP email sent', [
                    'user_id' => $user->id,
                    'email' => $user->email
                ]);
            } catch (\Exception $e) {
                Log::error('Login OTP email failed', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // WhatsApp OTP - WITHOUT transaction_id
        if ($user->phone) {
            try {
                $whatsappData = [
                    'user_name' => $user->name,
                    'otp' => $otp,
                    'expiry_minutes' => '5',
                    'timestamp' => now()->format('Y-m-d H:i:s')
                    // NO transaction_id here - it doesn't exist for login!
                ];

                $message = $this->whatsapp->sendTemplateMessage('login_otp', $whatsappData);

                if ($message) {
                    $response = $this->whatsapp->sendMessage($user->phone, $message);
                    $results['whatsapp'] = $response['success'] ?? false;

                    Log::info('Login OTP WhatsApp sent', [
                        'user_id' => $user->id,
                        'phone' => $user->phone,
                        'success' => $results['whatsapp']
                    ]);
                }

            } catch (\Exception $e) {
                Log::error('Login OTP WhatsApp failed', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $results;
    }

    /**
     * Send wallet top-up notifications
     * Email + WhatsApp + Push + In-App
     */
    public function sendWalletTopUpNotification(User $user, array $data): void
    {
        try {
            $notificationData = [
                'user_name' => $user->name,
                'amount' => number_format($data['amount'], 2),
                'currency' => $data['currency'] ?? 'SAR',
                'transaction_id' => $data['transaction_id'],
                'gateway' => $data['gateway'] ?? 'N/A',
                'previous_balance' => number_format($data['previous_balance'] ?? 0, 2),
                'new_balance' => number_format($user->wallet_balance, 2),
                'timestamp' => now()->format('Y-m-d H:i:s'),
                'date' => now()->format('d/m/Y'),
                'time' => now()->format('h:i A'),
            ];

            //  Email Notification
            if ($user->email) {
                try {
                    $emailServices = Helpers::get_business_settings('mail_config');
                    $mailStatus = Helpers::get_business_settings('wallet_topup_mail_status_user');

                    if (isset($emailServices['status']) && $emailServices['status'] == 1 && $mailStatus == 1) {
                        Mail::to($user->email)->send(new WalletTopUpNotification($notificationData, $user->language_code ?? 'en'));

                        Log::info('Wallet top-up email sent', [
                            'user_id' => $user->id,
                            'transaction_id' => $data['transaction_id']
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Wallet top-up email failed', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            //  WhatsApp Notification with Receipt
            if ($user->phone) {
                try {
                    $receiptUrl = null;

                    // Generate receipt
                    try {
                        $receiptGenerator = app(\App\Services\ReceiptGeneratorService::class);
                        $receiptData = [
                            'transaction_id' => $data['transaction_id'],
                            'customer_name' => $user->name,
                            'account_number' => str_pad($user->id, 8, '0', STR_PAD_LEFT),
                            'amount' => $data['amount'],
                            'currency' => $data['currency'] ?? 'SAR',
                            'previous_balance' => $data['previous_balance'] ?? 0,
                            'new_balance' => $user->wallet_balance,
                            'date' => now()->format('d/m/Y'),
                            'time' => now()->format('h:i A'),
                            'tax' => $data['tax'] ?? 0,
                        ];

                        $receiptPath = $receiptGenerator->generateReceiptImage($receiptData);

                        if ($receiptPath && file_exists($receiptPath)) {
                            $filename = basename($receiptPath);
                            $receiptUrl = url('storage/receipts/images/' . $filename);

                            // Add receipt URL to notification data
                            $notificationData['receipt_url'] = $receiptUrl;
                            $notificationData['receipt_link'] = "View your receipt: " . $receiptUrl;
                        }
                    } catch (\Exception $receiptError) {
                        Log::warning('Receipt generation failed', [
                            'error' => $receiptError->getMessage(),
                            'transaction_id' => $data['transaction_id']
                        ]);
                    }

                    // Build WhatsApp message (now includes receipt URL in text)
                    $message = $this->whatsapp->sendTemplateMessage('wallet_topup', $notificationData);

                    // Send as text only (no file attachment)
                    $response = $this->whatsapp->sendMessage($user->phone, $message, null);

                    Log::info('Wallet top-up WhatsApp sent', [
                        'user_id' => $user->id,
                        'transaction_id' => $data['transaction_id'],
                        'has_receipt_url' => !empty($receiptUrl),
                        'success' => $response['success'] ?? false
                    ]);
                } catch (\Exception $e) {
                    Log::error('Wallet top-up WhatsApp failed', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            //  Push Notification
            if ($user->cm_firebase_token) {
                try {
                    Helpers::send_push_notif_to_device($user->cm_firebase_token, [
                        'title' => translate('Wallet Top-Up Successful'),
                        'description' => str_replace([':amount', ':currency'], [number_format($data['amount'], 2), $data['currency'] ?? 'SAR'], translate('Your wallet has been topped up with :amount :currency')),
                        'type' => 'wallet_topup',
                        'transaction_id' => $data['transaction_id'],
                        'image' => '',
                        'order_id' => '',
                    ]);
                } catch (\Exception $e) {
                    Log::error('Wallet top-up push failed', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            //  In-App Notification
            $this->storeInAppNotification($user->id, [
                'title' => translate('Wallet Top-Up Successful'),
                'description' => str_replace([':amount', ':currency'], [$data['amount'], $data['currency']], translate('Your wallet has been topped up with :amount :currency')),
                'type' => 'wallet_topup',
                'reference_id' => $data['transaction_id'],
                'amount' => $data['amount'],
                'currency' => $data['currency'],
            ]);

            Log::info('All wallet top-up notifications processed', [
                'user_id' => $user->id,
                'transaction_id' => $data['transaction_id']
            ]);

        } catch (\Exception $e) {
            Log::error('Wallet top-up notification failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Send money transfer notifications (sender + receiver)
     * Email + WhatsApp + Push + In-App
     */
    public function sendMoneyTransferNotification(User $sender, User $receiver, array $data): void
    {
        try {
            // === SENDER NOTIFICATIONS ===
            $senderData = [
                'user_name' => $sender->name,
                'recipient_name' => $receiver->name,
                'sender_name' => $sender->name,
                'receiver_name' => $receiver->name,
                'amount' => number_format($data['amount'], 2),
                'currency' => $data['currency'] ?? 'SAR',
                'transaction_id' => $data['transaction_id'],
                'new_balance' => number_format($sender->wallet_balance, 2),
                'balance' => number_format($sender->wallet_balance, 2),
                'note' => $data['note'] ?? '',
                'type' => 'sent',
                'timestamp' => now()->format('Y-m-d H:i:s'),
                'date' => now()->format('d/m/Y'),
                'time' => now()->format('h:i A'),
            ];

            // Sender Email
            if ($sender->email) {
                try {
                    Mail::to($sender->email)->send(new MoneyTransferNotification($senderData, $sender->language_code ?? 'en', 'sent'));
                    Log::info('Transfer email sent to sender', ['sender_id' => $sender->id]);
                } catch (\Exception $e) {
                    Log::error('Transfer email to sender failed', ['error' => $e->getMessage()]);
                }
            }

            // Sender WhatsApp
            if ($sender->phone) {
                try {
                    $message = $this->whatsapp->sendTemplateMessage('transfer_sent', $senderData);
                    $this->whatsapp->sendMessage($sender->phone, $message);
                    Log::info('Transfer WhatsApp sent to sender');
                } catch (\Exception $e) {
                    Log::error('Transfer WhatsApp to sender failed', ['error' => $e->getMessage()]);
                }
            }

            // Sender Push
            if ($sender->cm_firebase_token) {
                try {
                    Helpers::send_push_notif_to_device($sender->cm_firebase_token, [
                        'title' => translate('Money Sent'),
                        'description' => str_replace([':amount', ':currency', ':name'], [number_format($data['amount'], 2), $data['currency'] ?? 'SAR', $receiver->name], translate('You sent :amount :currency to :name')),
                        'type' => 'money_transfer_sent',
                        'transaction_id' => $data['transaction_id'],
                        'image' => '',
                        'order_id' => '',
                    ]);
                } catch (\Exception $e) {
                    Log::error('Transfer push to sender failed', ['error' => $e->getMessage()]);
                }
            }

            // Sender In-App
            $this->storeInAppNotification($sender->id, [
                'title' => translate('Money Sent'),
                'description' => str_replace([':amount', ':currency', ':name'], [$data['amount'], $data['currency'], $receiver->name], translate('You sent :amount :currency to :name')),
                'type' => 'money_transfer_sent',
                'reference_id' => $data['transaction_id'],
                'amount' => $data['amount'],
                'currency' => $data['currency'],
            ]);

            // === RECEIVER NOTIFICATIONS ===
            $receiverData = [
                'user_name' => $receiver->name,
                'sender_name' => $sender->name,
                'recipient_name' => $receiver->name,
                'receiver_name' => $receiver->name,
                'amount' => number_format($data['amount'], 2),
                'currency' => $data['currency'] ?? 'SAR',
                'transaction_id' => $data['transaction_id'],
                'new_balance' => number_format($receiver->wallet_balance, 2),
                'balance' => number_format($receiver->wallet_balance, 2),
                'note' => $data['note'] ?? '',
                'type' => 'received',
                'timestamp' => now()->format('Y-m-d H:i:s'),
                'date' => now()->format('d/m/Y'),
                'time' => now()->format('h:i A'),
            ];

            // Receiver Email
            if ($receiver->email) {
                try {
                    Mail::to($receiver->email)->send(new MoneyTransferNotification($receiverData, $receiver->language_code ?? 'en', 'received'));
                    Log::info('Transfer email sent to receiver', ['receiver_id' => $receiver->id]);
                } catch (\Exception $e) {
                    Log::error('Transfer email to receiver failed', ['error' => $e->getMessage()]);
                }
            }

            // Receiver WhatsApp
            if ($receiver->phone) {
                try {
                    $message = $this->whatsapp->sendTemplateMessage('transfer_received', $receiverData);
                    $this->whatsapp->sendMessage($receiver->phone, $message);
                    Log::info('Transfer WhatsApp sent to receiver');
                } catch (\Exception $e) {
                    Log::error('Transfer WhatsApp to receiver failed', ['error' => $e->getMessage()]);
                }
            }

            // Receiver Push
            if ($receiver->cm_firebase_token) {
                try {
                    Helpers::send_push_notif_to_device($receiver->cm_firebase_token, [
                        'title' => translate('Money Received'),
                        'description' => str_replace([':amount', ':currency', ':name'], [number_format($data['amount'], 2), $data['currency'] ?? 'SAR', $sender->name], translate('You received :amount :currency from :name')),
                        'type' => 'money_transfer_received',
                        'transaction_id' => $data['transaction_id'],
                        'image' => '',
                        'order_id' => '',
                    ]);
                } catch (\Exception $e) {
                    Log::error('Transfer push to receiver failed', ['error' => $e->getMessage()]);
                }
            }

            // Receiver In-App
            $this->storeInAppNotification($receiver->id, [
                'title' => translate('Money Received'),
                'description' => str_replace([':amount', ':currency', ':name'], [$data['amount'], $data['currency'], $sender->name], translate('You received :amount :currency from :name')),
                'type' => 'money_transfer_received',
                'reference_id' => $data['transaction_id'],
                'amount' => $data['amount'],
                'currency' => $data['currency'],
            ]);

            Log::info('All transfer notifications processed', [
                'sender_id' => $sender->id,
                'receiver_id' => $receiver->id,
                'transaction_id' => $data['transaction_id']
            ]);

        } catch (\Exception $e) {
            Log::error('Transfer notification failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Send order placed notifications
     * Email + WhatsApp + Push + In-App
     */
    public function sendOrderPlacedNotification(User $user, Order $order, array $data): void
    {
        try {
            $notificationData = [
                'user_name' => $user->name,
                'order_id' => $order->id,
                'order_amount' => number_format($order->order_amount, 2),
                'currency' => $data['currency'] ?? 'SAR',
                'payment_method' => $order->payment_method,
                'order_type' => $order->order_type,
                'delivery_date' => $order->delivery_date,
                'delivery_time' => $order->delivery_time,
                'branch_name' => $order->branch->name ?? 'N/A',
                'items_count' => $order->details->count(),
                'timestamp' => now()->format('Y-m-d H:i:s'),
                'date' => now()->format('d/m/Y'),
                'time' => now()->format('h:i A'),
            ];

            //  Email Notification (uses OrderPlaced mail which is already working)
            if ($user->email) {
                try {
                    $emailServices = Helpers::get_business_settings('mail_config');
                    $orderMailStatus = Helpers::get_business_settings('place_order_mail_status_user');

                    if (isset($emailServices['status']) && $emailServices['status'] == 1 && $orderMailStatus == 1) {
                        Mail::to($user->email)->send(new \App\Mail\OrderPlaced($order->id));
                        Log::info('Order email sent', ['order_id' => $order->id]);
                    }
                } catch (\Exception $e) {
                    Log::error('Order email failed', ['error' => $e->getMessage()]);
                }
            }

            //  WhatsApp Notification
            if ($user->phone) {
                try {
                    $message = $this->whatsapp->sendTemplateMessage('order_placed', $notificationData);
                    $this->whatsapp->sendMessage($user->phone, $message);
                    Log::info('Order WhatsApp sent', ['order_id' => $order->id]);
                } catch (\Exception $e) {
                    Log::error('Order WhatsApp failed', ['error' => $e->getMessage()]);
                }
            }

            //  Push Notification
            if ($user->cm_firebase_token) {
                try {
                    Helpers::send_push_notif_to_device($user->cm_firebase_token, [
                        'title' => translate('Order Placed Successfully'),
                        'description' => str_replace(':order_id', $order->id, translate('Your order #:order_id has been placed')),
                        'type' => 'order_placed',
                        'order_id' => $order->id,
                        'image' => '',
                    ]);
                } catch (\Exception $e) {
                    Log::error('Order push failed', ['error' => $e->getMessage()]);
                }
            }

            //  In-App Notification
            $this->storeInAppNotification($user->id, [
                'title' => translate('Order Placed Successfully'),
                'description' => str_replace(':order_id', $order->id, translate('Your order #:order_id has been placed')),
                'type' => 'order_placed',
                'reference_id' => $order->id,
                'amount' => $order->order_amount,
                'currency' => $data['currency'],
            ]);

            Log::info('All order notifications processed', ['order_id' => $order->id]);

        } catch (\Exception $e) {
            Log::error('Order notification failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Send loyalty conversion notification
     * Email + WhatsApp + Push + In-App
     */
    public function sendLoyaltyConversionNotification(User $user, array $data): void
    {
        try {
            $notificationData = [
                'user_name' => $user->name,
                'points_used' => $data['points_used'],
                'converted_amount' => number_format($data['converted_amount'], 2),
                'currency' => $data['currency'] ?? 'SAR',
                'transaction_id' => $data['transaction_id'],
                'new_balance' => number_format($user->wallet_balance, 2),
                'remaining_points' => $user->point,
                'timestamp' => now()->format('Y-m-d H:i:s'),
                'date' => now()->format('d/m/Y'),
                'time' => now()->format('h:i A'),
            ];

            // Email
            if ($user->email) {
                try {
                    Mail::to($user->email)->send(new LoyaltyConversionNotification($notificationData, $user->language_code ?? 'en'));
                    Log::info('Loyalty conversion email sent');
                } catch (\Exception $e) {
                    Log::error('Loyalty email failed', ['error' => $e->getMessage()]);
                }
            }

            // WhatsApp
            if ($user->phone) {
                try {
                    $message = $this->whatsapp->sendTemplateMessage('loyalty_conversion', $notificationData);
                    $this->whatsapp->sendMessage($user->phone, $message);
                    Log::info('Loyalty WhatsApp sent');
                } catch (\Exception $e) {
                    Log::error('Loyalty WhatsApp failed', ['error' => $e->getMessage()]);
                }
            }

            // Push
            if ($user->cm_firebase_token) {
                try {
                    Helpers::send_push_notif_to_device($user->cm_firebase_token, [
                        'title' => translate('Loyalty Points Converted'),
                        'description' => str_replace([':points', ':amount', ':currency'], [$data['points_used'], number_format($data['converted_amount'], 2), $data['currency']], translate('Converted :points points to :amount :currency')),
                        'type' => 'loyalty_conversion',
                        'transaction_id' => $data['transaction_id'],
                        'image' => '',
                        'order_id' => '',
                    ]);
                } catch (\Exception $e) {
                    Log::error('Loyalty push failed', ['error' => $e->getMessage()]);
                }
            }

            // In-App
            $this->storeInAppNotification($user->id, [
                'title' => translate('Loyalty Points Converted'),
                'description' => str_replace([':points', ':amount', ':currency'], [$data['points_used'], $data['converted_amount'], $data['currency']], translate('Converted :points points to :amount :currency')),
                'type' => 'loyalty_conversion',
                'reference_id' => $data['transaction_id'],
                'amount' => $data['converted_amount'],
                'currency' => $data['currency'],
            ]);

        } catch (\Exception $e) {
            Log::error('Loyalty notification failed', ['error' => $e->getMessage()]);
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
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to store in-app notification', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Send PIN Reset OTP via Email and WhatsApp
     */
    public function sendPinResetOTP(User $user, string $otp): array
    {
        $results = ['email' => false, 'whatsapp' => false];

        // Email OTP
        if ($user->email) {
            try {
                $emailData = [
                    'user_name' => $user->name,
                    'otp' => $otp,
                    'expiry_minutes' => '5',
                    'timestamp' => now()->format('Y-m-d H:i:s'),
                    'purpose' => 'Wallet PIN Reset'
                ];

                Mail::to($user->email)->send(new \App\Mail\PinResetOTP($emailData, $user->language_code ?? 'en'));
                $results['email'] = true;

                Log::info('PIN Reset OTP email sent', [
                    'user_id' => $user->id,
                    'email' => $user->email
                ]);
            } catch (\Exception $e) {
                Log::error('PIN Reset OTP email failed', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // WhatsApp OTP
        if ($user->phone) {
            try {
                $whatsappData = [
                    'user_name' => $user->name,
                    'otp' => $otp,
                    'expiry_minutes' => '5',
                    'timestamp' => now()->format('Y-m-d H:i:s'),
                    'purpose' => 'Wallet PIN Reset'
                ];

                Log::info('Preparing PIN Reset OTP WhatsApp', [
                    'user_id' => $user->id,
                    'phone' => $user->phone,
                    'template_data' => $whatsappData
                ]);

                $message = $this->whatsapp->sendTemplateMessage('pin_reset_otp', $whatsappData);

                Log::info('PIN Reset OTP template generated', [
                    'message_length' => strlen($message),
                    'message_preview' => substr($message, 0, 200)
                ]);

                if ($message) {
                    $response = $this->whatsapp->sendMessage($user->phone, $message);
                    $results['whatsapp'] = $response['success'] ?? false;

                    Log::info('PIN Reset OTP WhatsApp sent', [
                        'user_id' => $user->id,
                        'phone' => $user->phone,
                        'success' => $results['whatsapp'],
                        'response' => $response
                    ]);
                } else {
                    Log::warning('PIN Reset OTP template returned empty message');
                }

            } catch (\Exception $e) {
                Log::error('PIN Reset OTP WhatsApp failed', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        return $results;
    }

    /**
     * Send PIN Reset Success Notification
     */
    public function sendPinResetSuccessNotification(User $user): void
    {
        try {
            $notificationData = [
                'user_name' => $user->name,
                'timestamp' => now()->format('Y-m-d H:i:s'),
                'date' => now()->format('d/m/Y'),
                'time' => now()->format('h:i A'),
            ];

            // Email Notification
            if ($user->email) {
                try {
                    Mail::to($user->email)->send(new \App\Mail\PinResetSuccess($notificationData, $user->language_code ?? 'en'));

                    Log::info('PIN reset success email sent', [
                        'user_id' => $user->id
                    ]);
                } catch (\Exception $e) {
                    Log::error('PIN reset success email failed', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // WhatsApp Notification
            if ($user->phone) {
                try {
                    $message = $this->whatsapp->sendTemplateMessage('pin_reset_success', $notificationData);
                    $this->whatsapp->sendMessage($user->phone, $message);

                    Log::info('PIN reset success WhatsApp sent', [
                        'user_id' => $user->id
                    ]);
                } catch (\Exception $e) {
                    Log::error('PIN reset success WhatsApp failed', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Push Notification
            if ($user->cm_firebase_token) {
                try {
                    Helpers::send_push_notif_to_device($user->cm_firebase_token, [
                        'title' => translate('Wallet PIN Reset'),
                        'description' => translate('Your wallet PIN has been reset successfully'),
                        'type' => 'pin_reset_success',
                        'image' => '',
                        'order_id' => '',
                    ]);
                } catch (\Exception $e) {
                    Log::error('PIN reset success push failed', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // In-App Notification
            $this->storeInAppNotification($user->id, [
                'title' => translate('Wallet PIN Reset'),
                'description' => translate('Your wallet PIN has been reset successfully'),
                'type' => 'pin_reset_success',
                'reference_id' => null,
            ]);

        } catch (\Exception $e) {
            Log::error('PIN reset success notification failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}