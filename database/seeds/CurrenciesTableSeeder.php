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
                'country' => 'European Union',
                'code' => 'EUR',
                'symbol' => '€',
                'exchange_rate' => 0.93,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'United Kingdom',
                'code' => 'GBP',
                'symbol' => '£',
                'exchange_rate' => 0.79,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'Japan',
                'code' => 'JPY',
                'symbol' => '¥',
                'exchange_rate' => 147.50,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'India',
                'code' => 'INR',
                'symbol' => '₹',
                'exchange_rate' => 83.20,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'Australia',
                'code' => 'AUD',
                'symbol' => 'A$',
                'exchange_rate' => 1.52,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'Canada',
                'code' => 'CAD',
                'symbol' => 'C$',
                'exchange_rate' => 1.35,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'Switzerland',
                'code' => 'CHF',
                'symbol' => 'Fr',
                'exchange_rate' => 0.88,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'China',
                'code' => 'CNY',
                'symbol' => '¥',
                'exchange_rate' => 7.18,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'Hong Kong',
                'code' => 'HKD',
                'symbol' => 'HK$',
                'exchange_rate' => 7.82,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'New Zealand',
                'code' => 'NZD',
                'symbol' => 'NZ$',
                'exchange_rate' => 1.67,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'Sweden',
                'code' => 'SEK',
                'symbol' => 'kr',
                'exchange_rate' => 10.45,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'Norway',
                'code' => 'NOK',
                'symbol' => 'kr',
                'exchange_rate' => 10.60,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'Denmark',
                'code' => 'DKK',
                'symbol' => 'kr',
                'exchange_rate' => 6.88,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'Singapore',
                'code' => 'SGD',
                'symbol' => 'S$',
                'exchange_rate' => 1.34,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'South Korea',
                'code' => 'KRW',
                'symbol' => '₩',
                'exchange_rate' => 1320.00,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'Thailand',
                'code' => 'THB',
                'symbol' => '฿',
                'exchange_rate' => 35.80,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'Malaysia',
                'code' => 'MYR',
                'symbol' => 'RM',
                'exchange_rate' => 4.67,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'Indonesia',
                'code' => 'IDR',
                'symbol' => 'Rp',
                'exchange_rate' => 15500.00,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'Philippines',
                'code' => 'PHP',
                'symbol' => '₱',
                'exchange_rate' => 56.20,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'Vietnam',
                'code' => 'VND',
                'symbol' => '₫',
                'exchange_rate' => 24350.00,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'Brazil',
                'code' => 'BRL',
                'symbol' => 'R$',
                'exchange_rate' => 4.95,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'Mexico',
                'code' => 'MXN',
                'symbol' => '$',
                'exchange_rate' => 17.20,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'Russia',
                'code' => 'RUB',
                'symbol' => '₽',
                'exchange_rate' => 91.50,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'South Africa',
                'code' => 'ZAR',
                'symbol' => 'R',
                'exchange_rate' => 18.90,
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
                'country' => 'Israel',
                'code' => 'ILS',
                'symbol' => '₪',
                'exchange_rate' => 3.68,
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
            ],
            [
                'country' => 'Nigeria',
                'code' => 'NGN',
                'symbol' => '₦',
                'exchange_rate' => 780.00,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'Kenya',
                'code' => 'KES',
                'symbol' => 'KSh',
                'exchange_rate' => 157.50,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'Pakistan',
                'code' => 'PKR',
                'symbol' => '₨',
                'exchange_rate' => 280.00,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'Bangladesh',
                'code' => 'BDT',
                'symbol' => '৳',
                'exchange_rate' => 110.00,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'Argentina',
                'code' => 'ARS',
                'symbol' => '$',
                'exchange_rate' => 350.00,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'Chile',
                'code' => 'CLP',
                'symbol' => '$',
                'exchange_rate' => 850.00,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'Colombia',
                'code' => 'COP',
                'symbol' => '$',
                'exchange_rate' => 3900.00,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'Peru',
                'code' => 'PEN',
                'symbol' => 'S/',
                'exchange_rate' => 3.75,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'Czech Republic',
                'code' => 'CZK',
                'symbol' => 'Kč',
                'exchange_rate' => 22.50,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'Hungary',
                'code' => 'HUF',
                'symbol' => 'Ft',
                'exchange_rate' => 350.00,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'Poland',
                'code' => 'PLN',
                'symbol' => 'zł',
                'exchange_rate' => 4.15,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'Romania',
                'code' => 'RON',
                'symbol' => 'lei',
                'exchange_rate' => 4.55,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'Ukraine',
                'code' => 'UAH',
                'symbol' => '₴',
                'exchange_rate' => 36.80,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]
        ];

        // Check if table exists and insert data
        if (DB::getSchemaBuilder()->hasTable('currencies')) {
            DB::table('currencies')->insert($currencies);
            $this->command->info('Currencies table seeded successfully with ' . count($currencies) . ' currencies!');
        } else {
            $this->command->error('Currencies table does not exist!');
        }
    }
}