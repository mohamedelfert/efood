<?php

namespace App\Services\Payment;

interface PaymentGatewayInterface
{
    public function requestPayment(array $data): array;
    public function confirmPayment(array $data): array;
    public function resendOTP(array $data): array;
    public function handleCallback(array $data): array;
    public function getPaymentMethods(): array;
}