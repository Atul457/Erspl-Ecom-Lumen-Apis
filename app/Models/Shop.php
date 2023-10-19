<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    protected $table = "tbl_shop";
    public $timestamps = false; // This disables created_at and updated_at columns
}
