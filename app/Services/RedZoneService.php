<?php

namespace App\Services;

use App\Models\Clinic;
use App\Models\RedZone;

/** Zonas rojas: polígonos donde no se da servicio de recolección */
class RedZoneService
{
    /** Zona activa que contiene el punto, o null */
    public function zoneContaining(float $lat, float $lng): ?RedZone
    {
        foreach (RedZone::where('is_active', true)->get() as $zone) {
            if ($this->inside($lat, $lng, $zone->polygon ?? [])) {
                return $zone;
            }
        }

        return null;
    }

    /** Ray casting. El polígono es una lista de [lat, lng]. */
    public function inside(float $lat, float $lng, array $polygon): bool
    {
        $n = count($polygon);
        if ($n < 3) {
            return false;
        }

        $inside = false;

        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            [$latI, $lngI] = [(float) $polygon[$i][0], (float) $polygon[$i][1]];
            [$latJ, $lngJ] = [(float) $polygon[$j][0], (float) $polygon[$j][1]];

            $crosses = (($latI > $lat) !== ($latJ > $lat))
                && ($lng < ($lngJ - $lngI) * ($lat - $latI) / (($latJ - $latI) ?: 1e-12) + $lngI);

            if ($crosses) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }

    /** ¿La clínica puede operar en estos puntos? (las de confianza ignoran las zonas rojas) */
    public function blockedMessage(Clinic $clinic, array $pickupPoints): ?string
    {
        if ($clinic->trust_red_zone) {
            return null;
        }

        foreach ($pickupPoints as [$lat, $lng]) {
            if ($lat !== null && $lng !== null && $this->zoneContaining((float) $lat, (float) $lng)) {
                return 'El punto de recolección está dentro de una zona roja, donde por ahora no damos servicio.';
            }
        }

        return null;
    }

    /** Mensaje si la ubicación guardada de la clínica cae en zona roja */
    public function clinicBlockedMessage(Clinic $clinic): ?string
    {
        if ($clinic->trust_red_zone || $clinic->default_lat === null || $clinic->default_lng === null) {
            return null;
        }

        return $this->zoneContaining((float) $clinic->default_lat, (float) $clinic->default_lng)
            ? 'La ubicación de tu clínica está dentro de una zona roja, donde por ahora no damos servicio.'
            : null;
    }
}
