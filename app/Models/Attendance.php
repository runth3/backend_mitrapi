<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $connection = 'mysql_absen'; // Use the absen database connection
    protected $table = 'vd_data_checkinout'; // Replace with the actual table name
    protected $primaryKey = 'id_checkinout'; // Assuming 'id_checkinout' is the primary key
    public $incrementing = false; // Set to true if the primary key is auto-incrementing
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id_checkinout',
        'nip_pegawai',
        'id_instansi',
        'id_unit_kerja',
        'checktime',
        'checktype',
        'iplog',
        'coordinate',
        'date',
        'id_profile',
        'jenis_absensi',
        'user_platform',
        'browser_name',
        'browser_version',
        'browser_agent',
        'aprv_by',
        'aprv_on',
        'aprv_stats',
        'reject_by',
        'reject_on',
        'altitude',
    ];

    /**
     * The attributes that should be hidden for arrays and JSON responses.
     *
     * @var array
     */
    protected $hidden = [
        'iplog',          // Hide IP address 
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'checktime' => 'datetime', // Casts checktime to a Carbon datetime object
        'date' => 'date',          // Casts date to a Carbon date object
        'aprv_on' => 'datetime',   // Casts approval timestamp to a Carbon datetime object
        'reject_on' => 'datetime', // Casts rejection timestamp to a Carbon datetime object
    ];

    /**
     * Example relationship: An attendance record belongs to a user.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'nip_pegawai', 'username'); // Assuming 'nip_pegawai' in Attendance corresponds to 'username' in User
    }
}
