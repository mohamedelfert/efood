<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AddonSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            [
                'id' => '070c6bbd-d777-11ed-96f4-0c7a158e4469',
                'key_name' => 'twilio',
                'live_values' => '{"gateway":"twilio","mode":"live","status":"0","sid":"data","messaging_service_sid":"data","token":"data","from":"data","otp_template":"data"}',
                'test_values' => '{"gateway":"twilio","mode":"live","status":"0","sid":"data","messaging_service_sid":"data","token":"data","from":"data","otp_template":"data"}',
                'settings_type' => 'sms_config',
                'mode' => 'live',
                'is_active' => 0,
                'created_at' => null,
                'updated_at' => '2023-08-12 07:01:29',
                'additional_data' => null,
            ],
            // Third-Party Gateways
            ['key_name' => 'paymob',       'settings_type' => 'payment_config', 'is_active' => 1, 'mode' => 'live', 'live_values' => '{"gateway":"paymob","status":1,"api_key":"","hmac_secret":"","supported_currencies":["EGP","USD"]}', 'test_values' => '{"gateway":"paymob","status":0,"api_key":"","hmac_secret":""}', 'additional_data' => '{"gateway_title":"paymob","gateway_image":""}'],
            ['key_name' => 'qib',          'settings_type' => 'payment_config', 'is_active' => 1, 'mode' => 'live', 'live_values' => '{"gateway":"qib","status":1}', 'test_values' => '{"status":0}', 'additional_data' => '{"gateway_title":"qib","gateway_image":""}'],
        ];

        foreach ($settings as $setting) {
            DB::table('addon_settings')->updateOrInsert(
                ['key_name' => $setting['key_name'], 'settings_type' => $setting['settings_type']],
                $setting
            );
        }
    }
}