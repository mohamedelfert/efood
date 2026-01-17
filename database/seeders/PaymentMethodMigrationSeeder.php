<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\SystemPaymentMethod;
use App\Models\Setting;

class PaymentMethodMigrationSeeder extends Seeder
{
    public function run()
    {
        $settings = DB::table('addon_settings')
            ->whereIn('key_name', ['stripe', 'qib'])
            ->where('settings_type', 'payment_config')
            ->get();

        foreach ($settings as $setting) {
            $liveValues = json_decode($setting->live_values, true);
            $testValues = json_decode($setting->test_values, true);
            $additionalData = json_decode($setting->additional_data, true);

            $mode = $setting->mode ?? 'test';
            $values = $mode === 'live' ? $liveValues : $testValues;

            // Prepare settings array
            $newSettings = [];
            foreach ($values as $key => $value) {
                if ($key !== 'gateway' && $key !== 'mode' && $key !== 'status') {
                    $newSettings[$key] = $value;
                }
            }

            // Check if exists
            $exists = SystemPaymentMethod::where('driver_name', $setting->key_name)->exists();

            if (!$exists) {
                SystemPaymentMethod::create([
                    'method_name' => $additionalData['gateway_title'] ?? ucfirst($setting->key_name),
                    'slug' => $setting->key_name,
                    'driver_name' => $setting->key_name, // stripe or qib
                    'settings' => $newSettings,
                    'image' => $additionalData['gateway_image'] ?? null,
                    'is_active' => $setting->is_active,
                    'mode' => $mode,
                ]);
            }
        }
    }
}
