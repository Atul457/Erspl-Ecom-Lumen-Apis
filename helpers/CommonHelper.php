<?php

namespace App\Helpers;

use App\Models\Shop;
use App\Models\UomType;


// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
/**
 * @todo Document this
 */
class CommonHelper
{

    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public static function uomName(int $unitId)
    {
        $sqlCategoryData = UomType::where("id", $unitId)->first()->toArray();
        return $sqlCategoryData['name'] ?? "";
    }

    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public static function shopName(int $shopId)
    {
        $shop = Shop::where("id", $shopId)->first();

        if ($shop)
            $shop = $shop->toArray();
        else
            throw ExceptionHelper::somethingWentWrong([
                "Shop with id:" . $shopId . " not found"
            ]);

        return $shop['name'] ?? "";
    }
    
    
    
    // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
    /**
     * @todo Document this
     */
    public static function shopDeliveryTime(int $shopId)
    {
        $shop = Shop::where("id", $shopId)->first();

        if ($shop)
            $shop = $shop->toArray();
        else
            throw ExceptionHelper::somethingWentWrong([
                "Shop with id:" . $shopId . " not found"
            ]);

        return $shop['delivery_time'] ?? "";
    }
}
