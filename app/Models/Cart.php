<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    protected $table = "tbl_cart";
    public $timestamps = false; // This disables created_at and updated_at columns

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'shop_id',
        'product_id',
        'weight',
        'qty',
        'offer_type',
    ];
}
