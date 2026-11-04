<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class RouteQuoteService
{
    /** Ruta simple recolección → entrega */
    public function quote(float $pickupLat, float $pickupLng, float $deliveryLat, float $deliveryLng): array
    {
        return $this->quotePoints([[$pickupLat, $pickupLng], [$deliveryLat, $deliveryLng]]);
    }

    /**
     * Ruta completa de un pedido: recolección → paradas de recolección →
     * entrega → paradas de entrega.
     *
     * @param  array<int, array{type: string, lat: mixed, lng: mixed}>  $stops
     */
    public function quoteWithStops(float $pickupLat, float $pickupLng, float $deliveryLat, float $deliveryLng, array $stops = []): array
    {
        $stops = array_values($stops);
        $pickupStops = array_filter($stops, fn ($s) => $s['type'] === 'pickup');
        $deliveryStops = array_filter($stops, fn ($s) => $s['type'] === 'delivery');

        $points = [[$pickupLat, $pickupLng]];
        foreach ($pickupStops as $s) {
            $points[] = [$s['lat'], $s['lng']];
        }
        $points[] = [$deliveryLat, $deliveryLng];
        foreach ($deliveryStops as $s) {
            $points[] = [$s['lat'], $s['lng']];
        }

        return $this->quotePoints($points);
    }

    /**
     * Ruta que pasa por todos los puntos en orden.
     *
     * @param  array<int, array{0: float, 1: float}>  $points  [[lat, lng], ...]
     */
    public function quotePoints(array $points): array
    {
        $points = array_map(fn ($p) => [(float) $p[0], (float) $p[1]], array_values($points));

        $key = 'route_quote:' . md5(implode(';', array_map(
            fn ($p) => round($p[0], 5) . ',' . round($p[1], 5),
            $points
        )));

        if ($cached = Cache::get($key)) {
            return $cached;
        }

        $route = $this->fetchRoute($points);

        if ($route) {
            Cache::put($key, $route, now()->addMinutes(30));

            return $route;
        }

        return $this->fallbackRoute($points);
    }

    private function fetchRoute(array $points): ?array
    {
        try {
            $coords = implode(';', array_map(fn ($p) => sprintf('%F,%F', $p[1], $p[0]), $points));

            $res = Http::timeout(6)->acceptJson()->get(
                "https://router.project-osrm.org/route/v1/driving/{$coords}",
                ['overview' => 'full', 'geometries' => 'geojson']
            );

            if (! $res->successful() || $res->json('code') !== 'Ok') {
                return null;
            }

            $r = $res->json('routes.0');
            $line = $r['geometry']['coordinates'] ?? [];

            return [
                'distance_km' => round($r['distance'] / 1000, 2),
                'duration_min' => round($r['duration'] / 60, 1),
                // OSRM devuelve [lng, lat]; Leaflet usa [lat, lng]
                'route' => array_map(fn ($c) => [$c[1], $c[0]], $line),
                'estimated' => false,
            ];
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    private function fallbackRoute(array $points): array
    {
        $km = 0.0;

        for ($i = 1; $i < count($points); $i++) {
            $km += $this->haversineKm($points[$i - 1][0], $points[$i - 1][1], $points[$i][0], $points[$i][1]);
        }

        $km = round($km * config('pricing.fallback_route_factor'), 2);

        return [
            'distance_km' => $km,
            'duration_min' => round($km / 25 * 60, 1), // ~25 km/h urbano
            'route' => $points,
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
