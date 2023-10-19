<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderDeliveryLogs extends Model
{
    protected $table = "tbl_order_delivery_logs";
    public $timestamps = false; // This disables created_at and updated_at columns
}
