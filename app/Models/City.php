<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    protected $table = "tbl_city";
    public $timestamps = false; // This disables created_at and updated_at columns
}
