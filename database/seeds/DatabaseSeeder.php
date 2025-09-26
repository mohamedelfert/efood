<?php

use Illuminate\Database\Seeder;
use Database\Seeders\TestDataTableSeeder;
use Database\Seeders\CurrenciesTableSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
         $this->call([
             AdminTableSeeder::class,
             CurrenciesTableSeeder::class,
             TestDataTableSeeder::class
         ]);
    }
}
