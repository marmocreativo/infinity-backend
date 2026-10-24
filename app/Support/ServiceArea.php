<?php

namespace App\Support;

class ServiceArea
{
    public const LAT_MIN = 14.3;
    public const LAT_MAX = 32.9;
    public const LNG_MIN = -118.7;
    public const LNG_MAX = -86.4;

    public static function latRules(): array
    {
        return ['required', 'numeric', 'between:' . self::LAT_MIN . ',' . self::LAT_MAX];
    }

    public static function lngRules(): array
    {
        return ['required', 'numeric', 'between:' . self::LNG_MIN . ',' . self::LNG_MAX];
    }
}