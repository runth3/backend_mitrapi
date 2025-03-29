<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserEkinerja extends Model
{
    use HasFactory;

    protected $connection = 'mysql_ekin'; // Use the ekinerja database connection
    protected $table = 'kinerja_user'; // Replace with the actual table name

    protected $fillable = [
        'UID',
        'title',
        'nama',
        'gelar',
        'NIP',
        'sts',
        'last_login',
        'uType',
        'opd_id',
        'opd_unit_id',
        'opd_unit_sub_id',
        'apvId',
        'isOps',
        'opd',
        'jabatan_id',
        'pangkat_id',
        'simpeg_id',
        'avatar',
        'b_day',
        'tunjangan',
        'isOpsTp',
        'last_updated',
    ];

    // Hide sensitive fields from API responses
    protected $hidden = [
        'apv',
        'password', // Password
    ];
      // Relationship with DataOfficeEkinerja
      public function officeEkinerja()
      {
          return $this->belongsTo(DataOfficeEkinerja::class, 'opd_id', 'id');
      }
}