<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ACategory extends Model
{
    protected $table = "tbl_acategory";
    public $timestamps = false; // This disables created_at and updated_at columns
}
