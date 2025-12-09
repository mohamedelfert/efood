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
            [
                'id' => 'b8992bd4-d6a0-11ed-962c-0c7a158e4469',
                'key_name' => 'paymob',
                'live_values' => '{"gateway":"paymob","mode":"live","status":"0"}',
                'test_values' => '{"gateway":"paymob","mode":"live","status":"0"}',
                'settings_type' => 'payment_config',
                'mode' => 'test',
                'is_active' => 0,
                'created_at' => null,
                'updated_at' => null,
                'additional_data' => '{"gateway_title":"paymob","gateway_image":""}',
            ],
            [
                'id' => 'c41c0dcd-d119-11ed-9f67-0c7a158e4469',
                'key_name' => 'qib',
                'live_values' => '{"gateway":"qib","mode":"live","status":"0"}',
                'test_values' => '{"gateway":"qib","mode":"live","status":"0"}',
                'settings_type' => 'payment_config',
                'mode' => 'test',
                'is_active' => 0,
                'created_at' => null,
                'updated_at' => '2023-08-30 04:49:15',
                'additional_data' => '{"gateway_title":"qib","gateway_image":""}',
            ]
        ];

        DB::table('addon_settings')->insertOrIgnore($settings);
    }
}