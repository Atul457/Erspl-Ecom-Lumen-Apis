<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Industry extends Model
{
    protected $table = "tbl_industries";
    public $timestamps = false; // This disables created_at and updated_at columns
}
