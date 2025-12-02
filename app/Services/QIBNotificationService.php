<?php

namespace App\Services;

use App\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class QIBNotificationService
{
    protected $whatsappService;

    public function __construct(WhatsAppService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    /**
     * Send OTP notification via email and WhatsApp
     */
    public function sendOTPNotification(User $user, array $data): void
    {
        try {
            $transactionId = $data['transaction_id'] ?? 'N/A';
            $amount = $data['amount'] ?? 0;
            $currency = $data['currency'] ?? 'YER';
            $gateway = $data['gateway'] ?? 'QIB';

            // Send Email
            $this->sendOTPEmail($user, [
                'transaction_id' => $transactionId,
                'amount' => $amount,
                'currency' => $currency,
                'gateway' => $gateway,
            ]);

            // Send WhatsApp
            $this->sendOTPWhatsApp($user, [
                'transaction_id' => $transactionId,
                'amount' => $amount,
                'currency' => $currency,
                'gateway' => $gateway,
            ]);

        } catch (\Exception $e) {
            Log::error('QIB OTP Notification failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Send OTP notification email
     */
    private function sendOTPEmail(User $user, array $data): void
    {
        try {
            // Check if email notifications are enabled
            $emailStatus = \App\Model\BusinessSetting::where('key', 'wallet_topup_email_status_user')->first();
            
            if (!$emailStatus || $emailStatus->value != '1') {
                Log::info('Email notifications disabled for wallet top-up OTP', [
                    'user_id' => $user->id
                ]);
                return;
            }

            if (!$user->email) {
                Log::warning('User has no email address', ['user_id' => $user->id]);
                return;
            }

            $userName = $user->name ?? ($user->f_name . ' ' . $user->l_name);

            Mail::send('emails.qib-otp-notification', [
                'user_name' => $userName,
                'transaction_id' => $data['transaction_id'],
                'amount' => number_format($data['amount'], 2),
                'currency' => $data['currency'],
                'gateway' => $data['gateway'],
                'app_name' => config('app.name'),
                'date' => now()->format('d/m/Y h:i A'),
            ], function ($message) use ($user) {
                $message->to($user->email)
                    ->subject('OTP Sent - Wallet Top-Up Pending');
            });

            Log::info('QIB OTP email sent successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
                'transaction_id' => $data['transaction_id'],
            ]);

        } catch (\Exception $e) {
            Log::error('QIB OTP email failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Send OTP notification via WhatsApp
     */
    private function sendOTPWhatsApp(User $user, array $data): void
    {
        try {
            // Check if WhatsApp notifications are enabled
            $whatsappStatus = \App\Model\BusinessSetting::where('key', 'wallet_topup_whatsapp_status_user')->first();
            
            if (!$whatsappStatus || $whatsappStatus->value != '1') {
                Log::info('WhatsApp notifications disabled for wallet top-up OTP', [
                    'user_id' => $user->id
                ]);
                return;
            }

            if (!$user->phone) {
                Log::warning('User has no phone number', ['user_id' => $user->id]);
                return;
            }

            // Format phone number - adjust based on your country
            $userPhone = $user->phone;
            
            // Remove any + prefix
            $userPhone = ltrim($userPhone, '+');
            
            // Add country code if not present
            // For Yemen: 967, For Egypt: 20, For Saudi: 966
            if (!preg_match('/^(967|20|966)/', $userPhone)) {
                // Remove leading zero and add Yemen country code by default
                $userPhone = '967' . ltrim($userPhone, '0');
            }

            // Prepare message
            $message = $this->buildWhatsAppMessage($user, $data);

            // Send WhatsApp message
            $whatsappResponse = $this->whatsappService->sendMessage($userPhone, $message);

            if (isset($whatsappResponse['success']) && $whatsappResponse['success']) {
                Log::info('QIB OTP WhatsApp sent successfully', [
                    'user_id' => $user->id,
                    'phone' => $userPhone,
                    'transaction_id' => $data['transaction_id'],
                ]);
            } else {
                Log::warning('QIB OTP WhatsApp failed', [
                    'user_id' => $user->id,
                    'phone' => $userPhone,
                    'error' => $whatsappResponse['error'] ?? 'Unknown error',
                ]);
            }

        } catch (\Exception $e) {
            Log::error('QIB OTP WhatsApp exception', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Build WhatsApp message for OTP notification
     */
    private function buildWhatsAppMessage(User $user, array $data): string
    {
        // Use 'name' field from users table
        $customerName = $user->name ?? ($user->f_name . ' ' . $user->l_name);
        $amount = number_format($data['amount'], 2);
        $currency = $data['currency'];
        $transactionId = $data['transaction_id'];
        $date = now()->format('d/m/Y h:i A');
        $appName = config('app.name');

        return "🔔 *{$appName} - OTP Notification*\n\n" .
               "Dear *{$customerName}*,\n\n" .
               "📱 An OTP has been sent to your WhatsApp number registered with QIB Bank.\n\n" .
               "💰 *Transaction Details:*\n" .
               "━━━━━━━━━━━━━━━━━━\n" .
               "Amount: *{$amount} {$currency}*\n" .
               "Transaction ID: `{$transactionId}`\n" .
               "Date: {$date}\n" .
               "Gateway: QIB Bank\n" .
               "━━━━━━━━━━━━━━━━━━\n\n" .
               "⏰ *Please check your WhatsApp for the OTP*\n" .
               "The OTP will expire in 5 minutes.\n\n" .
               "⚠️ Do not share your OTP with anyone.\n\n" .
               "If you did not initiate this transaction, please contact support immediately.\n\n" .
               "Thank you for using {$appName}! 🙏";
    }

    /**
     * Send payment success notification
     */
    public function sendPaymentSuccessNotification(User $user, array $data): void
    {
        try {
            $transactionId = $data['transaction_id'] ?? 'N/A';
            $amount = $data['amount'] ?? 0;
            $currency = $data['currency'] ?? 'YER';
            $newBalance = $data['new_balance'] ?? 0;

            // Send Email
            $this->sendSuccessEmail($user, [
                'transaction_id' => $transactionId,
                'amount' => $amount,
                'currency' => $currency,
                'new_balance' => $newBalance,
            ]);

            // Send WhatsApp
            $this->sendSuccessWhatsApp($user, [
                'transaction_id' => $transactionId,
                'amount' => $amount,
                'currency' => $currency,
                'new_balance' => $newBalance,
            ]);

        } catch (\Exception $e) {
            Log::error('QIB Success Notification failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send success email
     */
    private function sendSuccessEmail(User $user, array $data): void
    {
        try {
            if (!$user->email) {
                return;
            }

            $userName = $user->name ?? ($user->f_name . ' ' . $user->l_name);

            Mail::send('emails.qib-payment-success', [
                'user_name' => $userName,
                'transaction_id' => $data['transaction_id'],
                'amount' => number_format($data['amount'], 2),
                'currency' => $data['currency'],
                'new_balance' => number_format($data['new_balance'], 2),
                'app_name' => config('app.name'),
                'date' => now()->format('d/m/Y h:i A'),
            ], function ($message) use ($user) {
                $message->to($user->email)
                    ->subject('Payment Successful - Wallet Top-Up Completed');
            });

            Log::info('QIB Success email sent', [
                'user_id' => $user->id,
                'transaction_id' => $data['transaction_id'],
            ]);

        } catch (\Exception $e) {
            Log::error('QIB Success email failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send success WhatsApp
     */
    private function sendSuccessWhatsApp(User $user, array $data): void
    {
        try {
            if (!$user->phone) {
                return;
            }

            $userPhone = $user->phone;
            $userPhone = ltrim($userPhone, '+');
            
            if (!preg_match('/^(967|20|966)/', $userPhone)) {
                $userPhone = '967' . ltrim($userPhone, '0');
            }

            $customerName = $user->name ?? ($user->f_name . ' ' . $user->l_name);
            $amount = number_format($data['amount'], 2);
            $currency = $data['currency'];
            $newBalance = number_format($data['new_balance'], 2);
            $transactionId = $data['transaction_id'];
            $date = now()->format('d/m/Y h:i A');
            $appName = config('app.name');

            $message = "✅ *{$appName} - Payment Successful*\n\n" .
                       "Dear *{$customerName}*,\n\n" .
                       "Your wallet has been topped up successfully!\n\n" .
                       "💰 *Transaction Details:*\n" .
                       "━━━━━━━━━━━━━━━━━━\n" .
                       "Amount: *{$amount} {$currency}*\n" .
                       "New Balance: *{$newBalance} {$currency}*\n" .
                       "Transaction ID: `{$transactionId}`\n" .
                       "Date: {$date}\n" .
                       "━━━━━━━━━━━━━━━━━━\n\n" .
                       "Thank you for using {$appName}! 🙏";

            $this->whatsappService->sendMessage($userPhone, $message);

            Log::info('QIB Success WhatsApp sent', [
                'user_id' => $user->id,
                'transaction_id' => $data['transaction_id'],
            ]);

        } catch (\Exception $e) {
            Log::error('QIB Success WhatsApp failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}