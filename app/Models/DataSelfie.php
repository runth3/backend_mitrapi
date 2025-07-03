<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataSelfie extends Model
{
    protected $table = 'vd_data_selfie';
    protected $primaryKey = 'id_data_selfie';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'id_data_selfie',
        'nip',
        'nama_file',
        'tgl_selfie',
        'checktype',
        'jenis_absensi'
    ];

    protected $casts = [
        'tgl_selfie' => 'datetime',
        'jenis_absensi' => 'integer'
    ];
}