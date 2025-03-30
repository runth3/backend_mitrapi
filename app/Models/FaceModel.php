<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FaceModel extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'image_path',
        'is_active',
    ];

    /**
     * Relationship: A face model belongs to a user.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
