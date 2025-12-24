<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DiagnoseQRSystem extends Command
{
    protected $signature = 'qr:diagnose';
    protected $description = 'Diagnose QR code system and storage issues';

    public function handle()
    {
        $this->info('=== QR Code System Diagnostics ===');
        $this->newLine();
        
        $issues = [];
        $warnings = [];
        
        // 1. Check PHP Extensions
        $this->info('1. Checking PHP Extensions...');
        $extensions = ['gd', 'fileinfo', 'json'];
        foreach ($extensions as $ext) {
            if (extension_loaded($ext)) {
                $this->line("   ✓ {$ext} extension loaded");
            } else {
                $issues[] = "{$ext} extension not loaded";
                $this->error("   ✗ {$ext} extension NOT loaded");
            }
        }
        $this->newLine();
        
        // 2. Check Composer Packages
        $this->info('2. Checking Required Packages...');
        $packages = [
            'SimpleSoftwareIO\QrCode\Facades\QrCode' => 'simple-qrcode',
        ];
        
        foreach ($packages as $class => $package) {
            if (class_exists($class)) {
                $this->line("   ✓ {$package} installed");
            } else {
                $issues[] = "{$package} not installed";
                $this->error("   ✗ {$package} NOT installed");
                $this->line("      Install: composer require simplesoftwareio/{$package}");
            }
        }
        $this->newLine();
        
        // 3. Check Directories
        $this->info('3. Checking Directory Structure...');
        $directories = [
            storage_path('app/public'),
            storage_path('app/public/qr_codes'),
            storage_path('app/public/profile'),
            storage_path('app/public/banner'),
            storage_path('app/public/product'),
        ];
        
        foreach ($directories as $dir) {
            $exists = file_exists($dir);
            $writable = $exists && is_writable($dir);
            $perms = $exists ? substr(sprintf('%o', fileperms($dir)), -4) : 'N/A';
            $owner = $exists ? posix_getpwuid(fileowner($dir))['name'] ?? 'unknown' : 'N/A';
            
            if ($exists && $writable) {
                $this->line("   ✓ " . basename($dir) . " (perms: {$perms}, owner: {$owner})");
            } elseif ($exists) {
                $warnings[] = basename($dir) . " not writable";
                $this->warn("   ⚠ " . basename($dir) . " exists but NOT writable (perms: {$perms}, owner: {$owner})");
            } else {
                $issues[] = basename($dir) . " doesn't exist";
                $this->error("   ✗ " . basename($dir) . " does NOT exist");
            }
        }
        $this->newLine();
        
        // 4. Check Storage Link
        $this->info('4. Checking Storage Link...');
        $publicStorage = public_path('storage');
        $target = storage_path('app/public');
        
        if (file_exists($publicStorage)) {
            if (is_link($publicStorage)) {
                $linkTarget = readlink($publicStorage);
                if ($linkTarget === $target || realpath($linkTarget) === realpath($target)) {
                    $this->line("   ✓ Storage link exists and points to correct location");
                } else {
                    $warnings[] = "Storage link points to wrong location";
                    $this->warn("   ⚠ Storage link exists but points to: {$linkTarget}");
                    $this->line("      Expected: {$target}");
                }
            } else {
                $issues[] = "public/storage is not a symlink";
                $this->error("   ✗ public/storage exists but is NOT a symlink");
            }
        } else {
            $issues[] = "Storage link doesn't exist";
            $this->error("   ✗ Storage link does NOT exist");
            $this->line("      Run: php artisan storage:link");
        }
        $this->newLine();
        
        // 5. Check User Table Columns
        $this->info('5. Checking Database Columns...');
        try {
            $columns = \DB::getSchemaBuilder()->getColumnListing('users');
            $requiredColumns = ['qr_code', 'qr_code_image'];
            
            foreach ($requiredColumns as $col) {
                if (in_array($col, $columns)) {
                    $this->line("   ✓ Column '{$col}' exists");
                } else {
                    $issues[] = "Column '{$col}' missing";
                    $this->error("   ✗ Column '{$col}' does NOT exist");
                    $this->line("      Run: php artisan migrate");
                }
            }
        } catch (\Exception $e) {
            $issues[] = "Database connection failed";
            $this->error("   ✗ Could not check database: " . $e->getMessage());
        }
        $this->newLine();
        
        // 6. Check Existing QR Codes
        $this->info('6. Checking Existing QR Codes...');
        try {
            $usersWithQR = \App\User::whereNotNull('qr_code')->count();
            $usersWithImage = \App\User::whereNotNull('qr_code_image')->count();
            $totalUsers = \App\User::count();
            
            $this->line("   Total users: {$totalUsers}");
            $this->line("   Users with QR code: {$usersWithQR}");
            $this->line("   Users with QR image: {$usersWithImage}");
            
            // Check file existence
            $qrDir = storage_path('app/public/qr_codes');
            if (file_exists($qrDir)) {
                $files = glob($qrDir . '/qr_*.png');
                $fileCount = count($files);
                $this->line("   QR code files on disk: {$fileCount}");
                
                if ($fileCount !== $usersWithImage) {
                    $warnings[] = "File count mismatch";
                    $this->warn("   ⚠ Mismatch between database records and files");
                }
            }
        } catch (\Exception $e) {
            $this->error("   ✗ Could not check QR codes: " . $e->getMessage());
        }
        $this->newLine();
        
        // 7. Test QR Generation
        $this->info('7. Testing QR Code Generation...');
        $testFile = storage_path('app/public/qr_codes/test_' . time() . '.png');
        try {
            $qr = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')
                ->size(300)
                ->generate('test');
            
            file_put_contents($testFile, $qr);
            
            if (file_exists($testFile) && filesize($testFile) > 0) {
                $this->line("   ✓ QR code generation works");
                @unlink($testFile);
            } else {
                $issues[] = "QR generation creates empty files";
                $this->error("   ✗ QR code file created but is empty");
            }
        } catch (\Exception $e) {
            $issues[] = "QR generation failed: " . $e->getMessage();
            $this->error("   ✗ QR code generation failed: " . $e->getMessage());
        }
        $this->newLine();
        
        // Summary
        $this->info('=== Diagnostic Summary ===');
        if (count($issues) === 0 && count($warnings) === 0) {
            $this->info('✓ All checks passed! System is ready.');
        } else {
            if (count($issues) > 0) {
                $this->error('Issues found (' . count($issues) . '):');
                foreach ($issues as $issue) {
                    $this->line('  • ' . $issue);
                }
                $this->newLine();
            }
            
            if (count($warnings) > 0) {
                $this->warn('Warnings (' . count($warnings) . '):');
                foreach ($warnings as $warning) {
                    $this->line('  • ' . $warning);
                }
                $this->newLine();
            }
            
            $this->info('Recommended actions:');
            $this->line('  1. Run the fix script: bash fix-storage.sh');
            $this->line('  2. Or manually run: sudo chown -R $(whoami):www-data storage');
            $this->line('  3. Then: chmod -R 775 storage');
            $this->line('  4. Finally: php artisan qr:generate-all --force');
        }
        
        return count($issues) === 0 ? 0 : 1;
    }
}