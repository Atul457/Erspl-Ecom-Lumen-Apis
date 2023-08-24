<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Auth\Authorizable;

class User extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable, HasFactory;

     /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'dob',
        'otp',
        'image',
        'email',
        'gender',
        'mobile',
        'status',
        'attempt',
        'password',
        'reg_type',
        'last_name',
        'first_name',
        'alt_mobile',
        'referral_by',
        'middle_name',
        'email_status',
        'guest_status',
        'otp_datetime',
        'referral_code',
        'wallet_balance',
        'email_verified_at',
        'suspended_datetime',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'remember_token', "password"
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function permission():HasOne{
        return $this->HasOne(UserPermission::class);
    }
    
}
