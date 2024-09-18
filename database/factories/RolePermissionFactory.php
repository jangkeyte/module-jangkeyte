<?php

namespace Modules\Authetication\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class RolePermissionFactory extends Factory
{
    public function definition(): array
    {        
        return [
            'role_id' => fake()->numberBetween(1, 5),
        ];
    }
}
