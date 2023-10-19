<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationReceiveLogs extends Model
{
    protected $table = "tbl_notification_receive_logs";
    public $timestamps = false; // This disables created_at and updated_at columns
}
