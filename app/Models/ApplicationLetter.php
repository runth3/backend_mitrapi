<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicationLetter extends Model
{
    protected $connection = 'mysql_absen';
    protected $table = 'vd_data_permohonan';
    protected $primaryKey = 'id_data_permohonan';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id_data_permohonan',
        'nip_pegawai',
        'jenis_permohonan',
        'id_instansi',
        'id_unit_kerja',
        'tgl_mulai',
        'tgl_selesai',
        'deskripsi',
        'nama_file',
        'cre_on',
        'cre_by',
        'aprv_on',
        'aprv_by',
        'upd_on',
        'upd_by',
        'reject_on',
        'reject_by',
        'status',
        'alasan'
    ];

    protected $casts = [
        'tgl_mulai' => 'date',
        'tgl_selesai' => 'date',
        'cre_on' => 'datetime',
        'aprv_on' => 'datetime',
        'upd_on' => 'datetime',
        'reject_on' => 'datetime'
    ];
}