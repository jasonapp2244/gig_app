<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'role',
        'phone_number',
        'service_provider',
        'service_provider_id',
        'auth_token',
        'otp',
        'otp_status',
        'otp_expires_at',
        'email_verified_at',
        'profile_image',
        'bio',
        'city',
        'state',
        'country',
        'address_one',
        'address_two',
        'latitude',
        'longitude',
        'hasActiveSubscription',
        'payment_status',
        'skills',
        'cv',
        'password_reset_token',
        'password_reset_token_expires_at',
        'last_password_reset_at',
        'fcm_token',
        'status'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'auth_token',
        'otp',
        'password_reset_token'
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'otp_expires_at' => 'datetime',
            'password_reset_token_expires_at' => 'datetime',
            'last_password_reset_at' => 'datetime',
            'password' => 'hashed',
            'skills' => 'array',
            'created_at' => 'datetime:Y-m-d H:i:s',
            'updated_at' => 'datetime:Y-m-d H:i:s',
            'deleted_at' => 'datetime:Y-m-d H:i:s'
        ];
    }

    /**
     * Get the role associated with the user.
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Check if user has a specific role
     */
    public function hasRole($role): bool
    {
        return $this->role === $role ||
              ($this->roleRelation && $this->roleRelation->name === $role);
    }

    /**
     * Get the password reset tokens for the user.
     */
    // public function passwordResetTokens()
    // {
    //     return $this->hasMany(PasswordResetToken::class, 'email', 'email');
    // }
}
