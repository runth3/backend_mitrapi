<?php

namespace Database\Factories;

use App\Models\DataOfficeAbsen;
use Illuminate\Database\Eloquent\Factories\Factory;

class DataOfficeAbsenFactory extends Factory
{
    protected $model = DataOfficeAbsen::class;

    public function definition()
    {
        return [
            'id_instansi' => $this->faker->unique()->uuid,
            'nama_instansi' => $this->faker->company,
            'alamat_instansi' => $this->faker->address,
            'kota' => $this->faker->city,
            'kodepos' => $this->faker->postcode,
            'phone' => $this->faker->phoneNumber,
            'fax' => $this->faker->phoneNumber,
            'website' => $this->faker->url,
            'email' => $this->faker->companyEmail,
        ];
    }
}