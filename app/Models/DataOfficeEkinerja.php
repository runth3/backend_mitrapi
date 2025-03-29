<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataOfficeEkinerja extends Model
{
    use HasFactory;

    protected $connection = 'mysql_ekin'; // Use the ekinerja database connection
    protected $table = 'opd'; // Replace with the actual table name

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
}
