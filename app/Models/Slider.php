<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Slider extends Model
{
    protected $table = "tbl_slider";
    public $timestamps = false; // This disables created_at and updated_at columns
}
