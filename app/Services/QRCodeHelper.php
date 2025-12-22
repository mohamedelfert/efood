<?php

namespace App\Services;

use App\User;
use Illuminate\Support\Facades\Log;

class QRCodeHelper
{
    /**
     * Validate and decode QR code data
     */
    public static function validateQRCode(string $qrData): array
    {
        try {
            $data = json_decode($qrData, true);
            
            if (!$data || !isset($data['qr_code']) || !isset($data['user_id']) || !isset($data['type'])) {
                return [
                    'valid' => false,
                    'message' => translate('Invalid QR code format')
                ];
            }
            
            if ($data['type'] !== 'wallet_transfer') {
                return [
                    'valid' => false,
                    'message' => translate('QR code is not for wallet transfer')
                ];
            }
            
            $user = User::where('id', $data['user_id'])
                ->where('qr_code', $data['qr_code'])
                ->where('is_active', 1)
                ->first();
            
            if (!$user) {
                return [
                    'valid' => false,
                    'message' => translate('User not found or QR code is invalid')
                ];
            }
            
            return [
                'valid' => true,
                'user' => $user,
                'data' => $data
            ];
            
        } catch (\Exception $e) {
            Log::error('QR code validation failed', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'valid' => false,
                'message' => translate('Failed to validate QR code')
            ];
        }
    }
    
    /**
     * Generate QR codes for all users without one
     */
    public static function generateMissingQRCodes(): array
    {
        $users = User::whereNull('qr_code')
            ->orWhereNull('qr_code_image')
            ->get();
        
        $generated = 0;
        $failed = 0;
        
        foreach ($users as $user) {
            if ($user->generateQRCode()) {
                $generated++;
            } else {
                $failed++;
            }
        }
        
        return [
            'total' => $users->count(),
            'generated' => $generated,
            'failed' => $failed
        ];
    }
}