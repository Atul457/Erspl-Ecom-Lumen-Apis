<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSearchLog extends Model
{
    protected $table = "tbl_user_search_logs";
    public $timestamps = false; // This disables created_at and updated_at columns
}
