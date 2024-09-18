<?php

namespace Modules\Authetication\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class UserPermissionFactory extends Factory
{
    public function definition(): array
    {        
        return [
            'user_id' => fake()->numberBetween(1, 5),
            'permission_id' => fake()->numberBetween(1, 5),
        ];
    }
}
