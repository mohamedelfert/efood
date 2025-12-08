<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ReceiptGeneratorService
{
    /**
     * Generate receipt image from Blade template
     * Uses simple GD library (built into PHP, no external dependencies)
     */
    public function generateReceiptImage(array $receiptData): ?string
    {
        try {
            // Generate filename and path
            $filename = 'receipt_' . $receiptData['transaction_id'] . '_' . time() . '.png';
            $outputPath = storage_path('app/public/receipts/images/' . $filename);
            
            // Ensure directory exists
            if (!file_exists(dirname($outputPath))) {
                mkdir(dirname($outputPath), 0755, true);
            }
            
            // Generate receipt using GD library
            $this->generateSimpleReceipt($receiptData, $outputPath);
            
            Log::info('Receipt generated successfully', [
                'transaction_id' => $receiptData['transaction_id'],
                'path' => $outputPath
            ]);
            
            return $outputPath;
            
        } catch (\Exception $e) {
            Log::error('Receipt generation failed', [
                'error' => $e->getMessage(),
                'transaction_id' => $receiptData['transaction_id'] ?? 'unknown'
            ]);
            return null;
        }
    }

    /**
     * Generate professional receipt using GD library
     * No external dependencies required
     */
    private function generateSimpleReceipt(array $data, string $outputPath): void
    {
        $width = 800;
        $height = 1200;
        $image = imagecreatetruecolor($width, $height);
        
        // Colors
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        $gray = imagecolorallocate($image, 100, 100, 100);
        $blue = imagecolorallocate($image, 0, 102, 204);
        $green = imagecolorallocate($image, 0, 150, 0);
        $lightGray = imagecolorallocate($image, 240, 240, 240);
        $darkBlue = imagecolorallocate($image, 0, 51, 102);
        
        // Fill background
        imagefill($image, 0, 0, $white);
        
        $y = 40;
        $leftMargin = 60;
        $lineHeight = 40;
        
        // ===== HEADER =====
        imagefilledrectangle($image, 0, 0, $width, 100, $blue);
        
        $title = 'WALLET TOP-UP RECEIPT';
        $titleWidth = imagefontwidth(5) * strlen($title);
        $titleX = ($width - $titleWidth) / 2;
        imagestring($image, 5, $titleX, 40, $title, $white);
        
        $y = 140;
        
        // ===== TRANSACTION INFO =====
        $this->drawLabel($image, 'Transaction ID:', $data['transaction_id'], $leftMargin, $y, $black, $darkBlue);
        $y += $lineHeight;
        
        $dateTime = $data['date'] . ' ' . $data['time'];
        $this->drawLabel($image, 'Date & Time:', $dateTime, $leftMargin, $y, $black, $gray);
        $y += $lineHeight;
        
        // Separator
        $this->drawSeparator($image, $leftMargin, $y, $width - $leftMargin, $lightGray);
        $y += 40;
        
        // ===== CUSTOMER INFO =====
        imagestring($image, 5, $leftMargin, $y, 'CUSTOMER DETAILS', $blue);
        $y += $lineHeight;
        
        $this->drawLabel($image, 'Name:', $data['customer_name'], $leftMargin, $y, $black, $gray);
        $y += $lineHeight;
        
        $this->drawLabel($image, 'Account:', $data['account_number'], $leftMargin, $y, $black, $gray);
        $y += $lineHeight;
        
        // Separator
        $this->drawSeparator($image, $leftMargin, $y, $width - $leftMargin, $lightGray);
        $y += 40;
        
        // ===== AMOUNT BOX (Highlighted) =====
        $boxTop = $y - 10;
        $boxBottom = $y + 90;
        $boxLeft = $leftMargin - 10;
        $boxRight = $width - $leftMargin + 10;
        
        // Box background
        imagefilledrectangle($image, $boxLeft, $boxTop, $boxRight, $boxBottom, $lightGray);
        // Box border (thick green)
        for ($i = 0; $i < 3; $i++) {
            imagerectangle($image, $boxLeft + $i, $boxTop + $i, $boxRight - $i, $boxBottom - $i, $green);
        }
        
        imagestring($image, 5, $leftMargin, $y, 'AMOUNT CREDITED', $green);
        $y += 35;
        
        // Amount in larger font (using multiple font draws for bold effect)
        $amount = $data['amount'] . ' ' . $data['currency'];
        for ($i = 0; $i < 2; $i++) {
            imagestring($image, 5, $leftMargin + $i, $y, $amount, $green);
        }
        $y += 80;
        
        // ===== BALANCE INFO =====
        imagestring($image, 5, $leftMargin, $y, 'BALANCE DETAILS', $blue);
        $y += $lineHeight;
        
        $this->drawLabel($image, 'Previous Balance:', 
            number_format($data['previous_balance'], 2) . ' ' . $data['currency'], 
            $leftMargin, $y, $black, $gray);
        $y += $lineHeight;
        
        $this->drawLabel($image, 'New Balance:', 
            number_format($data['new_balance'], 2) . ' ' . $data['currency'], 
            $leftMargin, $y, $black, $green);
        $y += $lineHeight;
        
        if (isset($data['tax']) && $data['tax'] > 0) {
            $this->drawLabel($image, 'Tax:', 
                number_format($data['tax'], 2) . ' ' . $data['currency'], 
                $leftMargin, $y, $black, $gray);
            $y += $lineHeight;
        }
        
        // Separator
        $this->drawSeparator($image, $leftMargin, $y + 10, $width - $leftMargin, $lightGray);
        $y += 60;
        
        // ===== FOOTER =====
        $footer = 'Thank you for using our service!';
        $footerWidth = imagefontwidth(4) * strlen($footer);
        $footerX = ($width - $footerWidth) / 2;
        imagestring($image, 4, $footerX, $y, $footer, $gray);
        $y += 40;
        
        $company = 'Powered by eFood';
        $companyWidth = imagefontwidth(3) * strlen($company);
        $companyX = ($width - $companyWidth) / 2;
        imagestring($image, 3, $companyX, $y, $company, $gray);
        $y += 30;
        
        // Timestamp
        $timestamp = 'Generated on ' . date('Y-m-d H:i:s');
        $timestampWidth = imagefontwidth(2) * strlen($timestamp);
        $timestampX = ($width - $timestampWidth) / 2;
        imagestring($image, 2, $timestampX, $y, $timestamp, $lightGray);
        
        // Save image with high quality
        imagepng($image, $outputPath, 9); // Compression level 0-9 (9 = max)
        imagedestroy($image);
    }

    /**
     * Helper to draw label-value pairs
     */
    private function drawLabel($image, string $label, string $value, int $x, int $y, $labelColor, $valueColor): void
    {
        imagestring($image, 4, $x, $y, $label, $labelColor);
        imagestring($image, 4, $x + 220, $y, $value, $valueColor);
    }

    /**
     * Helper to draw separator line
     */
    private function drawSeparator($image, int $x1, int $y, int $x2, $color): void
    {
        imageline($image, $x1, $y, $x2, $y, $color);
        imageline($image, $x1, $y + 1, $x2, $y + 1, $color); // Make it thicker
    }

    /**
     * Get public URL for receipt
     */
    public function getReceiptUrl(string $path): string
    {
        $relativePath = str_replace(storage_path('app/public/'), '', $path);
        return url(Storage::url($relativePath));
    }

    /**
     * Cleanup old receipts (call this periodically via cron)
     */
    public function cleanupOldReceipts(int $daysToKeep = 7): void
    {
        try {
            $receiptPath = storage_path('app/public/receipts/images/');
            
            if (!file_exists($receiptPath)) {
                return;
            }
            
            $now = time();
            $maxAge = 60 * 60 * 24 * $daysToKeep;
            $files = glob($receiptPath . 'receipt_*.png');
            $deletedCount = 0;
            
            foreach ($files as $file) {
                if (is_file($file) && ($now - filemtime($file) >= $maxAge)) {
                    unlink($file);
                    $deletedCount++;
                }
            }
            
            if ($deletedCount > 0) {
                Log::info("Cleaned up {$deletedCount} old receipt(s)");
            }
        } catch (\Exception $e) {
            Log::warning('Failed to cleanup old receipts', [
                'error' => $e->getMessage()
            ]);
        }
    }
}