<?php

namespace App\Services;

class PaymentGatewayHelper
{
    /**
     * Determine if a gateway requires online URL (redirect-based payment)
     * 
     * @param string $gateway
     * @return bool
     */
    public static function requiresOnlineUrl(string $gateway): bool
    {
        // Gateways that require online URL (redirect/iframe)
        $onlineGateways = [
            'paymob',
            'stripe',
            'paypal',
            'razorpay',
            'flutterwave',
            'paystack',
            'ssl_commerz',
            'mercadopago',
        ];

        return in_array(strtolower($gateway), $onlineGateways);
    }

    /**
     * Determine if a gateway is offline/manual (OTP-based, bank transfer, etc.)
     * 
     * @param string $gateway
     * @return bool
     */
    public static function isOfflineGateway(string $gateway): bool
    {
        // Gateways that are offline/manual
        $offlineGateways = [
            'qib',
            'bank_transfer',
            'manual_payment',
            'offline_payment',
            'cash_on_delivery',
        ];

        return in_array(strtolower($gateway), $offlineGateways);
    }

    /**
     * Get gateway type information
     * 
     * @param string $gateway
     * @return array
     */
    public static function getGatewayInfo(string $gateway): array
    {
        $requiresOnlineUrl = self::requiresOnlineUrl($gateway);
        $isOffline = self::isOfflineGateway($gateway);

        return [
            'gateway' => $gateway,
            'requires_online_url' => $requiresOnlineUrl,
            'is_offline' => $isOffline,
            'payment_type' => $requiresOnlineUrl ? 'redirect' : ($isOffline ? 'manual' : 'direct'),
        ];
    }
}