<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\CentralLogics\Helpers;

$scenarios = [
    // OnPremise
    ['type' => 'in_car', 'status' => 'confirmed', 'expect' => 'confirmed'],
    ['type' => 'in_car', 'status' => 'processing', 'expect' => 'processing'],
    ['type' => 'in_car', 'status' => 'delivered', 'expect' => 'out_to_delivery'],
    ['type' => 'in_car', 'status' => 'completed', 'expect' => 'completed'], // New expectation

    // OffPremise
    ['type' => 'delivery', 'status' => 'out_for_delivery', 'expect' => 'out_to_delivery'],
    ['type' => 'delivery', 'status' => 'delivered', 'expect' => 'completed'],

    // New type: branch (OffPremise)
    ['type' => 'branch', 'status' => 'out_for_delivery', 'expect' => 'out_to_delivery'],
];

foreach ($scenarios as $s) {
    $order = ['order_type' => $s['type'], 'order_status' => $s['status']];
    try {
        $res = Helpers::order_status_mapping($order);
        $got = $res['order_status'];
        echo "Type: {$s['type']}, Status: {$s['status']} -> Got: {$got} | Expected: {$s['expect']} - " . ($got === $s['expect'] ? 'PASS' : 'FAIL') . "\n";
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
