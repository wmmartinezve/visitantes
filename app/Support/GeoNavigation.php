<?php

declare(strict_types=1);

namespace App\Support;

final class GeoNavigation
{
    public static function mapsQueryUrl(float $lat, float $lng): string
    {
        return 'https://www.google.com/maps?q='.urlencode("{$lat},{$lng}");
    }

    public static function directionsUrl(float $fromLat, float $fromLng, float $toLat, float $toLng): string
    {
        return 'https://www.google.com/maps/dir/?api=1'
            .'&origin='.urlencode("{$fromLat},{$fromLng}")
            .'&destination='.urlencode("{$toLat},{$toLng}");
    }
}
