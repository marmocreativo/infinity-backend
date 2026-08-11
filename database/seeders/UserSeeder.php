<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Clinic;
use App\Models\CourierProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // --- Admin ---
        $admin = User::create([
            'name' => 'Manuel Marmolejo',
            'email' => 'marmocreativo@gmail.com',
            'password' => Hash::make('Angeles1#'),
            'phone' => '5523995604',
            'status' => 'active',
        ]);
        $admin->assignRole('admin');

        // --- Cliente (clínica) ---
        $clienteUser = User::create([
            'name' => 'Dra. Ana Torres',
            'email' => 'cliente@infinityapp.test',
            'password' => Hash::make('password123'),
            'phone' => '5500000002',
            'status' => 'active',
        ]);
        $clienteUser->assignRole('cliente');

        Clinic::create([
            'user_id' => $clienteUser->id,
            'business_name' => 'Clínica Veterinaria San Ángel',
            'tax_id' => 'VET010101ABC',
            'default_address' => 'Av. Revolución 123, CDMX',
            'default_lat' => 19.347000,
            'default_lng' => -99.187000,
        ]);

        // --- Mensajero ---
        $mensajeroUser = User::create([
            'name' => 'Carlos Ramírez',
            'email' => 'mensajero@infinityapp.test',
            'password' => Hash::make('password123'),
            'phone' => '5500000003',
            'status' => 'active',
            'must_change_password' => true, // recibió credenciales del admin
        ]);
        $mensajeroUser->assignRole('mensajero');

        CourierProfile::create([
            'user_id' => $mensajeroUser->id,
            'vehicle_type' => 'motorcycle',
            'is_on_duty' => false,
        ]);
    }
}