<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataOfficeKoordinat extends Model
{
    use HasFactory;

    protected $connection = 'mysql_absen'; // Use the absen database connection
    protected $table = 'vd_ref_instansi_koordinat'; // Replace with the actual table name
     protected $primaryKey = 'id_ref_instansi_koordinat';
    public $incrementing = false; // Karena primary key adalah varchar, bukan auto-increment
    protected $keyType = 'string'; // Tipe primary key adalah string

    protected $fillable = [
        'id_instansi',
        'koordinat',
        'block_koordinat',
        'jarak_koordinat',
        'wajib_absen',
        'absen_shift',
        'cre_on',
        'cre_by',
        'upd_on',
        'upd_by',
        'aktif',
    ];

    protected $casts = [
        'block_koordinat' => 'string', // Enum Y/N
        'absen_shift' => 'string', // Enum Y/N
        'cre_on' => 'datetime',
        'upd_on' => 'datetime',
        'aktif' => 'integer', // 0 = Aktif, 1 = Tidak Aktif
    ];

    // Relasi ke instansi (opsional, kalau ada tabel instansi)
    public function instansi()
    {
        return $this->belongsTo(DataOfficeAbsen::class, 'id_instansi', 'id_instansi');
    }
}
