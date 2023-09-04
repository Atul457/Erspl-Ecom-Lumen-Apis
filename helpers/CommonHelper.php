<?php

namespace App\Helpers;

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
}
