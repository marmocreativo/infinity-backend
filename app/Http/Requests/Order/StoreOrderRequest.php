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
            'pickup_contact_name' => ['nullable', 'string'],
            'pickup_contact_phone' => ['nullable', 'string'],

            'delivery_address' => ['required', 'string'],
            'delivery_lat' => ServiceArea::latRules(),
            'delivery_lng' => ServiceArea::lngRules(),
            'delivery_contact_name' => ['nullable', 'string'],
            'delivery_contact_phone' => ['nullable', 'string'],

            'notes' => ['nullable', 'string'],
            'is_urgent' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'pickup_lat.between' => 'La ubicación de recolección debe estar dentro de México.',
            'pickup_lng.between' => 'La ubicación de recolección debe estar dentro de México.',
            'delivery_lat.between' => 'La ubicación de entrega debe estar dentro de México.',
            'delivery_lng.between' => 'La ubicación de entrega debe estar dentro de México.',
        ];
    }
}