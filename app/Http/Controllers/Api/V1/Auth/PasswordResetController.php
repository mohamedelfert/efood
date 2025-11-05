<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\CentralLogics\Helpers;
use App\CentralLogics\SMS_module;
use App\Http\Controllers\Controller;
use App\Mail\ResetPasswordOtp;
use App\Models\LoginSetup;
use App\Models\Setting;
use App\Traits\HelperTrait;
use App\User;
use Carbon\CarbonInterval;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Modules\Gateways\Traits\SmsGateway;
use App\Services\WhatsAppService;

class PasswordResetController extends Controller
{
    use HelperTrait;

    public function __construct(
        private User $user,
        private LoginSetup $loginSetup,
        private WhatsAppService $whatsapp
    ) {}

    /**
     * Check if OTP should be exposed in response (debug/local only)
     */
    private function shouldExposeOtp(): bool
    {
        return config('app.debug') || env('APP_MODE') !== 'live';
    }

    public function passwordResetRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email_or_phone' => 'required',
            'type' => 'required|in:phone,email',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        if ($request['type'] == 'phone') {
            $customer = $this->user->where(['phone' => $request['email_or_phone']])->first();
        } else {
            $customer = $this->user->where(['email' => $request['email_or_phone']])->first();
        }

        if (!isset($customer)) {
            return response()->json(['errors' => [['code' => 'not-found', 'message' => translate('Customer not found!')]]], 404);
        }

        $OTPIntervalTime = Helpers::get_business_settings('otp_resend_time') ?? 60;
        $passwordVerificationData = DB::table('password_resets')->where('email_or_phone', $request['email_or_phone'])->first();

        if ($passwordVerificationData && isset($passwordVerificationData->created_at) && Carbon::parse($passwordVerificationData->created_at)->DiffInSeconds() < $OTPIntervalTime) {
            $time = $OTPIntervalTime - Carbon::parse($passwordVerificationData->created_at)->DiffInSeconds();

            return response()->json([
                'errors' => [[
                    'code' => 'otp',
                    'message' => translate('please_try_again_after_') . $time . ' ' . translate('seconds')
                ]]
            ], 403);
        }

        $token = (env('APP_MODE') == 'live') ? rand(100000, 999999) : 123456;

        DB::table('password_resets')->updateOrInsert(['email_or_phone' => $request['email_or_phone']], [
            'token' => $token,
            'created_at' => now(),
        ]);

        // ==========================================
        // WHATSAPP OTP 
        // ==========================================
        $whatsappMessage = translate('Your password reset verification code is: ') . $token;
        $whatsappSent = false;

