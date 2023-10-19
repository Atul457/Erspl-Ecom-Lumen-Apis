<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FavShop extends Model
{
    protected $table = "tbl_fav_shop";
    public $timestamps = false; // This disables created_at and updated_at columns

     /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'shop_id',
        'user_id',
    ];
}
