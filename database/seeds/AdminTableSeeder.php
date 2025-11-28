<?php

namespace Database\Seeders;

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
            'f_name' => 'Admin',
            'l_name' => 'Admin',
            'phone' => '0123456789',
            'email' => 'admin@gmail.com',
            'image' => 'def.png',
            'admin_role_id' => 1,
            'status' => 1,
            'password' => bcrypt(123456789),
            'identity_number' => 'ADMIN001',
            'identity_type' => 'NID',
            'identity_image' => 'def.png',
            'remember_token' => Str::random(10),
            'created_at'=>now(),
            'updated_at'=>now()
        ]);

        DB::table('admin_roles')->insert([
            'id' => 1,
            'name' => 'Master Admin',
            'module_access' => null,
            'status' => 1,
            'created_at'=>now(),
            'updated_at'=>now()
        ]);
    }
}
