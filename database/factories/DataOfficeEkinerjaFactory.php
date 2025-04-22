<?php

namespace Database\Factories;

use App\Models\DataOfficeEkinerja;
use Illuminate\Database\Eloquent\Factories\Factory;

class DataOfficeEkinerjaFactory extends Factory
{
    protected $model = DataOfficeEkinerja::class;

    public function definition()
    {
        return [
            'id' => $this->faker->unique()->uuid,
            'nama' => $this->faker->company,
            'ka_nip' => $this->faker->numerify('##################'),
            'ka_nama' => $this->faker->name,
            'ka_jabatan' => $this->faker->jobTitle,
            'tmt_jabatan' => $this->faker->date(),
            'idk' => $this->faker->uuid,
            'jam_kerja' => $this->faker->numberBetween(7, 9),
            'menit_kerja' => $this->faker->numberBetween(420, 540),
            'menit_kerja_harian' => $this->faker->numberBetween(420, 540),
        ];
    }
}