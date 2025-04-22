<?php

namespace Database\Factories;

use App\Models\UserAbsen;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserAbsenFactory extends Factory
{
    protected $model = UserAbsen::class;

    public function definition()
    {
        return [
            'id' => $this->faker->unique()->uuid,
            'lvl' => $this->faker->randomElement(['1', '2', '3']),
            'name' => $this->faker->userName,
            'id_instansi' => $this->faker->uuid,
            'id_unit_kerja' => $this->faker->uuid,
            'id_pejabat' => $this->faker->uuid,
            'phone' => $this->faker->phoneNumber,
            'email' => $this->faker->safeEmail,
            'active' => $this->faker->boolean,
            'full_akses' => $this->faker->boolean,
        ];
    }
}