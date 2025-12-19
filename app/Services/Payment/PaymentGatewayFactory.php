<?php

namespace App\Services\Payment;

use App\Services\Payment\PaymentGatewayInterface;
use App\Services\Payment\Gateways\QIBPaymentGateway;
use App\Services\Payment\Gateways\StripePaymentGateway;



class PaymentGatewayFactory
{
    public static function create(string $gateway): ?PaymentGatewayInterface
    {
        return match (strtolower($gateway)) {
            'qib' => app(QIBPaymentGateway::class),
            'stripe' => app(StripePaymentGateway::class),
            default => null,
        };
    }
}