<?php

namespace Modules\Authetication\Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserRoleTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for( $user_id = 1; $user_id <= 10; $user_id++ ) {
            $role_id = $user_id <= 2 ? $user_id : 5;
            DB::table('users_roles')->insert([  
                'user_id' => $user_id,
                'role_id' => $role_id,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}
