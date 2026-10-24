<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class RouteQuoteService
{
    public function quote(float $pickupLat, float $pickupLng, float $deliveryLat, float $deliveryLng): array
    {
        $key = 'route_quote:' . md5(implode(',', array_map(
            fn ($v) => round($v, 5),
            [$pickupLat, $pickupLng, $deliveryLat, $deliveryLng]
        )));

        if ($cached = Cache::get($key)) {
            return $cached;
        }

        $route = $this->fetchRoute($pickupLat, $pickupLng, $deliveryLat, $deliveryLng);

        if ($route) {
            Cache::put($key, $route, now()->addMinutes(30));

            return $route;
        }

        return $this->fallbackRoute($pickupLat, $pickupLng, $deliveryLat, $deliveryLng);
    }

    private function fetchRoute(float $pLat, float $pLng, float $dLat, float $dLng): ?array
    {
        try {
            $url = sprintf(
                'https://router.project-osrm.org/route/v1/driving/%F,%F;%F,%F',
                $pLng, $pLat, $dLng, $dLat
            );

            $res = Http::timeout(6)->acceptJson()->get($url, [
                'overview' => 'full',
                'geometries' => 'geojson',
            ]);

            if (! $res->successful() || $res->json('code') !== 'Ok') {
                return null;
            }

            $r = $res->json('routes.0');
            $coords = $r['geometry']['coordinates'] ?? [];

            return [
                'distance_km' => round($r['distance'] / 1000, 2),
                'duration_min' => round($r['duration'] / 60, 1),
                // OSRM devuelve [lng, lat]; Leaflet usa [lat, lng]
                'route' => array_map(fn ($c) => [$c[1], $c[0]], $coords),
                'estimated' => false,
            ];
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    private function fallbackRoute(float $pLat, float $pLng, float $dLat, float $dLng): array
    {
        $km = round($this->haversineKm($pLat, $pLng, $dLat, $dLng) * config('pricing.fallback_route_factor'), 2);

        return [
            'distance_km' => $km,
            'duration_min' => round($km / 25 * 60, 1), // ~25 km/h urbano
            'route' => [[$pLat, $pLng], [$dLat, $dLng]],
            'estimated' => true,
        ];
    }

    private function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 6371 * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}