<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataPegawaiAbsen extends Model
{
    use HasFactory;

    protected $connection = 'mysql_simpeg';
    protected $table = 'vd_data_identitas_pegawai';

    protected $fillable = [
        'id_pegawai',
        'nip',
        'nama_lengkap',
        'gelar',
        'tempat_lahir',
        'tgl_lahir',
        'jenis_kelamin',
        'id_pangkat',
        'id_instansi',
        'id_unit_kerja',
        'id_sub_unit_kerja',
        'id_jabatan',
        'tmt_jabatan',
        'id_eselon',
        'alamat',
        'no_telp',
    ];

    protected $hidden = [
        'nip9',
        'title',
        'id_agama',
        'id_status_kepeg',
        'tmt_pns',
        'tmt_cpns',
        'id_jenis_kepeg',
        'tgl_reg_data',
        'id_kedudukan_kepeg',
        'tmt_pangkat',
        'gaji_pokok',
        'tmt_gaji',
        'id_status_perkawinan',
        'id_golongan_darah',
        'id_riwayat_pendidikan',
        'rt',
        'rw',
        'kodepos',
        'id_prov',
        'id_kab',
        'id_kec',
        'id_desa',
        'no_ktp',
        'no_karpeg',
        'no_karis',
        'no_askes',
        'no_taspen',
        'no_npwp',
        'email',
        'id_status_kpe',
        'tgl_kpe',
        'tahun_pendataan',
        'cre_on',
        'cre_by',
        'upd_on',
        'upd_by',
        'status',
        'del_on',
        'del_by',
    ];

    public function officeAbsen()
    {
        return $this->belongsTo(DataOfficeAbsen::class, 'id_instansi', 'id_instansi');
    }

    // Catatan: Relasi officeAbsen diperlukan untuk ProfileController
    // Jika ada relasi lain yang dihilangkan (misalnya ke unit kerja atau jabatan), konfirmasi kebutuhannya

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (app()->environment('testing')) {
                $model->setConnection('sqlite');
                $model->setTable('simpeg_vd_data_identitas_pegawai');
            }
        });
    }
}