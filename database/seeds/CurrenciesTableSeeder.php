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
                'currency_code' => 'USD',
                'currency_symbol' => '$',
                'exchange_rate' => 1.00,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'European Union',
                'currency_code' => 'EUR',
                'currency_symbol' => '€',
                'exchange_rate' => 0.93,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'United Kingdom',
                'currency_code' => 'GBP',
                'currency_symbol' => '£',
                'exchange_rate' => 0.79,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'Japan',
                'currency_code' => 'JPY',
                'currency_symbol' => '¥',
                'exchange_rate' => 147.50,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'India',
                'currency_code' => 'INR',
                'currency_symbol' => '₹',
                'exchange_rate' => 83.20,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'Australia',
                'currency_code' => 'AUD',
                'currency_symbol' => 'A$',
                'exchange_rate' => 1.52,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'Canada',
                'currency_code' => 'CAD',
                'currency_symbol' => 'C$',
                'exchange_rate' => 1.35,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'Switzerland',
                'currency_code' => 'CHF',
                'currency_symbol' => 'Fr',
                'exchange_rate' => 0.88,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'China',
                'currency_code' => 'CNY',
                'currency_symbol' => '¥',
                'exchange_rate' => 7.18,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'Hong Kong',
                'currency_code' => 'HKD',
                'currency_symbol' => 'HK$',
                'exchange_rate' => 7.82,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'New Zealand',
                'currency_code' => 'NZD',
                'currency_symbol' => 'NZ$',
                'exchange_rate' => 1.67,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'Sweden',
                'currency_code' => 'SEK',
                'currency_symbol' => 'kr',
                'exchange_rate' => 10.45,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'Norway',
                'currency_code' => 'NOK',
                'currency_symbol' => 'kr',
                'exchange_rate' => 10.60,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'Denmark',
                'currency_code' => 'DKK',
                'currency_symbol' => 'kr',
                'exchange_rate' => 6.88,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'Singapore',
                'currency_code' => 'SGD',
                'currency_symbol' => 'S$',
                'exchange_rate' => 1.34,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'South Korea',
                'currency_code' => 'KRW',
                'currency_symbol' => '₩',
                'exchange_rate' => 1320.00,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'Thailand',
                'currency_code' => 'THB',
                'currency_symbol' => '฿',
                'exchange_rate' => 35.80,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'Malaysia',
                'currency_code' => 'MYR',
                'currency_symbol' => 'RM',
                'exchange_rate' => 4.67,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'Indonesia',
                'currency_code' => 'IDR',
                'currency_symbol' => 'Rp',
                'exchange_rate' => 15500.00,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'Philippines',
                'currency_code' => 'PHP',
                'currency_symbol' => '₱',
                'exchange_rate' => 56.20,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'Vietnam',
                'currency_code' => 'VND',
                'currency_symbol' => '₫',
                'exchange_rate' => 24350.00,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'Brazil',
                'currency_code' => 'BRL',
                'currency_symbol' => 'R$',
                'exchange_rate' => 4.95,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'Mexico',
                'currency_code' => 'MXN',
                'currency_symbol' => '$',
                'exchange_rate' => 17.20,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'Russia',
                'currency_code' => 'RUB',
                'currency_symbol' => '₽',
                'exchange_rate' => 91.50,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'South Africa',
                'currency_code' => 'ZAR',
                'currency_symbol' => 'R',
                'exchange_rate' => 18.90,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'Turkey',
                'currency_code' => 'TRY',
                'currency_symbol' => '₺',
                'exchange_rate' => 32.10,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'Saudi Arabia',
                'currency_code' => 'SAR',
                'currency_symbol' => '﷼',
                'exchange_rate' => 3.75,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'United Arab Emirates',
                'currency_code' => 'AED',
                'currency_symbol' => 'د.إ',
                'exchange_rate' => 3.67,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'Israel',
                'currency_code' => 'ILS',
                'currency_symbol' => '₪',
                'exchange_rate' => 3.68,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'Egypt',
                'currency_code' => 'EGP',
                'currency_symbol' => '£',
                'exchange_rate' => 30.90,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'Nigeria',
                'currency_code' => 'NGN',
                'currency_symbol' => '₦',
                'exchange_rate' => 780.00,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'Kenya',
                'currency_code' => 'KES',
                'currency_symbol' => 'KSh',
                'exchange_rate' => 157.50,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'Pakistan',
                'currency_code' => 'PKR',
                'currency_symbol' => '₨',
                'exchange_rate' => 280.00,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'Bangladesh',
                'currency_code' => 'BDT',
                'currency_symbol' => '৳',
                'exchange_rate' => 110.00,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'Argentina',
                'currency_code' => 'ARS',
                'currency_symbol' => '$',
                'exchange_rate' => 350.00,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'Chile',
                'currency_code' => 'CLP',
                'currency_symbol' => '$',
                'exchange_rate' => 850.00,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'Colombia',
                'currency_code' => 'COP',
                'currency_symbol' => '$',
                'exchange_rate' => 3900.00,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'Peru',
                'currency_code' => 'PEN',
                'currency_symbol' => 'S/',
                'exchange_rate' => 3.75,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'Czech Republic',
                'currency_code' => 'CZK',
                'currency_symbol' => 'Kč',
                'exchange_rate' => 22.50,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'Hungary',
                'currency_code' => 'HUF',
                'currency_symbol' => 'Ft',
                'exchange_rate' => 350.00,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'Poland',
                'currency_code' => 'PLN',
                'currency_symbol' => 'zł',
                'exchange_rate' => 4.15,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'Romania',
                'currency_code' => 'RON',
                'currency_symbol' => 'lei',
                'exchange_rate' => 4.55,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'country' => 'Ukraine',
                'currency_code' => 'UAH',
                'currency_symbol' => '₴',
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