<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataOfficeAbsen extends Model
{
    use HasFactory;

    protected $connection = 'mysql_absen'; // Use the absen database connection
    protected $table = 'vd_ref_instansi'; // Replace with the actual table name

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
}
