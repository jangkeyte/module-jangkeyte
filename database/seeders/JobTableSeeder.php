<?php

namespace Modules\JangKeyte\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JobTableSeeder extends Seeder
{
    public function run(): void
    {
        for($i = 1; $i <= 9; $i++){
            DB::table('jobs')->insert([
                'title' => fake()->name(),
                'name' => fake()->name(),
                'image' => fake()->file(public_path("storage/uploads/tmp/"), public_path("storage/uploads/jobs/"), false),
                'birthday' => fake()->date(),
                'age' => fake()->randomDigitNotNull(),
                'sexual' => fake()->boolean(),
                'description' => fake()->paragraph(2),
                'start_time' => fake()->dateTimeBetween("-1 week", "+3 week"),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
