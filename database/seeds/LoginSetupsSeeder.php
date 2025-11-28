<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LoginSetupsSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('login_setups')->upsert(
            [
                // Email Verification
                [
                    'id'         => 1,
                    'key'        => 'email_verification',
                    'value'      => '1', // 1 = enabled
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                // Phone (OTP) Verification
                [
                    'id'         => 2,
                    'key'        => 'phone_verification',
                    'value'      => '0', // 0 = disabled
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                // Login Options
                [
                    'id'         => 3,
                    'key'        => 'login_options',
                    'value'      => json_encode([
                        'manual_login'       => 1,
                        'otp_login'          => 0,
                        'social_media_login' => 0,
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                // Social Media Login Providers
                [
                    'id'         => 4,
                    'key'        => 'social_media_for_login',
                    'value'      => json_encode([
                        'google'   => 0,
                        'facebook' => 0,
                        'apple'    => 0,
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ],
            ['id'],
            ['value', 'updated_at']
        );
    }
}