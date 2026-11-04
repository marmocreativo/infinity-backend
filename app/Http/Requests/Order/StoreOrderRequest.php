<?php

namespace App\Http\Requests\Order;

use App\Support\ServiceArea;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pickup_address' => ['required', 'string'],
            'pickup_lat' => ServiceArea::latRules(),
            'pickup_lng' => ServiceArea::lngRules(),
            'pickup_contact_name' => ['required', 'string', 'max:255'],
            'pickup_contact_phone' => ['required', 'string', 'max:30'],

            'delivery_address' => ['required', 'string'],
            'delivery_lat' => ServiceArea::latRules(),
            'delivery_lng' => ServiceArea::lngRules(),
            'delivery_contact_name' => ['required', 'string', 'max:255'],
            'delivery_contact_phone' => ['required', 'string', 'max:30'],

            'notes' => ['nullable', 'string'],
            'is_urgent' => ['nullable', 'boolean'],
            'requires_invoice' => ['nullable', 'boolean'],

            // Paradas adicionales (además de la recolección y entrega principales)
            'stops' => ['nullable', 'array', 'max:' . self::MAX_STOPS],
            'stops.*.type' => ['required', 'in:pickup,delivery'],
            'stops.*.address' => ['required', 'string'],
            'stops.*.lat' => ServiceArea::latRules(),
            'stops.*.lng' => ServiceArea::lngRules(),
            'stops.*.contact_name' => ['required', 'string', 'max:255'],
            'stops.*.contact_phone' => ['required', 'string', 'max:30'],
        ];
    }

    public const MAX_STOPS = 6;

    public function messages(): array
    {
        return [
            'pickup_lat.between' => 'La ubicación de recolección debe estar dentro de México.',
            'pickup_lng.between' => 'La ubicación de recolección debe estar dentro de México.',
            'delivery_lat.between' => 'La ubicación de entrega debe estar dentro de México.',
            'delivery_lng.between' => 'La ubicación de entrega debe estar dentro de México.',
            'pickup_contact_name.required' => 'El nombre de contacto en recolección es obligatorio.',
            'pickup_contact_phone.required' => 'El teléfono de contacto en recolección es obligatorio.',
            'delivery_contact_name.required' => 'El nombre de contacto en entrega es obligatorio.',
            'delivery_contact_phone.required' => 'El teléfono de contacto en entrega es obligatorio.',
            'stops.*.contact_name.required' => 'Cada parada necesita un nombre de contacto.',
            'stops.*.contact_phone.required' => 'Cada parada necesita un teléfono de contacto.',
            'stops.*.lat.between' => 'Todas las paradas deben estar dentro de México.',
            'stops.*.lng.between' => 'Todas las paradas deben estar dentro de México.',
        ];
    }
}
