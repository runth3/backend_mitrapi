<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataOfficeEkinerja extends Model
{
    use HasFactory;

    protected $connection = 'mysql_ekin';
    protected $table = 'opd';

    protected $fillable = [
        'id',
        'nama',
        'ka_nip',
        'ka_nama',
        'ka_jabatan',
        'tmt_jabatan',
        'idk',
        'jam_kerja',
        'menit_kerja',
        'menit_kerja_harian',
    ];

    // Relasi: Tidak ada relasi eksplisit saat ini
    // Catatan: Jika ada relasi hasMany ke DataPegawaiEkinerja atau UserEkinerja yang dihilangkan, tambahkan kembali jika diperlukan
    // Contoh relasi yang mungkin dihilangkan:
    /*
    public function pegawai()
    {
        return $this->hasMany(DataPegawaiEkinerja::class, 'id_instansi', 'id');
    }
    public function users()
    {
        return $this->hasMany(UserEkinerja::class, 'opd_id', 'id');
    }
    */

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (app()->environment('testing')) {
                $model->setConnection('sqlite');
            }
        });
        static::updating(function ($model) {
            if (app()->environment('testing')) {
                $model->setConnection('sqlite');
            }
        });
    }
}