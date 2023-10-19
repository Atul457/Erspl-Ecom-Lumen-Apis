<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubCategory extends Model
{
    protected $table = "tbl_scategory";
    public $timestamps = false; // This disables created_at and updated_at columns
}
