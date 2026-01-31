<?php

namespace App\Http\Controllers\Api\V1;

use App\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class KuraimiPaymentController extends Controller
{
    /**
     * Verify Customer API (Incoming request from Kuraimi)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyCustomer(Request $request)
    {
        try {
            // Log the raw request for debugging
            Log::info('Kuraimi Verification: Raw Request', [
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'headers' => $request->headers->all(),
                'body' => $request->all()
            ]);

            // 1. Verify Basic Auth
            $authHeader = $request->header('Authorization');
            if (!$authHeader || !str_starts_with($authHeader, 'Basic ')) {
                Log::warning('Kuraimi Verification: Missing or invalid Authorization header', [
                    'auth_header' => $authHeader,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]);
                return response()->json([
                    'Code' => '2',
                    'SCustID' => null,
                    'DescriptionAr' => 'غير مصرح',
                    'DescriptionEn' => 'Unauthorized - Invalid authorization header'
                ], 401);
            }

            // Decode credentials
            try {
                $credentials = base64_decode(substr($authHeader, 6));
                $credentialsParts = explode(':', $credentials, 2);

                if (count($credentialsParts) !== 2) {
                    Log::warning('Kuraimi Verification: Malformed credentials');
                    return response()->json([
                        'Code' => '2',
                        'SCustID' => null,
                        'DescriptionAr' => 'غير مصرح',
                        'DescriptionEn' => 'Unauthorized - Malformed credentials'
                    ], 401);
                }

                [$requestUser, $requestPass] = $credentialsParts;

                $expectedUser = config('payment.kuraimi.username');
                $expectedPass = config('payment.kuraimi.password');

                // If config is not set, provide clear error
                if (empty($expectedUser) || empty($expectedPass)) {
                    Log::error('Kuraimi Verification: Configuration missing', [
                        'has_username' => !empty($expectedUser),
                        'has_password' => !empty($expectedPass)
                    ]);
                    return response()->json([
                        'Code' => '2',
                        'SCustID' => null,
                        'DescriptionAr' => 'خطأ في الإعدادات',
                        'DescriptionEn' => 'System configuration error'
                    ], 500);
                }

                if ($requestUser !== $expectedUser || $requestPass !== $expectedPass) {
                    Log::warning('Kuraimi Verification: Invalid credentials', [
                        'provided_user' => $requestUser,
                        'ip' => $request->ip()
                    ]);
                    return response()->json([
                        'Code' => '2',
                        'SCustID' => null,
                        'DescriptionAr' => 'غير مصرح',
                        'DescriptionEn' => 'Unauthorized - Invalid credentials'
                    ], 401);
                }
            } catch (\Exception $e) {
                Log::error('Kuraimi Verification: Error decoding credentials', [
                    'error' => $e->getMessage()
                ]);
                return response()->json([
                    'Code' => '2',
                    'SCustID' => null,
                    'DescriptionAr' => 'غير مصرح',
                    'DescriptionEn' => 'Unauthorized - Invalid authorization format'
                ], 401);
            }

            // 2. Validate Input
            $data = $request->all();
            Log::info('Kuraimi Verification Request Data', [
                'has_customer_id' => isset($data['SCustID']),
                'has_mobile' => isset($data['MobileNo']),
                'has_email' => isset($data['Email']),
                'customer_zone' => $data['CustomerZone'] ?? 'not_provided',
                'raw_data' => $data
            ]);

            $sCustId = $data['SCustID'] ?? null;
            $mobileNo = $data['MobileNo'] ?? null;
            $email = $data['Email'] ?? null;

            // At least one identifier required
            if (empty($sCustId) && empty($mobileNo) && empty($email)) {
                Log::warning('Kuraimi Verification: No identifier provided');
                return response()->json([
                    'Code' => '2',
                    'SCustID' => null,
                    'DescriptionAr' => 'تفاصيل العميل غير صالحة',
                    'DescriptionEn' => 'Invalid customer details - at least one identifier required'
                ]);
            }

            // 3. Find Customer
            $user = null;
            $foundBy = null;

            // Try Customer ID first
            if ($sCustId) {
                try {
                    $user = User::find($sCustId);
                    if ($user) {
                        $foundBy = 'id';
                        Log::info('Kuraimi Verification: User found by ID', ['user_id' => $user->id]);
                    } else {
                        Log::info('Kuraimi Verification: No user found with ID', ['provided_id' => $sCustId]);
                    }
                } catch (\Exception $e) {
                    Log::error('Kuraimi Verification: Error searching by ID', [
                        'provided_id' => $sCustId,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Try Email
            if (!$user && $email) {
                try {
                    // Clean and normalize email
                    $cleanEmail = trim(strtolower($email));

                    Log::info('Kuraimi Verification: Searching by email', [
                        'original_email' => $email,
                        'clean_email' => $cleanEmail
                    ]);

                    $user = User::where('email', $cleanEmail)
                        ->orWhere('email', $email)
                        ->first();

                    if ($user) {
                        $foundBy = 'email';
                        Log::info('Kuraimi Verification: User found by email', [
                            'user_id' => $user->id,
                            'user_email' => $user->email
                        ]);
                    } else {
                        Log::info('Kuraimi Verification: No user found with email', [
                            'provided_email' => $email,
                            'clean_email' => $cleanEmail
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Kuraimi Verification: Error searching by email', [
                        'provided_email' => $email,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }

            // Try Mobile Number
            if (!$user && $mobileNo) {
                try {
                    $cleanMobile = preg_replace('/[^0-9]/', '', $mobileNo);

                    Log::info('Kuraimi Verification: Searching by mobile', [
                        'original_mobile' => $mobileNo,
                        'clean_mobile' => $cleanMobile
                    ]);

                    $user = User::where(function ($query) use ($mobileNo, $cleanMobile) {
                        $query->where('phone', $mobileNo)
                            ->orWhere('phone', $cleanMobile)
                            ->orWhere('phone', 'like', "%{$cleanMobile}");
                    })->first();

                    if ($user) {
                        $foundBy = 'mobile';
                        Log::info('Kuraimi Verification: User found by mobile', [
                            'user_id' => $user->id,
                            'user_phone' => $user->phone
                        ]);
                    } else {
                        Log::info('Kuraimi Verification: No user found with mobile', [
                            'provided_mobile' => $mobileNo,
                            'clean_mobile' => $cleanMobile
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Kuraimi Verification: Error searching by mobile', [
                        'provided_mobile' => $mobileNo,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }

            // 4. Return Response
            if ($user) {
                Log::info('Kuraimi Verification: Success', [
                    'customer_id' => $user->id,
                    'customer_email' => $user->email,
                    'customer_phone' => $user->phone,
                    'found_by' => $foundBy
                ]);

                return response()->json([
                    'Code' => '1',
                    'SCustID' => (string) $user->id,
                    'DescriptionAr' => 'تم التحقق من تفاصيل العميل بنجاح',
                    'DescriptionEn' => 'Customer details verified successfully'
                ]);
            } else {
                Log::info('Kuraimi Verification: Customer not found', [
                    'provided_id' => $sCustId,
                    'provided_mobile' => $mobileNo,
                    'provided_email' => $email
                ]);

                return response()->json([
                    'Code' => '2',
                    'SCustID' => null,
                    'DescriptionAr' => 'تفاصيل العميل غير صالحة',
                    'DescriptionEn' => 'Invalid customer details - customer not found'
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Kuraimi Verification: Unexpected Exception', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'Code' => '2',
                'SCustID' => null,
                'DescriptionAr' => 'حدث خطأ في النظام',
                'DescriptionEn' => 'System error occurred'
            ], 500);
        }
    }

    /**
     * Test endpoint to verify configuration
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function testConfig()
    {
        try {
            $hasUsername = !empty(config('payment.kuraimi.username'));
            $hasPassword = !empty(config('payment.kuraimi.password'));

            return response()->json([
                'status' => 'ok',
                'config_status' => [
                    'has_username' => $hasUsername,
                    'has_password' => $hasPassword,
                    'username_length' => $hasUsername ? strlen(config('payment.kuraimi.username')) : 0,
                    'password_length' => $hasPassword ? strlen(config('payment.kuraimi.password')) : 0,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}