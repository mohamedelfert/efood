<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AddonSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // SMS
            [
                'id'              => '070c6bbd-d777-11ed-96f4-0c7a158e4469',
                'key_name'      => 'twilio',
                'live_values'   => json_encode([
                    'gateway'              => 'twilio',
                    'mode'                 => 'live',
                    'status'               => 0,
                    'sid'                  => 'data',
                    'messaging_service_sid'=> 'data',
                    'token'                => 'data',
                    'from'                 => 'data',
                    'otp_template'         => 'data'
                ]),
                'test_values'   => json_encode([
                    'gateway'              => 'twilio',
                    'mode'                 => 'live',
                    'status'               => 0,
                    'sid'                  => 'data',
                    'messaging_service_sid'=> 'data',
                    'token'                => 'data',
                    'from'                 => 'data',
                    'otp_template'         => 'data'
                ]),
                'settings_type' => 'sms_config',
                'mode'          => 'live',
                'is_active'     => 0,
                'additional_data' => null,
            ],

            // Payment Gateways – Paymob
            [
                'id'             => (string) Str::uuid(),
                'key_name'       => 'paymob',
                'settings_type'  => 'payment_config',
                'is_active'      => 1,
                'mode'           => 'live',
                'live_values'    => json_encode([
                    'gateway'             => 'paymob',
                    'status'              => 1,
                    'api_key'             => '',
                    'hmac_secret'         => '',
                    'supported_currencies'=> ['EGP','USD']
                ]),
                'test_values'    => json_encode([
                    'gateway'     => 'paymob',
                    'status'      => 0,
                    'api_key'     => '',
                    'hmac_secret' => ''
                ]),
                'additional_data'=> json_encode([
                    'gateway_title' => 'paymob',
                    'gateway_image' => ''
                ]),
            ],

            // QIB
            [
                'id'             => (string) Str::uuid(),
                'key_name'       => 'qib',
                'settings_type'  => 'payment_config',
                'is_active'      => 1,
                'mode'           => 'live',
                'live_values'    => json_encode(['gateway' => 'qib', 'status' => 1]),
                'test_values'    => json_encode(['status' => 0]),
                'additional_data'=> json_encode([
                    'gateway_title' => 'qib',
                    'gateway_image' => ''
                ]),
            ]
        ];

        foreach ($settings as $setting) {
            DB::table('addon_settings')->updateOrInsert(
                ['key_name' => $setting['key_name'], 'settings_type' => $setting['settings_type']],
                $setting + [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}