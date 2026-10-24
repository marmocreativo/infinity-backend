<?php

namespace App\Http\Requests\Admin;

use App\Support\ServiceArea;
use Illuminate\Foundation\Http\FormRequest;

class UpdateClinicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('clinic')->user_id;

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'unique:users,email,' . $userId],
            'phone' => ['nullable', 'string', 'max:20'],
            'business_name' => ['sometimes', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:50'],
            'default_address' => ['nullable', 'string'],
            'default_lat' => ['nullable', 'numeric', 'between:' . ServiceArea::LAT_MIN . ',' . ServiceArea::LAT_MAX],
            'default_lng' => ['nullable', 'numeric', 'between:' . ServiceArea::LNG_MIN . ',' . ServiceArea::LNG_MAX],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Ese correo ya está en uso por otra cuenta.',
            'default_lat.between' => 'La ubicación de la clínica debe estar dentro de México.',
            'default_lng.between' => 'La ubicación de la clínica debe estar dentro de México.',
        ];
    }
}