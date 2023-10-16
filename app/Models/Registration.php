<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Auth\Authorizable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Registration extends Model implements AuthenticatableContract, AuthorizableContract, JWTSubject
{
    use Authenticatable, Authorizable, HasFactory;

    protected $table = "tbl_registration";

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
        'referral_status',
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


    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [
            'id' => $this->id,
            'dob' => $this->dob,
            'image' => $this->image,
            'email' => $this->email,
            'gender' => $this->gender,
            'mobile' => $this->mobile,
            'status' => $this->status,
            'reg_type' => $this->reg_type,
            'last_name' => $this->last_name,
            'alt_mobile' => $this->alt_mobile,
            'first_name' => $this->first_name,
            'referral_by' => $this->referral_by,
            'middle_name' => $this->middle_name,
            'email_status' => $this->email_status,
        ];
    }
}
