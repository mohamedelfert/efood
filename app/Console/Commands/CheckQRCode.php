<?php

namespace App\Console\Commands;

use App\User;
use Illuminate\Console\Command;

class CheckQRCode extends Command
{
    protected $signature = 'qr:check {user_id}';
    
    protected $description = 'Check QR code for a specific user';

    public function handle()
    {
        $userId = $this->argument('user_id');
        $user = User::find($userId);
        
        if (!$user) {
            $this->error("User ID {$userId} not found");
            return 1;
        }
        
        $this->info("Checking QR code for User: {$user->name} (ID: {$userId})");
        $this->newLine();
        
        // Check QR code data
        $this->table(
            ['Field', 'Value'],
            [
                ['QR Code', $user->qr_code ?? 'NULL'],
                ['QR Code Image', $user->qr_code_image ?? 'NULL'],
                ['Has QR Code', $user->hasQRCode() ? 'YES' : 'NO'],
            ]
        );
        
        if ($user->qr_code_image) {
            $path = storage_path('app/public/qr_codes/' . $user->qr_code_image);
            
            $this->newLine();
            $this->info("File Details:");
            $this->table(
                ['Property', 'Value'],
                [
                    ['File Path', $path],
                    ['File Exists', file_exists($path) ? 'YES' : 'NO'],
                    ['File Size', file_exists($path) ? filesize($path) . ' bytes' : 'N/A'],
                    ['Readable', file_exists($path) && is_readable($path) ? 'YES' : 'NO'],
                    ['URL', $user->qr_code_image_url ?? 'NULL'],
                ]
            );
            
            // Check storage link
            $this->newLine();
            $storageLinkPath = public_path('storage');
            $this->info("Storage Link: " . $storageLinkPath);
            $this->info("Link Exists: " . (file_exists($storageLinkPath) ? 'YES' : 'NO'));
            
            if (!file_exists($storageLinkPath)) {
                $this->warn("Storage link missing! Run: php artisan storage:link");
            }
            
            // Try to access the image
            if (file_exists($path)) {
                $this->newLine();
                $this->info("✓ QR code file exists and is accessible");
                $this->info("You can view it at: " . $user->qr_code_image_url);
            } else {
                $this->error("✗ QR code file not found!");
                $this->warn("Try regenerating with: php artisan qr:generate-all --force");
            }
        } else {
            $this->warn("User has no QR code. Generate one with:");
            $this->line("php artisan qr:generate-all");
        }
        
        return 0;
    }
}