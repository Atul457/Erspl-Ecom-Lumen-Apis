<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FavShop extends Model
{
    protected $table = "fav_shop";

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
