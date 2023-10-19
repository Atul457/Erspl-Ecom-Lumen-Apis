<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Home extends Model
{
    protected $table = 'tbl_home';
    public $timestamps = false; // This disables created_at and updated_at columns
}
