<?php

namespace Modules\Authetication\Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = array('info' => 'Browse', 'success' => 'Read', 'warning' => 'Edit', 'primary' => 'Add', 'danger' => 'Delete');
        $objects = array('User', 'Role', 'Layout', 'Room', 'Booking', 'Equipment', 'FoodDrink', 'Status');
        foreach($objects as $object) {
            foreach($permissions as $code => $permission) {
                DB::table('permissions')->insert([  
                    'name' => $permission . ' ' . $object,
                    'slug' => strtolower(str_replace(' ', '-', $permission) . '-' . str_replace(' ', '-', $object)),
                    'group' => $object,
                    'description' => 'User can ' . $permission . ' ' . $object,
                    'code' => $code,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }
    }
}
