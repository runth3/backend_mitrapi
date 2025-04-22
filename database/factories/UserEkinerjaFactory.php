<?php

namespace Database\Factories;

use App\Models\UserEkinerja;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserEkinerjaFactory extends Factory
{
    protected $model = UserEkinerja::class;

    public function definition()
    {
        return [
            'UID' => $this->faker->unique()->userName,
            'title' => $this->faker->title,
            'nama' => $this->faker->name,
            'gelar' => $this->faker->title,
            'NIP' => $this->faker->unique()->numerify('##################'),
            'sts' => $this->faker->randomElement(['1', '0']),
            'last_login' => $this->faker->dateTimeThisYear(),
            'uType' => $this->faker->randomElement(['admin', 'user']),
            'opd_id' => $this->faker->uuid,
            'opd_unit_id' => $this->faker->uuid,
            'opd_unit_sub_id' => $this->faker->uuid,
            'apvId' => $this->faker->uuid,
            'isOps' => $this->faker->boolean,
            'opd' => $this->faker->company,
            'jabatan_id' => $this->faker->uuid,
            'pangkat_id' => $this->faker->uuid,
            'simpeg_id' => $this->faker->uuid,
            'avatar' => $this->faker->imageUrl(),
            'b_day' => $this->faker->date(),
            'tunjangan' => $this->faker->randomNumber(6),
            'isOpsTp' => $this->faker->boolean,
            'last_updated' => now(),
        ];
    }
}