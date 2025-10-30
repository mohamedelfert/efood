<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use App\Models\WhatsAppTemplate;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ReceiptGeneratorService
{
    /**
     * Generate receipt as PDF
     */
    public function generateReceiptPDF(array $data): string
    {
        $template = WhatsAppTemplate::where('whatsapp_type', 'wallet_topup')
            ->where('type', 'user')
            ->first();

        $receiptData = $this->prepareReceiptData($data, $template);
        
        // Load the receipt view
        $html = View::make('receipts.wallet-topup-receipt', $receiptData)->render();
        
        // Generate PDF
        $pdf = Pdf::loadHTML($html)
            ->setPaper('a5', 'portrait')
            ->setOptions([
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
                'isFontSubsettingEnabled' => true,
                'defaultFont' => 'DejaVu Sans'
            ]);
        
        // Save to storage
        $fileName = 'receipt_' . $data['transaction_id'] . '_' . time() . '.pdf';
        $filePath = 'receipts/pdf/' . $fileName;
        Storage::disk('public')->makeDirectory('receipts/pdf');
        Storage::disk('public')->put($filePath, $pdf->output());
        
        return storage_path('app/public/' . $filePath);
    }

    /**
     * Generate receipt as Image
     */
    public function generateReceiptImage(array $data): string
    {
        $template = WhatsAppTemplate::where('whatsapp_type', 'wallet_topup')
            ->where('type', 'user')
            ->first();

        $receiptData = $this->prepareReceiptData($data, $template);

        // Always try Imagick first for better Arabic support via PDF
        if (extension_loaded('imagick')) {
            $pdfPath = $this->generateReceiptPDF($data);
            return $this->convertPDFToImageWithImagick($pdfPath, $data['transaction_id']);
        }
        
        // Fallback: Direct GD with bilingual support
        return $this->generateReceiptImageDirect($receiptData);
    }

    /**
     * Convert PDF to Image using Imagick
     */
    private function convertPDFToImageWithImagick(string $pdfPath, string $transactionId): string
    {
        try {
            $imagick = new Imagick();
            $imagick->setResolution(150, 150);
            $imagick->readImage($pdfPath . '[0]');
            $imagick->setImageFormat('png');
            $imagick->setImageCompressionQuality(90);
            
            // Save image
            $fileName = 'receipt_' . $transactionId . '_' . time() . '.png';
            $filePath = 'receipts/images/' . $fileName;
            $fullPath = storage_path('app/public/' . $filePath);
            
            Storage::disk('public')->makeDirectory('receipts/images');
            $imagick->writeImage($fullPath);
            $imagick->clear();
            
            return $fullPath;
        } catch (\Exception $e) {
            Log::error('Imagick conversion failed', ['error' => $e->getMessage()]);
            return $this->generateReceiptImageDirect($this->extractDataFromPath($pdfPath));
        }
    }

    /**
     * Generate receipt image directly using GD with TTF fonts (enhanced bilingual support)
     */
    private function generateReceiptImageDirect(array $receiptData): string
    {
        $fontPath = $this->getReadableFontPath();
        if (!$fontPath) {
            Log::warning('No readable TTF font found. Using bitmap fallback for entire image.', [
                'paths_tried' => [
                    public_path('fonts/Amiri-Regular.ttf'),
                    public_path('fonts/DejaVuSans.ttf')
                ]
            ]);
        }

        $width = 800;
        $height = 1400; // Increased for bilingual lines
        
        $img = imagecreatetruecolor($width, $height);
        
        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 0, 0, 0);
        $green = imagecolorallocate($img, 40, 167, 69);
        $gray = imagecolorallocate($img, 102, 102, 102);
        $lightGray = imagecolorallocate($img, 248, 249, 250);
        $borderColor = imagecolorallocate($img, 0, 0, 0);
        
        imagefill($img, 0, 0, $white);
        
        imagesetthickness($img, 3);
        imagerectangle($img, 10, 10, $width - 10, $height - 10, $borderColor);
        
        $y = 40;
        
        // Header
        imagefilledrectangle($img, 20, 20, $width - 20, 180, $lightGray);
        imagerectangle($img, 20, 20, $width - 20, 180, $borderColor);
        
        // Bilingual title
        $this->drawTTFText($img, $receiptData['template']->title ?? 'QNB Alahli Bank', $width / 2, $y + 30, $black, $fontPath, 20, 'center');
        $this->drawTTFText($img, 'eFood Wallet Top-Up', $width / 2, $y + 55, $black, $fontPath, 16, 'center');
        
        $this->drawTTFText($img, $receiptData['date'], $width / 2, $y + 90, $gray, $fontPath, 14, 'center');
        $this->drawTTFText($img, now()->format('M d, Y'), $width / 2, $y + 110, $gray, $fontPath, 12, 'center');
        
        // Title section
        $y = 220;
        imagefilledrectangle($img, 20, $y, $width - 20, $y + 60, $green);
        $this->drawTTFText($img, 'إيصال شحن محفظة', $width / 2, $y + 35, $white, $fontPath, 18, 'center');
        $this->drawTTFText($img, 'Wallet Top-Up Receipt', $width / 2, $y + 55, $white, $fontPath, 14, 'center');
        
        // Details
        $y = 300;
        $lineHeight = 45;
        
        // Transaction ID
        $this->drawTTFLabelValue($img, 'رقم العملية / Transaction ID:', $receiptData['transaction_id'], 50, $y, $gray, $black, $fontPath, 12);
        $y += $lineHeight;
        
        // Account
        $this->drawTTFLabelValue($img, 'الحساب / Account:', $receiptData['account_number'], 50, $y, $gray, $black, $fontPath, 12);
        $y += $lineHeight;
        
        // Customer
        $this->drawTTFLabelValue($img, 'العميل / Customer:', $receiptData['customer_name'], 50, $y, $gray, $black, $fontPath, 12);
        $y += $lineHeight;
        
        // Divider
        imageline($img, 30, $y, $width - 30, $y, $borderColor);
        $y += 30;
        
        // Amount section
        imagefilledrectangle($img, 30, $y, $width - 30, $y + 100, $lightGray);
        $this->drawTTFLabelValue($img, 'المبلغ / Amount:', $receiptData['amount'] . ' ' . $receiptData['currency'], 50, $y + 25, $gray, $green, $fontPath, 14);
        $this->drawTTFLabelValue($img, 'الضريبة / Tax:', $receiptData['tax'] . ' ' . $receiptData['currency'], 50, $y + 60, $gray, $black, $fontPath, 12);
        $y += 120;
        
        // Previous balance
        $this->drawTTFLabelValue($img, 'الرصيد السابق / Previous Balance:', $receiptData['previous_balance'] . ' ' . $receiptData['currency'], 50, $y, $gray, $black, $fontPath, 12);
        $y += $lineHeight;
        
        // New Balance (highlighted)
        imagefilledrectangle($img, 30, $y - 10, $width - 30, $y + 50, $lightGray);
        imagesetthickness($img, 2);
        imagerectangle($img, 30, $y - 10, $width - 30, $y + 50, $green);
        $this->drawTTFLabelValue($img, 'الرصيد الجديد / New Balance:', $receiptData['new_balance'] . ' ' . $receiptData['currency'], 50, $y + 20, $gray, $white, $fontPath, 16);
        
        $y += 90;
        
        // Footer
        imageline($img, 30, $y, $width - 30, $y, $borderColor);
        $y += 30;
        
        $footerTextAr = $this->replacePlaceholders($receiptData['template']->footer_text ?? 'شكراً لاستخدام خدماتنا', $receiptData);
        $footerTextEn = $this->replacePlaceholders('Thank you for using our service', $receiptData);
        $this->drawTTFText($img, $footerTextAr, $width / 2, $y, $gray, $fontPath, 12, 'center');
        $this->drawTTFText($img, $footerTextEn, $width / 2, $y + 20, $gray, $fontPath, 10, 'center');
        $y += 40;
        
        $arabicDate = Carbon::now()->locale('ar')->formatLocalized('%A، %d %B %Y');
        $englishDate = now()->format('l, F d, Y');
        $this->drawTTFText($img, $arabicDate, $width / 2, $y, $gray, $fontPath, 11, 'center');
        $this->drawTTFText($img, $englishDate, $width / 2, $y + 20, $gray, $fontPath, 10, 'center');
        
        // Save
        $fileName = 'receipt_' . $receiptData['transaction_id'] . '_' . time() . '.png';
        $filePath = 'receipts/images/' . $fileName;
        $fullPath = storage_path('app/public/' . $filePath);
        
        Storage::disk('public')->makeDirectory('receipts/images');
        imagepng($img, $fullPath, 9);
        imagedestroy($img);
        
        Log::info('Receipt image generated with fallback', ['path' => $fullPath, 'used_ttf' => $fontPath ? 'yes' : 'no']);
        return $fullPath;
    }

    /**
     * Get a readable TTF font path
     */
    private function getReadableFontPath(): ?string
    {
        $fonts = [
            public_path('fonts/Amiri-Regular.ttf'),
            public_path('fonts/DejaVuSans.ttf')
        ];
        
        foreach ($fonts as $path) {
            if (file_exists($path) && is_readable($path)) {
                Log::info('Using TTF font', ['path' => $path]);
                return $path;
            }
        }
        
        return null;
    }

    /**
     * Draw TTF text (enhanced RTL for Arabic)
     */
    private function drawTTFText($img, $text, $x, $y, $color, $fontPath, $size, $align = 'left')
    {
        if (!$fontPath || !file_exists($fontPath) || !is_readable($fontPath)) {
            $this->drawBitmapText($img, $text, $x, $y, $color, $size, $align);
            return;
        }
        
        try {
            $bbox = imagettfbbox($size, 0, $fontPath, $text);
            if ($bbox === false) {
                throw new \Exception('TTF bbox failed');
            }
            
            $textWidth = $bbox[4] - $bbox[0];
            
            if ($align === 'center') {
                $x -= $textWidth / 2;
            } elseif ($align === 'right') {
                $x -= $textWidth;
            }
            
            // Improved RTL: Reverse Arabic segments
            if (preg_match('/[\x{0600}-\x{06FF}]/u', $text)) {
                $text = $this->reverseArabic($text);
            }
            
            imagettftext($img, $size, 0, $x, $y + $size, $color, $fontPath, $text);
        } catch (\Exception $e) {
            Log::warning('TTF rendering failed, falling back to bitmap', [
                'text' => substr($text, 0, 50),
                'font' => $fontPath,
                'error' => $e->getMessage()
            ]);
            $this->drawBitmapText($img, $text, $x, $y, $color, $size, $align);
        }
    }

    /**
     * Bitmap fallback for text
     */
    private function drawBitmapText($img, $text, $x, $y, $color, $size, $align = 'left')
    {
        $fontSize = min(5, max(1, round($size / 4)));
        $textWidth = imagefontwidth($fontSize) * strlen($text);
        
        if ($align === 'center') {
            $x -= $textWidth / 2;
        } elseif ($align === 'right') {
            $x -= $textWidth;
        }
        
        if (preg_match('/[\x{0600}-\x{06FF}]/u', $text)) {
            $text = $this->reverseArabic($text);
        }
        
        imagestring($img, $fontSize, $x, $y, $text, $color);
    }

    /**
     * Draw TTF label-value
     */
    private function drawTTFLabelValue($img, $label, $value, $x, $y, $labelColor, $valueColor, $fontPath, $size)
    {
        $this->drawTTFText($img, $label, $x, $y, $labelColor, $fontPath, $size, 'left');
        
        if ($fontPath && file_exists($fontPath)) {
            try {
                $bbox = imagettfbbox($size, 0, $fontPath, $label);
                $labelWidth = $bbox[4] - $bbox[0];
            } catch (\Exception $e) {
                $labelWidth = strlen($label) * ($size / 2);
            }
        } else {
            $labelWidth = strlen($label) * 6;
        }
        
        $valueX = $x + $labelWidth + 20;
        $this->drawTTFText($img, $value, $valueX, $y, $valueColor, $fontPath, $size, 'left');
    }

    /**
     * Replace placeholders in text
     */
    private function replacePlaceholders(string $text, array $data): string
    {
        foreach ($data as $key => $value) {
            $text = str_replace('{' . $key . '}', $value, $text);
        }
        return $text;
    }

    /**
     * Simple RTL reverse for Arabic
     */
    private function reverseArabic(string $text): string
    {
        preg_match_all('/[\x{0600}-\x{06FF}]+/u', $text, $matches);
        foreach ($matches[0] as $match) {
            $text = str_replace($match, strrev($match), $text);
        }
        return $text;
    }

    /**
     * Prepare receipt data with placeholders
     */
    private function prepareReceiptData(array $data, $template): array
    {
        $prepared = [
            'template' => $template,
            'transaction_id' => $data['transaction_id'],
            'date' => $data['date'] ?? now()->format('d/m/Y'),
            'time' => $data['time'] ?? now()->format('h:i A'),
            'customer_name' => $data['customer_name'],
            'account_number' => str_pad($data['account_number'], 8, '0', STR_PAD_LEFT),
            'branch' => $data['branch'] ?? 'Main Branch',
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'EGP',
            'previous_balance' => $data['previous_balance'] ?? '0.00',
            'new_balance' => $data['new_balance'],
            'tax' => $data['tax'] ?? '0.00',
            'company_name' => $template?->title ?? config('app.name'),
            'company_logo' => $template?->logo ? asset('storage/whatsapp_template/' . $template->logo) : null,
            'company_phone' => config('company.phone', '8009999'),
            'company_email' => config('company.email', 'info@company.com'),
            'company_address' => config('company.address', 'Yemen - Aden'),
        ];

        if ($template) {
            $prepared['footer_text'] = $this->replacePlaceholders($template->footer_text ?? '', $prepared);
        }

        return $prepared;
    }

    /**
     * Generate receipt with custom format
     */
    public function generateReceipt(array $data, string $format = 'image'): string
    {
        if ($format === 'pdf') {
            return $this->generateReceiptPDF($data);
        }
        
        return $this->generateReceiptImage($data);
    }

    /**
     * Extract data from PDF path
     */
    private function extractDataFromPath(string $pdfPath): array
    {
        $fileName = basename($pdfPath, '.pdf');
        return [
            'transaction_id' => explode('_', $fileName)[1] ?? 'UNKNOWN',
            'date' => now()->format('d/m/Y'),
            'customer_name' => 'Customer',
            'account_number' => 'N/A',
            'amount' => '0.00 EGP',
            'currency' => 'EGP',
            'previous_balance' => '0.00',
            'new_balance' => '0.00',
            'tax' => '0.00',
        ];
    }
}