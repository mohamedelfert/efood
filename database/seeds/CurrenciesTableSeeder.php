<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CurrenciesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $now = Carbon::now();

        $currencies = [
            [
                'country' => 'United States',
                'code' => 'USD',
                'symbol' => '$',
                'exchange_rate' => 1.00,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'Yemen',
                'is_primary' => 1,
                'code' => 'YER',
                'symbol' => '﷼',
                'exchange_rate' => 250.00,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'Turkey',
                'code' => 'TRY',
                'symbol' => '₺',
                'exchange_rate' => 32.10,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'Saudi Arabia',
                'code' => 'SAR',
                'symbol' => '﷼',
                'exchange_rate' => 3.75,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'United Arab Emirates',
                'code' => 'AED',
                'symbol' => 'د.إ',
                'exchange_rate' => 3.67,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'Egypt',
                'code' => 'EGP',
                'symbol' => '£',
                'exchange_rate' => 30.90,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]
        ];

        $data = array_map(function ($item) use ($now) {
            return [
                'name'           => $item['country'],
                'country'        => $item['country'],
                'code'           => $item['code'],
                'symbol'         => $item['symbol'],
                'exchange_rate'  => $item['exchange_rate'],
                'is_primary'     => $item['is_primary'] ?? false,
                'is_active'      => true,
                'decimal_places' => $item['decimal_places'] ?? 2,
                'position'       => 'before',
                'created_at'     => $now,
                'updated_at'     => $now,
            ];
        }, $currencies);

        DB::table('currencies')->insert($data);
    }
}