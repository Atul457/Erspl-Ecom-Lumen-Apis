<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderEdited extends Model
{
    protected $table = "tbl_order_edited";
    public $timestamps = false; // This disables created_at and updated_at columns
}
