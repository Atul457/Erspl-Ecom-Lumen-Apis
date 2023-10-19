<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CancelReason extends Model
{
    protected $table = "tbl_cancel_reason";
    public $timestamps = false; // This disables created_at and updated_at columns
}
