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
            // 1. Verify Basic Auth
            $authHeader = $request->header('Authorization');
            if (!$authHeader || !str_starts_with($authHeader, 'Basic ')) {
                Log::warning('Kuraimi Verification: Missing Authorization header', [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]);
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            $credentials = base64_decode(substr($authHeader, 6));
            [$requestUser, $requestPass] = explode(':', $credentials, 2);

            $expectedUser = config('payment.kuraimi.username');
            $expectedPass = config('payment.kuraimi.password');

            if ($requestUser !== $expectedUser || $requestPass !== $expectedPass) {
                Log::warning('Kuraimi Verification: Invalid credentials', [
                    'provided_user' => $requestUser,
                    'ip' => $request->ip()
                ]);
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            // 2. Validate Input
            $data = $request->all();
            Log::info('Kuraimi Verification Request', [
                'has_customer_id' => isset($data['SCustID']),
                'has_mobile' => isset($data['MobileNo']),
                'has_email' => isset($data['Email']),
                'customer_zone' => $data['CustomerZone'] ?? 'not_provided'
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

            // Try Customer ID first
            if ($sCustId) {
                $user = User::find($sCustId);
                if ($user) {
                    Log::info('Kuraimi Verification: User found by ID', ['user_id' => $user->id]);
                }
            }

            // Try Mobile Number
            if (!$user && $mobileNo) {
                $cleanMobile = preg_replace('/[^0-9]/', '', $mobileNo);
                $user = User::where(function ($query) use ($mobileNo, $cleanMobile) {
                    $query->where('phone', $mobileNo)
                        ->orWhere('phone', 'like', "%{$cleanMobile}%")
                        ->orWhere('phone', $cleanMobile);
                })->first();

                if ($user) {
                    Log::info('Kuraimi Verification: User found by mobile', ['user_id' => $user->id]);
                }
            }

            // Try Email
            if (!$user && $email) {
                $user = User::where('email', $email)->first();
                if ($user) {
                    Log::info('Kuraimi Verification: User found by email', ['user_id' => $user->id]);
                }
            }

            // 4. Return Response
            if ($user) {
                Log::info('Kuraimi Verification: Success', [
                    'customer_id' => $user->id,
                    'found_by' => $sCustId ? 'id' : ($mobileNo ? 'mobile' : 'email')
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
                    'provided_email' => $email ? 'yes' : 'no'
                ]);

                return response()->json([
                    'Code' => '2',
                    'SCustID' => null,
                    'DescriptionAr' => 'تفاصيل العميل غير صالحة',
                    'DescriptionEn' => 'Invalid customer details'
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Kuraimi Verification: Exception', [
                'error' => $e->getMessage(),
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
}