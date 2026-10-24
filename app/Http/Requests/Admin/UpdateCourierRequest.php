<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCourierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $courierId = $this->route('courier')->id;

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'unique:users,email,' . $courierId],
            'phone' => ['nullable', 'string', 'max:20'],
            'vehicle_type' => ['sometimes', 'in:motorcycle,car,bike'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Ese correo ya está en uso por otra cuenta.',
        ];
    }
}