<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAbsen extends Model
{
    use HasFactory;

    protected $connection = 'mysql_absen'; // Use the absen database connection
    protected $table = 'vd_staff'; // Replace with the actual table name

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

    // Hide sensitive fields from API responses
    protected $hidden = [
        'pwd',        // Password
        'auth_token', // Authentication token 
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
}