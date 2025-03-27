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
    protected $table = 'attendance'; // If your table name is 'attendances', change this to 'attendances'

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'nip', // Assuming you have a 'nip' column (employee ID/username)
        'date',
        'time_in',
        'time_out',
        'location_in',
        'location_out',
        'status',
        // Add other columns here if they exist
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'date' => 'date', // Casts the 'date' column to a Carbon date object
        'time_in' => 'datetime', // Casts time_in to datetime object
        'time_out' => 'datetime' // Casts time_out to datetime object
    ];

    // If you have a relationship with a User model, you can define it here
    // Example: An attendance record belongs to a user
    // public function user()
    // {
    //     return $this->belongsTo(User::class, 'nip', 'username'); // Assuming 'nip' in Attendance corresponds to 'username' in User
    // }
}
