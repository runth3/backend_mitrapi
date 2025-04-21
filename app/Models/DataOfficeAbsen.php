<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataOfficeAbsen extends Model
{
    use HasFactory;

    protected $connection = 'mysql_simpeg';
    protected $table = 'vd_ref_instansi';

    protected $fillable = [
        'id_instansi',
        'nama_instansi',
        'alamat_instansi',
        'kota',
        'kodepos',
        'phone',
        'fax',
        'website',
        'email',
    ];

    protected $hidden = [
        'nama_bagian',
        'id_instansi_induk',
        'cre_on',
        'cre_by',
        'upd_on',
        'upd_by',
        'aktif',
        'cap_surat',
        'ordered',
    ];

    // Relasi: Tidak ada relasi eksplisit saat ini
    // Catatan: Jika ada relasi hasMany ke DataPegawaiAbsen atau UserAbsen yang dihilangkan, tambahkan kembali jika diperlukan
    // Contoh relasi yang mungkin dihilangkan:
    /*
    public function pegawai()
    {
        return $this->hasMany(DataPegawaiAbsen::class, 'id_instansi', 'id_instansi');
    }
    public function users()
    {
        return $this->hasMany(UserAbsen::class, 'id_instansi', 'id_instansi');
    }
    */

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (app()->environment('testing')) {
                $model->setConnection('sqlite');
                $model->setTable('simpeg_vd_ref_instansi');
            }
        });
    }
}