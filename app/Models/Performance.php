<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Performance extends Model
{
    use HasFactory;

    /**
     * The database connection used by the model.
     *
     * @var string
     */
    protected $connection = 'mysql_ekin'; // Use the ekinerja database connection

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'kinerja'; // Table name

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id'; // Assuming 'id' is the primary key

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'nama',
        'penjelasan',
        'tglInput',
        'tglKinerja',
        'menitKinerja',
        'apv',
        'apvId',
        'apvNama',
        'tglApv',
        'apvReject',
        'stsDel',
        'NIP',
        'durasiKinerja',
        'tupoksi',
        'durasiKinerjaMulai',
        'durasiKinerjaSelesai',
        'periodeKinerja',
        'target',
        'satuanTarget',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'tglInput' => 'datetime',
        'tglKinerja' => 'datetime',
        'tglApv' => 'datetime',
        'menitKinerja' => 'float',
        'durasiKinerja' => 'string', // Assuming duration is stored as a string (e.g., "9:0")
        'target' => 'integer',
    ];
}
