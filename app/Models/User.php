<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;


class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
   protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'block_name', // Existing
        'plot_name',  // Existing
        'area',       // Existing
        'user_type',  // Existing
        'site_name',  // Existing
        'site_id',    // Existing
        'last_login', // Existing
        'previous_last_login', // Existing
        'last_activity_at', // Existing, from previous logic
        'gps_status',        // NEW
        'is_moving',         // NEW
        'last_known_latitude', // NEW
        'last_known_longitude',// NEW
        'last_gps_update_at',  // NEW
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

      // Role Checking Methods
      public function isAdmin()
      {
          return $this->role === 'admin';
      }
  
      public function isUser()
      {
          return $this->role === 'user';
      }
      
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'last_activity_at' => 'datetime', // Ensure this is cast to datetime
        'previous_last_login' => 'datetime', // Ensure this is cast to datetime
        'last_login' => 'datetime', // Assuming this should also be datetime for consistency
        'gps_status' => 'boolean',     // Cast new column to boolean
        'is_moving' => 'boolean',      // Cast new column to boolean
        'last_gps_update_at' => 'datetime', // Cast new column to datetime
    ];
}
