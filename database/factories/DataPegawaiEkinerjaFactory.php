<?php

namespace Database\Factories;

use App\Models\DataPegawaiEkinerja;
use Illuminate\Database\Eloquent\Factories\Factory;

class DataPegawaiEkinerjaFactory extends Factory
{
    protected $model = DataPegawaiEkinerja::class;

    public function definition()
    {
        return [
            'id_pegawai' => $this->faker->unique()->uuid,
            'nip' => $this->faker->unique()->numerify('##################'),
            'nama_lengkap' => $this->faker->name,
            'gelar' => $this->faker->title,
            'tempat_lahir' => $this->faker->city,
            'tgl_lahir' => $this->faker->date(),
            'jenis_kelamin' => $this->faker->randomElement(['L', 'P']),
            'id_pangkat' => $this->faker->uuid,
            'id_instansi' => $this->faker->uuid,
            'id_unit_kerja' => $this->faker->uuid,
            'id_sub_unit_kerja' => $this->faker->uuid,
            'id_jabatan' => $this->faker->uuid,
            'tmt_jabatan' => $this->faker->date(),
            'id_eselon' => $this->faker->uuid,
            'alamat' => $this->faker->address,
            'no_telp' => $this->faker->phoneNumber,
        ];
    }
}