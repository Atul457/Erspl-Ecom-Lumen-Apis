<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $table = "tbl_coupon";
    public $timestamps = false; // This disables created_at and updated_at columns
}
