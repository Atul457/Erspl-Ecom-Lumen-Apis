<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WrongReg extends Model
{
    protected $table = "tbl_wrong_reg";
    public $timestamps = false; // This disables created_at and updated_at columns
}
