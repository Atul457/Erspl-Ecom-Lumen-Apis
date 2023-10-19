<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $table = "tbl_employee";
    public $timestamps = false; // This disables created_at and updated_at columns
}
