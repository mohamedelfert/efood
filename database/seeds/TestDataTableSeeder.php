<?php

namespace Database\Seeders;

use App\Model\AddOn;
use App\Model\Branch;
use App\Model\Product;
use App\Model\CustomerAddress;
use App\Model\ProductByBranch;
use Illuminate\Database\Seeder;

class TestDataTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seed Product
        Product::updateOrCreate(
            ['id' => 1],
            [
                'name' => 'Test Product',
                'price' => 300.00,
                'discount_type' => 'amount',
                'discount' => 0,
                'variations' => json_encode([]),
                'tax' => 15,
                'popularity_count' => 0,
            ]
        );

        // Seed Branch
        Branch::updateOrCreate(
            ['id' => 1],
            [
                'name' => 'Main Branch',
                'preparation_time' => 30,
            ]
        );

        // Seed ProductByBranch
        ProductByBranch::updateOrCreate(
            ['product_id' => 1, 'branch_id' => 1],
            [
                'price' => 300.00,
                'discount_type' => 'amount',
                'discount' => 0,
                'stock_type' => 'unlimited',
                'stock' => 100,
                'sold_quantity' => 0,
                'variations' => json_encode([]),
            ]
        );

        // Seed AddOn
        AddOn::updateOrCreate(
            ['id' => 1],
            [
                'name' => 'Test Add-on',
                'price' => 50.00,
                'tax' => 15,
            ]
        );

        // Seed CustomerAddress
        CustomerAddress::updateOrCreate(
            ['id' => 1],
            [
                'user_id' => 8,
                'address' => '123 Test Street',
                'contact_person_number' => '+1234567890',
                'latitude' => '24.7136',
                'longitude' => '46.6753',
            ]
        );
    }
}
