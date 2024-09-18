<?php

namespace Modules\Authetication\Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserTableSeeder::class,
            RoleTableSeeder::class,
            PermissionTableSeeder::class,
            UserRoleTableSeeder::class,
            UserPermissionTableSeeder::class,
            RolePermissionTableSeeder::class,
        ]);
    }
}
