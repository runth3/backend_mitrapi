<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAbsen extends Model
{
    use HasFactory;

    protected $connection = 'mysql_absen';
    protected $table = 'vd_staff';

    protected $fillable = [
        'id',
        'lvl',
        'name',
        'id_instansi',
        'id_unit_kerja',
        'id_pejabat',
        'phone',
        'email',
        'active',
        'full_akses',
    ];

    protected $hidden = [
        'pwd',
        'auth_token',
        'cre_on',
        'cre_by',
        'upd_on',
        'upd_by',
        'lastlogin',
        'online',
        'tgl_expire',
    ];

    public function officeAbsen()
    {
        return $this->belongsTo(DataOfficeAbsen::class, 'id_instansi', 'id_instansi');
    }

    // Catatan: Relasi officeAbsen diperlukan untuk ProfileController (opsional)
    // Jika ada relasi lain yang dihilangkan, konfirmasi kebutuhannya

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (app()->environment('testing')) {
                $model->setConnection('sqlite');
                $model->setTable('absen_vd_staff');
            }
        });
    }
}