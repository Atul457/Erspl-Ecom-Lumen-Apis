<?php

namespace App\Helpers;

class UtilityHelper
{
    /**
     * Calculates the difference between the two places using lat long
     * @param $unit This could be m for miles, km for kilometer
     */
    public static function getDistanceBetweenPlaces(
        array $latLongFrom,
        array $latLongTo,
        string $unit = "k"
    ) {

        if ($unit === "m")
            $earthRadius = 3959;
        else
            $earthRadius = 6371;

        $latTo = $latLongTo["lat"] ?? 0;
        $longTo = $latLongTo["long"] ?? 0;
        $latFrom = $latLongFrom["lat"] ?? 0;
        $longFrom = $latLongFrom["long"] ?? 0;

        // Convert degrees to radians
        $latFrom = deg2rad($latFrom);
        $longFrom = deg2rad($longFrom);
        $latTo = deg2rad($latTo);
        $longTo = deg2rad($longTo);

        // Calculate differences
        $deltaLat = $latTo - $latFrom;
        $deltaLon = $longTo - $longFrom;

        // Calculate distance using Haversine formula
        $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
            cos($latFrom) * cos($latTo) *
            sin($deltaLon / 2) * sin($deltaLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;

        return $distance;
    }
}
