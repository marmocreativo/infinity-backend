<?php

namespace App\Http\Requests\Order;

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
            'pickup_lat' => ['required', 'numeric'],
            'pickup_lng' => ['required', 'numeric'],
            'pickup_contact_name' => ['nullable', 'string'],
            'pickup_contact_phone' => ['nullable', 'string'],

            'delivery_address' => ['required', 'string'],
            'delivery_lat' => ['required', 'numeric'],
            'delivery_lng' => ['required', 'numeric'],
            'delivery_contact_name' => ['nullable', 'string'],
            'delivery_contact_phone' => ['nullable', 'string'],

            'notes' => ['nullable', 'string'],
        ];
    }
}