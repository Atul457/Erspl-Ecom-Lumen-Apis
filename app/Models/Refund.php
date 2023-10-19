<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Refund extends Model
{
    protected $table = "tbl_refund";
    public $timestamps = false; // This disables created_at and updated_at columns
}
