<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderCodTransaction extends Model
{
    protected $table = "tbl_order_cod_transaction";
    public $timestamps = false; // This disables created_at and updated_at columns
}
