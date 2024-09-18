<?php

namespace Modules\Authetication\Database\Seeders;

use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserTableSeeder extends Seeder
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password = '123456';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = array([
            'username' => 'administrator',
            'name' => 'Administrator',
            'email' => 'administrator@example.com',
            'password' => '$2y$12$ZqOZO0XbPS5Meddz1qpmgOObe4PWyQ7VD40auYvgIxH.3F/kFkZz2', // 123456
            'photo' => 'default_photo.jpg',
        ],[
            'username' => 'admin',
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => '$2y$12$ZqOZO0XbPS5Meddz1qpmgOObe4PWyQ7VD40auYvgIxH.3F/kFkZz2', // 123456
            'photo' => 'default_photo.jpg',
        ],[
            'username' => 'manager',
            'name' => 'Manager',
            'email' => 'manager@example.com',
            'password' => '$2y$12$ZqOZO0XbPS5Meddz1qpmgOObe4PWyQ7VD40auYvgIxH.3F/kFkZz2', // 123456
            'photo' => 'default_photo.jpg',
        ],[
            'username' => 'leader',
            'name' => 'Leader',
            'email' => 'leader@example.com',
            'password' => '$2y$12$ZqOZO0XbPS5Meddz1qpmgOObe4PWyQ7VD40auYvgIxH.3F/kFkZz2', // 123456
            'photo' => 'default_photo.jpg',            
        ],[
            'username' => 'user',
            'name' => 'User',
            'email' => 'user@example.com',
            'password' => '$2y$12$ZqOZO0XbPS5Meddz1qpmgOObe4PWyQ7VD40auYvgIxH.3F/kFkZz2', // 123456
            'photo' => 'default_photo.jpg',  
            
        ]);

        foreach($data as $item) {
            DB::table('users')->insert([  
                'username' => $item['username'], 
                'name' => $item['name'], 
                'email' => $item['email'], 
                'password' => $item['password'], 
                'photo' => $item['photo'],
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        for($i=6; $i<=10; $i++){
            DB::table('users')->insert([  
                'username' => fake()->userName(), 
                'name' => fake()->name(), 
                'email' => fake()->unique()->safeEmail(), 
                'password' => Hash::make(static::$password ??= '123123'),
                'photo' => 'default_photo.jpg',
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}
