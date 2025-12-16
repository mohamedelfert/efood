<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\User;
use App\Models\Setting;
use App\Models\LoginSetup;
use Carbon\CarbonInterval;
use App\Traits\HelperTrait;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use Illuminate\Support\Carbon;
use App\CentralLogics\SMS_module;
use App\Services\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use App\Services\NotificationService;
use Modules\Gateways\Traits\SmsGateway;
use Illuminate\Support\Facades\Validator;

class PasswordResetController extends Controller
{
        use HelperTrait;
    
    public function __construct(
        private User $user,
        private LoginSetup $loginSetup,
        private WhatsAppService $whatsapp,
        private NotificationService $notificationService
    ){
        $this->notificationService = $notificationService;
        $this->whatsapp = $whatsapp;
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

        if ($request['type'] == 'phone'){
            $customer = $this->user->where(['phone' => $request['email_or_phone']])->first();
        }else{
            $customer = $this->user->where(['email' => $request['email_or_phone']])->first();
        }

        if (!isset($customer)){
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

        // SEND OTP via PHONE (SMS + WhatsApp)
        if ($request['type'] == 'phone') {
            $results = ['sms' => false, 'whatsapp' => false];
            
            // Send SMS (existing)
            $activeSMSGatewaysCount = $this->getActiveSMSGatewayCount();
            if ($activeSMSGatewaysCount > 0) {
                $publishedStatus = 0;
                $paymentPublishedStatus = config('get_payment_publish_status');
                if (isset($paymentPublishedStatus[0]['is_published'])) {
                    $publishedStatus = $paymentPublishedStatus[0]['is_published'];
                }
                
                try {
                    if($publishedStatus == 1){
                        $smsResponse = SmsGateway::send($customer['phone'], $token);
                    }else{
                        $smsResponse = SMS_module::send($customer['phone'], $token);
                    }
                    $results['sms'] = true;
                    
                    Log::info('Password reset SMS sent', [
                        'phone' => $customer['phone'],
                        'response' => $smsResponse
                    ]);
                } catch (\Exception $e) {
                    Log::error('Password reset SMS failed', [
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // SEND WhatsApp OTP (NEW)
            try {
                $whatsappData = [
                    'user_name' => $customer['name'],
                    'otp' => $token,
                    'expiry_minutes' => '5',
                    'timestamp' => now()->format('Y-m-d H:i:s')
                ];
                
                $message = $this->whatsapp->sendTemplateMessage('password_reset_otp', $whatsappData);
                $results = $this->notificationService->sendLoginOTP($customer, $token);
                
                if (!$message) {
                    // Fallback message if template not found
                    $appName = env('APP_NAME', 'eFood');
                    $message = "*{$appName} - Password Reset*\n\n" .
                              "Hello {$customer['name']},\n\n" .
                              "Your password reset code is: *{$token}*\n\n" .
                              "This code will expire in 5 minutes.\n\n" .
                              "_Do not share this code with anyone._\n\n" .
                              "© " . date('Y') . " {$appName}";
                }
                
                $whatsappResponse = $this->whatsapp->sendMessage($customer['phone'], $message);
                $results['whatsapp'] = $whatsappResponse['success'] ?? false;
                
                Log::info('Password reset WhatsApp sent', [
                    'phone' => $customer['phone'],
                    'success' => $results['whatsapp']
                ]);
                
            } catch (\Exception $e) {
                Log::error('Password reset WhatsApp failed', [
                    'error' => $e->getMessage()
                ]);
            }
            
            // Return response based on what succeeded
            if ($results['sms'] || $results['whatsapp']) {
                $channels = [];
                if ($results['sms']) $channels[] = 'SMS';
                if ($results['whatsapp']) $channels[] = 'WhatsApp';
                
                return response()->json([
                    'message' => translate('OTP sent successfully via ') . implode(' & ', $channels)
                ], 200);
            } else {
                return response()->json([
                    'errors' => [['code' => 'otp', 'message' => translate('Unable to send OTP')]]
                ], 400);
            }
        } 
        
        // SEND OTP via EMAIL (existing)
        else {
            try {
                $emailServices = Helpers::get_business_settings('mail_config');
                $mailStatus = Helpers::get_business_settings('forget_password_mail_status_user');

                if(isset($emailServices['status']) && $emailServices['status'] == 1 && $mailStatus == 1){
                    Mail::to($customer['email'])->send(new \App\Mail\PasswordResetMail($token, $customer['name'], $customer->language_code));
                    
                    Log::info('Password reset email sent', [
                        'email' => $customer['email']
                    ]);
                    
                    return response()->json(['message' => translate('Email sent successfully.')], 200);
                }
                
                return response()->json([
                    'errors' => [['code' => 'otp', 'message' => translate('Email service disabled')]]
                ], 400);

            } catch (\Exception $exception) {
                Log::error('Password reset email failed', [
                    'error' => $exception->getMessage()
                ]);
                
                return response()->json([
                    'errors' => [['code' => 'otp', 'message' => translate('Unable to send OTP.')]]
                ], 400);
            }
        }
    }


    public function verifyToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email_or_phone' => 'required',
            'reset_token'=> 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $maxOTPHit = Helpers::get_business_settings('maximum_otp_hit') ?? 5;
        $maxOTPHitTime = Helpers::get_business_settings('otp_resend_time') ?? 60;    // seconds
        $tempBlockTime = Helpers::get_business_settings('temporary_block_time') ?? 600;   // seconds

        $verify = DB::table('password_resets')->where(['token' => $request['reset_token'], 'email_or_phone' => $request['email_or_phone']])->first();

        if (isset($verify)) {
            if(isset($verify->temp_block_time ) && Carbon::parse($verify->temp_block_time)->DiffInSeconds() <= $tempBlockTime){
                $time = $tempBlockTime - Carbon::parse($verify->temp_block_time)->DiffInSeconds();

                $errors = [];
                $errors[] = ['code' => 'otp_block_time',
                    'message' => translate('please_try_again_after_') . CarbonInterval::seconds($time)->cascade()->forHumans()
                ];
                return response()->json([
                    'errors' => $errors
                ], 403);
            }

            return response()->json(['message' => translate("Token found, you can proceed")], 200);

        }else{
            $verificationData= DB::table('password_resets')->where('email_or_phone', $request['email_or_phone'])->first();

            if(isset($verificationData)){
                $time = $tempBlockTime - Carbon::parse($verificationData->temp_block_time)->DiffInSeconds();

                if(isset($verificationData->temp_block_time ) && Carbon::parse($verificationData->temp_block_time)->DiffInSeconds() <= $tempBlockTime){
                    $time= $tempBlockTime - Carbon::parse($verificationData->temp_block_time)->DiffInSeconds();

                    $errors = [];
                    $errors[] = [
                        'code' => 'otp_block_time',
                        'message' => translate('please_try_again_after_') . CarbonInterval::seconds($time)->cascade()->forHumans()
                    ];
                    return response()->json([
                        'errors' => $errors
                    ], 403);
                }

                if($verificationData->is_temp_blocked == 1 && Carbon::parse($verificationData->created_at)->DiffInSeconds() >= $tempBlockTime){
                    DB::table('password_resets')->updateOrInsert(['email_or_phone' => $request['email_or_phone']],
                        [
                            'otp_hit_count' => 0,
                            'is_temp_blocked' => 0,
                            'temp_block_time' => null,
                            'created_at' => now(),
                        ]);
                }

                if($verificationData->otp_hit_count >= $maxOTPHit &&  Carbon::parse($verificationData->created_at)->DiffInSeconds() < $maxOTPHitTime &&  $verificationData->is_temp_blocked == 0){

                    DB::table('password_resets')->updateOrInsert(['email_or_phone' => $request['email_or_phone']],
                        [
                            'is_temp_blocked' => 1,
                            'temp_block_time' => now(),
                            'created_at' => now(),
                        ]);

                    $errors = [];
                    $errors[] = [
                        'code' => 'otp_temp_blocked',
                        'message' => translate('Too_many_attempts')
                    ];
                    return response()->json([
                        'errors' => $errors
                    ], 405);
                }

            }

            DB::table('password_resets')->updateOrInsert(['email_or_phone' => $request['email_or_phone']],
                [
                    'otp_hit_count' => DB::raw('otp_hit_count + 1'),
                    'created_at' => now(),
                    'temp_block_time' => null,
                ]);
        }

        return response()->json(['errors' => [
            ['code' => 'invalid', 'message' => translate('OTP is not matched.')]
        ]], 400);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
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

        if (!isset($data)){
            return response()->json(['errors' => [
                ['code' => 'invalid', 'message' => translate('Invalid token.')]
            ]], 400);
        }

        if ($request['password'] == $request['confirm_password']) {
            $customer = $this->user
                ->where(['email' => $request['email_or_phone']])
                ->orWhere('phone', $request['email_or_phone'])
                ->first();

            if (!isset($customer)){
                return response()->json(['errors' => [['code' => 'not-found', 'message' => translate('Customer not found!')]]], 404);
            }

            $customer->password = bcrypt($request['confirm_password']);

            if ($request['type'] == 'phone'){
                $customer->is_phone_verified = 1;
            }else{
                $customer->email_verified_at = now();
            }
            $customer->save();

            DB::table('password_resets')
                ->where(['email_or_phone' => $request['email_or_phone']])
                ->where(['token' => $request['reset_token']])
                ->delete();


            return response()->json(['message' => translate('Password changed successfully.')], 200);
        }

        return response()->json(['errors' => [
            ['code' => 'mismatch', 'message' => translate('Password did,t match!')]
        ]], 401);


    }
}