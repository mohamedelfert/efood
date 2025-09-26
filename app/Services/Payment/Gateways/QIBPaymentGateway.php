<?php

namespace App\Services\Payment\Gateways;


use Illuminate\Support\Facades\Http;
use App\Services\Payment\PaymentGatewayInterface;

class QIBPaymentGateway implements PaymentGatewayInterface
{
    protected $baseUrl = 'https://newdc.qtb-bank.com:5052/PayBills';
    protected $apiKey;
    protected $appKey;

    public function __construct()
    {
        $this->apiKey = env('QIB_API_KEY');
        $this->appKey = env('QIB_APP_KEY');
    }

    private function encryptString(string $key, string $plainInput): string
    {
        $iv = str_repeat("\0", 16);
        $aes = openssl_encrypt($plainInput, 'AES-256-CBC', $key, 0, $iv);
        return base64_encode($aes);
    }

    private function normalizeCustomerNumber(string $number): string
    {
        if (strlen($number) == 9 && substr($number, 0, 1) == '4') {
            return substr($number, 1);
        }
        return $number;
    }

    public function requestPayment(array $data): array
    {
        $data['customer_no'] = $this->encryptString(
            $this->apiKey,
            $this->normalizeCustomerNumber((string)$data['payment_DestNation'])
        );

        $response = Http::withHeaders([
            'X-API-KEY' => $this->apiKey,
            'X-APP-KEY' => $this->appKey,
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}/E_Payment/RequestPayment", $data);

        return $response->json();
    }

    public function confirmPayment(array $data): array
    {
        $data['customer_no'] = $this->encryptString(
            $this->apiKey,
            $this->normalizeCustomerNumber((string)$data['payment_DestNation'])
        );

        $response = Http::withHeaders([
            'X-API-KEY' => $this->apiKey,
            'X-APP-KEY' => $this->appKey,
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}/E_Payment/ConfirmPayment", $data);

        return $response->json();
    }

    public function resendOTP(array $data): array
    {
        $data['customer_no'] = $this->encryptString(
            $this->apiKey,
            $this->normalizeCustomerNumber((string)$data['payment_DestNation'])
        );

        $response = Http::withHeaders([
            'X-API-KEY' => $this->apiKey,
            'X-APP-KEY' => $this->appKey,
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}/E_Payment/ResendOTP", $data);

        return $response->json();
    }

    public function handleCallback(array $data): array
    {
        return ['status' => 'success', 'data' => $data];
    }

    public function getPaymentMethods(): array
    {
        return [['id' => 1, 'name' => 'QIB OTP', 'description' => 'Payment via OTP']];
    }
}