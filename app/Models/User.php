<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\DataPegawaiAbsen;
use App\Models\FaceModel;
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',  
        'email_verified_at',
        'remember_token',
        'created_at',
        'updated_at',
        'is_admin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the number of models to return for pagination.
     *
     * @return int
     */
    public function getPerPage()
    {
        return 10;
    }

    /**
     * Define the relationship with the DataPegawai model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function dataPegawaiAbsen()
    {
        return $this->hasOne(DataPegawaiAbsen::class, 'nip', 'username'); // Assuming 'nip' in DataPegawaiAbsen matches 'username' in User
    }
    public function faceModel()
    {
        return $this->hasMany(FaceModel::class, 'user_id', 'id'); // 1 user can have many models - 1 model belongs to 1 user
    }
}
