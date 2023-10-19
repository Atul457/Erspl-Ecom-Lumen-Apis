<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SellerLeger extends Model
{
    protected $table = "tbl_seller_ledger";
    public $timestamps = false; // This disables created_at and updated_at columns
}
