<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = "tbl_order";
    public $timestamps = false; // This disables created_at and updated_at columns
}
