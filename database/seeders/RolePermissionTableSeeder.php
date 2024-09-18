<?php

namespace Modules\Authetication\Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for( $role_id = 1; $role_id <= 2; $role_id++ ) {
            for( $permission_id = 1; $permission_id <= 40; $permission_id++ ) {
                DB::table('roles_permissions')->insert([  
                    'role_id' => $role_id,
                    'permission_id' => $permission_id,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }
    }
}
