<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalendarInstansi extends Model
{
    protected $connection = 'mysql_absen';
    protected $table = 'vd_data_kalender_instansi';
    protected $primaryKey = 'id_data_kalender_instansi';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id_data_kalender_instansi',
        'id_instansi',
        'id_data_kalender'
    ];

    public function calendar()
    {
        return $this->belongsTo(Calendar::class, 'id_data_kalender', 'id_data_kalender');
    }
}