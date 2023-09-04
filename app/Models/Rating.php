<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    protected $table = "rating"; 

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'shop_id',
        'user_id',
        'order_id',
        'delivery_boy_id',
        'delivery_boy_rating',
        'rating',
        'review',
        'delivery_boy_review',
        'date',
    ];

}
