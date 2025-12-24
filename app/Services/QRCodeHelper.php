<?php

namespace App\Services;

use App\User;
use Illuminate\Support\Facades\Log;

class QRCodeHelper
{
    /**
     * Validate and decode QR code data
     * Handles both JSON format and simple string format
     */
    public static function validateQRCode(string $qrData): array
    {
        try {
            Log::info('Validating QR code', [
                'qr_data' => $qrData,
                'length' => strlen($qrData)
            ]);

            // Try to decode as JSON first
            $decoded = json_decode($qrData, true);
            
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                // JSON format (from actual QR code scan)
                Log::info('QR code is JSON format', ['decoded' => $decoded]);
                
                if (!isset($decoded['qr_code']) || !isset($decoded['user_id']) || !isset($decoded['type'])) {
                    return [
                        'valid' => false,
                        'message' => translate('Invalid QR code format - missing required fields')
                    ];
                }
                
                if ($decoded['type'] !== 'wallet_transfer') {
                    return [
                        'valid' => false,
                        'message' => translate('QR code is not for wallet transfer')
                    ];
                }
                
                $user = User::where('id', $decoded['user_id'])
                    ->where('qr_code', $decoded['qr_code'])
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
                    'data' => $decoded
                ];
            }
            
            // Simple string format (just the QR code string)
            // Format: WALLET_XXXXX_USER_ID
            Log::info('QR code is string format, parsing...', ['qr_data' => $qrData]);
            
            if (!preg_match('/^WALLET_([A-Z0-9]+)_(\d+)$/', $qrData, $matches)) {
                return [
                    'valid' => false,
                    'message' => translate('Invalid QR code format')
                ];
            }
            
            $userId = (int) $matches[2];
            
            Log::info('Extracted user ID from QR code', [
                'user_id' => $userId,
                'qr_code' => $qrData
            ]);
            
            $user = User::where('id', $userId)
                ->where('qr_code', $qrData)
                ->where('is_active', 1)
                ->first();
            
            if (!$user) {
                Log::warning('User not found or QR code mismatch', [
                    'user_id' => $userId,
                    'qr_data' => $qrData
                ]);
                
                return [
                    'valid' => false,
                    'message' => translate('User not found or QR code is invalid')
                ];
            }
            
            Log::info('QR code validated successfully', [
                'user_id' => $user->id,
                'user_name' => $user->name
            ]);
            
            return [
                'valid' => true,
                'user' => $user,
                'data' => [
                    'qr_code' => $qrData,
                    'user_id' => $user->id,
                    'type' => 'wallet_transfer'
                ]
            ];
            
        } catch (\Exception $e) {
            Log::error('QR code validation failed with exception', [
                'qr_data' => $qrData,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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