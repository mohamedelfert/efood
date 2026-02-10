<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add to addon_settings
        $kuraimiSettings = [
            'id' => Str::uuid(),
            'key_name' => 'kuraimi',
            'live_values' => json_encode([
                'gateway' => 'kuraimi',
                'mode' => 'live',
                'status' => '1',
                'gateway_title' => 'Kuraimi Bank'
            ]),
            'test_values' => json_encode([
                'gateway' => 'kuraimi',
                'mode' => 'live',
                'status' => '1'
            ]),
            'settings_type' => 'payment_config',
            'mode' => 'live',
            'is_active' => 1,
            'additional_data' => json_encode([
                'gateway_title' => 'Kuraimi Bank',
                'gateway_image' => ''
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('addon_settings')->updateOrInsert(
            ['key_name' => 'kuraimi', 'settings_type' => 'payment_config'],
            $kuraimiSettings
        );

        // 2. Add to system_payment_methods
        $systemMethod = [
            'method_name' => 'بنك الكريمي',
            'slug' => 'kuraimi',
            'driver_name' => 'kuraimi',
            'settings' => json_encode([]),
            'image' => null,
            'is_active' => 1,
            'mode' => 'live',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('system_payment_methods')->updateOrInsert(
            ['slug' => 'kuraimi'],
            $systemMethod
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('addon_settings')->where('key_name', 'kuraimi')->delete();
        DB::table('system_payment_methods')->where('slug', 'kuraimi')->delete();
    }
};
