<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UomType extends Model
{
    protected $table = "tbl_uom_type";
    public $timestamps = false; // This disables created_at and updated_at columns
}
