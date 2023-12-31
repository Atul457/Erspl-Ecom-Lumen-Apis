<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegistrationTemp extends Model
{
    use HasFactory;

    protected $table = "tbl_registration_temp";
    public $timestamps = false; // This disables created_at and updated_at columns

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
        'referral_status',
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
        'remember_token'
    ];
}