        if ($customer->phone) {
            try {
                $whatsappResult = $this->whatsapp->sendMessage($customer->phone, $whatsappMessage);

                if ($whatsappResult['success']) {
                    $whatsappSent = true;
                    Log::info('WhatsApp password reset OTP sent', [
                        'type' => $request['type'],
                        'identifier' => $request['email_or_phone'],
                        'phone' => $customer->phone,
                        'otp' => $token
                    ]);
                } else {
                    Log::warning('WhatsApp password reset OTP failed', [
                        'type' => $request['type'],
                        'identifier' => $request['email_or_phone'],
                        'phone' => $customer->phone,
                        'otp' => $token,
                        'error' => $whatsappResult['error'] ?? 'Unknown error'
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('WhatsApp password reset OTP exception', [
                    'type' => $request['type'],
                    'identifier' => $request['email_or_phone'],
                    'phone' => $customer->phone,
                    'otp' => $token,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // ==========================================
        // TYPE-SPECIFIC DELIVERY (FALLBACK/DUAL)
        // ==========================================
        if ($request['type'] == 'phone') {
            $activeSMSGatewaysCount = $this->getActiveSMSGatewayCount();

            if (!$whatsappSent && $activeSMSGatewaysCount == 0) {
                return response()->json(['errors' => [['code' => 'otp', 'message' => translate('Unable to send OTP')]]], 404);
            }

            if (!$whatsappSent) {
                $publishedStatus = config('get_payment_publish_status')[0]['is_published'] ?? 0;

                if ($publishedStatus == 1) {
                    $response = SmsGateway::send($customer->phone, $token);
                } else {
                    $response = SMS_module::send($customer->phone, $token);
                }

                Log::info('SMS fallback used for password reset', [
                    'phone' => $customer->phone,
                    'otp' => $token
                ]);

                $payload = ['message' => $response];
                if ($this->shouldExposeOtp()) {
                    $payload['debug_otp'] = $token;
                }
                return response()->json($payload, 200);
            }

            // WhatsApp success
            $payload = ['message' => translate('OTP sent successfully via WhatsApp')];
            if ($this->shouldExposeOtp()) {
                $payload['debug_otp'] = $token;
            }
            return response()->json($payload, 200);

        } else {
            // EMAIL FLOW
            try {
                $emailServices = Helpers::get_business_settings('mail_config');
                $mailStatus = Helpers::get_business_settings('forget_password_mail_status_user');

                if (isset($emailServices['status']) && $emailServices['status'] == 1 && $mailStatus == 1) {
                    Mail::to($customer->email)->send(new ResetPasswordOtp($token, $customer->language_code));

                    Log::info('Email password reset OTP sent', [
                        'email' => $customer->email,
                        'otp' => $token
                    ]);

                    if ($whatsappSent) {
                        $payload = ['message' => translate('OTP sent successfully via Email and WhatsApp')];
                    } else {
                        $payload = ['message' => translate('Email sent successfully.')];
                    }

                    if ($this->shouldExposeOtp()) {
                        $payload['debug_otp'] = $token;
                    }

                    return response()->json($payload, 200);
                }
            } catch (\Exception $exception) {
                Log::error('Email password reset OTP failed', [
                    'email' => $customer->email,
                    'otp' => $token,
                    'error' => $exception->getMessage()
                ]);

                if ($whatsappSent) {
                    $payload = ['message' => translate('OTP sent via WhatsApp (email delivery failed)')];
                    if ($this->shouldExposeOtp()) {
                        $payload['debug_otp'] = $token;
                    }
                    return response()->json($payload, 200);
                }

                return response()->json(['errors' => [
                    ['code' => 'otp', 'message' => translate('Unable to send OTP.')]
                ]], 400);
            }

            // Email disabled but WhatsApp worked
            if ($whatsappSent) {
                $payload = ['message' => translate('OTP sent successfully via WhatsApp')];
                if ($this->shouldExposeOtp()) {
                    $payload['debug_otp'] = $token;
                }
                return response()->json($payload, 200);
            }

            return response()->json(['errors' => [
                ['code' => 'otp', 'message' => translate('Unable to send OTP.')]
            ]], 400);
        }
    }

    // verifyToken() and resetPasswordSubmit() remain unchanged
    // (You can keep them exactly as they were)

    public function verifyToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email_or_phone' => 'required',
            'reset_token' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $maxOTPHit = Helpers::get_business_settings('maximum_otp_hit') ?? 5;
        $maxOTPHitTime = Helpers::get_business_settings('otp_resend_time') ?? 60;
        $tempBlockTime = Helpers::get_business_settings('temporary_block_time') ?? 600;

        $verify = DB::table('password_resets')
            ->where(['token' => $request['reset_token'], 'email_or_phone' => $request['email_or_phone']])
            ->first();

        if (isset($verify)) {
            if (isset($verify->temp_block_time) && Carbon::parse($verify->temp_block_time)->DiffInSeconds() <= $tempBlockTime) {
                $time = $tempBlockTime - Carbon::parse($verify->temp_block_time)->DiffInSeconds();
                return response()->json([
                    'errors' => [[
                        'code' => 'otp_block_time',
                        'message' => translate('please_try_again_after_') . CarbonInterval::seconds($time)->cascade()->forHumans()
                    ]]
                ], 403);
            }
            return response()->json(['message' => translate("Token found, you can proceed")], 200);
        }

        $verificationData = DB::table('password_resets')->where('email_or_phone', $request['email_or_phone'])->first();

        if (isset($verificationData)) {
            if (isset($verificationData->temp_block_time) && Carbon::parse($verificationData->temp_block_time)->DiffInSeconds() <= $tempBlockTime) {
                $time = $tempBlockTime - Carbon::parse($verificationData->temp_block_time)->DiffInSeconds();
                return response()->json([
                    'errors' => [[
                        'code' => 'otp_block_time',
                        'message' => translate('please_try_again_after_') . CarbonInterval::seconds($time)->cascade()->forHumans()
                    ]]
                ], 403);
            }

            if ($verificationData->is_temp_blocked == 1 && Carbon::parse($verificationData->created_at)->DiffInSeconds() >= $tempBlockTime) {
                DB::table('password_resets')->updateOrInsert(['email_or_phone' => $request['email_or_phone']], [
                    'otp_hit_count' => 0,
                    'is_temp_blocked' => 0,
                    'temp_block_time' => null,
                    'created_at' => now(),
                ]);
            }

            if ($verificationData->otp_hit_count >= $maxOTPHit && Carbon::parse($verificationData->created_at)->DiffInSeconds() < $maxOTPHitTime && $verificationData->is_temp_blocked == 0) {
                DB::table('password_resets')->updateOrInsert(['email_or_phone' => $request['email_or_phone']], [
                    'is_temp_blocked' => 1,
                    'temp_block_time' => now(),
                    'created_at' => now(),
                ]);

                return response()->json([
                    'errors' => [[
                        'code' => 'otp_temp_blocked',
                        'message' => translate('Too_many_attempts')
                    ]]
                ], 405);
            }
        }

        DB::table('password_resets')->updateOrInsert(['email_or_phone' => $request['email_or_phone']], [
            'otp_hit_count' => DB::raw('otp_hit_count + 1'),
            'created_at' => now(),
            'temp_block_time' => null,
        ]);

        return response()->json(['errors' => [
            ['code' => 'invalid', 'message' => translate('OTP is not matched.')]
        ]], 400);
    }

    public function resetPasswordSubmit(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email_or_phone' => 'required',
            'reset_token' => 'required',
            'type' => 'required|in:phone,email',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $data = DB::table('password_resets')
            ->where(['email_or_phone' => $request['email_or_phone']])
            ->where(['token' => $request['reset_token']])
            ->first();

        if (!isset($data)) {
            return response()->json(['errors' => [
                ['code' => 'invalid', 'message' => translate('Invalid token.')]
            ]], 400);
        }

        if ($request['password'] == $request['confirm_password']) {
            $customer = $this->user
                ->where(['email' => $request['email_or_phone']])
                ->orWhere('phone', $request['email_or_phone'])
                ->first();

            if (!isset($customer)) {
                return response()->json(['errors' => [['code' => 'not-found', 'message' => translate('Customer not found!')]]], 404);
            }

            $customer->password = bcrypt($request['confirm_password']);
            if ($request['type'] == 'phone') {
                $customer->is_phone_verified = 1;
            } else {
                $customer->email_verified_at = now();
            }
            $customer->save();

            DB::table('password_resets')
                ->where(['email_or_phone' => $request['email_or_phone']])
                ->where(['token' => $request['reset_token']])
                ->delete();

            if ($customer->phone) {
                try {
                    $successMessage = translate('Your password has been reset successfully. If you did not perform this action, please contact support immediately.');
                    $this->whatsapp->sendMessage($customer->phone, $successMessage);
                    Log::info('WhatsApp password reset success notification sent', [
                        'identifier' => $request['email_or_phone'],
                        'phone' => $customer->phone
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('WhatsApp password reset success notification failed', [
                        'identifier' => $request['email_or_phone'],
                        'phone' => $customer->phone,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return response()->json(['message' => translate('Password changed successfully.')], 200);
        }

        return response()->json(['errors' => [
            ['code' => 'mismatch', 'message' => translate('Password did,t match!')]
        ]], 401);
    }
}