<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataOfficeKoordinat extends Model
{
    use HasFactory;

    protected $connection = 'mysql_absen';
    protected $table = 'vd_ref_instansi_koordinat';
    protected $primaryKey = 'id_ref_instansi_koordinat';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id_ref_instansi_koordinat',
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
        'block_koordinat' => 'string',
        'absen_shift' => 'string',
        'cre_on' => 'datetime',
        'upd_on' => 'datetime',
        'aktif' => 'integer',
    ];

    public function instansi()
    {
        return $this->belongsTo(DataOfficeAbsen::class, 'id_instansi', 'id_instansi');
    }

    // Catatan: Relasi instansi dipertahankan karena diperlukan untuk mengaitkan koordinat dengan kantor
    // Jika ada relasi lain yang dihilangkan (misalnya ke tabel lain), konfirmasi kebutuhannya

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (app()->environment('testing')) {
                $model->setConnection('sqlite');
                $model->setTable('absen_vd_ref_instansi_koordinat');
            }
        });
    }
}