<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaytmPaymentLog extends Model
{
    protected $table = "tbl_paytm_payment_logs";
    public $timestamps = false; // This disables created_at and updated_at columns
}
