<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AddressBook extends Model
{
    protected $table = 'address_book';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'city',
        'flat',
        'state',
        'mobile',
        'address',
        'pincode',
        'country',
        'latitude',
        'landmark',
        'longitude',
        'customer_id',
        'address_type',
        'default_status',
    ];
}
