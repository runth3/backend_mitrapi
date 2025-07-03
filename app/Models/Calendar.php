<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Calendar extends Model
{
    protected $connection = 'mysql_absen';
    protected $table = 'vd_data_kalender';
    protected $primaryKey = 'id_data_kalender';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id_data_kalender',
        'id_instansi',
        'tgl_mulai',
        'tgl_selesai',
        'jam_mulai',
        'jam_selesai',
        'judul',
        'color',
        'status',
        'cre_on',
        'cre_by',
        'upd_on',
        'upd_by'
    ];

    protected $casts = [
        'tgl_mulai' => 'date',
        'tgl_selesai' => 'date',
        'cre_on' => 'datetime',
        'upd_on' => 'datetime'
    ];

    public function calendarInstansi()
    {
        return $this->hasMany(CalendarInstansi::class, 'id_data_kalender', 'id_data_kalender');
    }
}