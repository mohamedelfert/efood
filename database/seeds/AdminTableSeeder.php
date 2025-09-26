<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('admins')->insert([
            'id' => 1,
            'f_name' => 'mohamed',
            'l_name' => 'elfert',
            'phone' => '01011731954',
            'email' => 'mohamedelfert@yahoo.com',
            'image' => 'def.png',
            'password' => bcrypt(123456789),
            'identity_number' => 'ADMIN001',
            'identity_type' => 'NID',
            'identity_image' => 'def.png',
            'remember_token' => Str::random(10),
            'created_at'=>now(),
            'updated_at'=>now()
        ]);
    }
}
