<?php

namespace Database\Seeders;

use App\Models\Clinic;
use App\Models\CourierLocation;
use App\Models\CourierProfile;
use App\Models\CourierShift;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    private const PASSWORD = 'password123';

    private array $clinics = [];
    private array $couriers = [];
    private ?int $adminId = null;
    private int $ordersCreated = 0;

    /** Clínicas (puntos de recolección) */
    private array $clinicDefs = [
        'san_angel' => [
            'user_name' => 'Dra. Ana Torres',
            'email' => 'cliente@infinityapp.test',
            'phone' => '5500000002',
            'business' => 'Clínica Veterinaria San Ángel',
            'tax_id' => 'VET010101ABC',
            'address' => 'Av. Revolución 123, San Ángel, CDMX',
            'lat' => 19.347000,
            'lng' => -99.187000,
            'contact' => 'Recepción Clínica',
        ],
        'del_valle' => [
            'user_name' => 'Dr. Ricardo Salinas',
            'email' => 'ricardo.salinas@infinityapp.test',
            'phone' => '5511223344',
            'business' => 'Veterinaria Del Valle',
            'tax_id' => 'VDV050505XY1',
            'address' => 'Av. Coyoacán 1250, Del Valle Centro, CDMX',
            'lat' => 19.387000,
            'lng' => -99.165000,
            'contact' => 'Recepción Del Valle',
        ],
        'polanco' => [
            'user_name' => 'Dra. Paola Esquivel',
            'email' => 'paola.esquivel@infinityapp.test',
            'phone' => '5522334455',
            'business' => 'Hospital Veterinario Polanco',
            'tax_id' => 'HVP080808AB2',
            'address' => 'Av. Presidente Masaryk 340, Polanco, CDMX',
            'lat' => 19.434000,
            'lng' => -99.196000,
            'contact' => 'Dra. Esquivel',
        ],
        'roma' => [
            'user_name' => 'Dr. Emilio Castañeda',
            'email' => 'emilio.castaneda@infinityapp.test',
            'phone' => '5533445566',
            'business' => 'Clínica Pet Roma',
            'tax_id' => 'CPR120912CD3',
            'address' => 'Calle Durango 220, Roma Norte, CDMX',
            'lat' => 19.419500,
            'lng' => -99.162000,
            'contact' => 'Recepción Pet Roma',
        ],
        'coyoacan' => [
            'user_name' => 'Dra. Fernanda Lozano',
            'email' => 'fernanda.lozano@infinityapp.test',
            'phone' => '5544556677',
            'business' => 'Veterinaria Coyoacán',
            'tax_id' => 'VCO150315EF4',
            'address' => 'Calle Hidalgo 45, Coyoacán Centro, CDMX',
            'lat' => 19.346700,
            'lng' => -99.161700,
            'contact' => 'Recepción Coyoacán',
        ],
        'satelite' => [
            'user_name' => 'Dr. Héctor Vidal',
            'email' => 'hector.vidal@infinityapp.test',
            'phone' => '5555667788',
            'business' => 'Animal Care Satélite',
            'tax_id' => 'ACS180620GH5',
            'address' => 'Circuito Economistas 12, Ciudad Satélite, Naucalpan',
            'lat' => 19.509000,
            'lng' => -99.234000,
            'contact' => 'Dr. Vidal',
        ],
    ];

    /** Laboratorios (puntos de entrega) */
    private array $labs = [
        'central' => [
            'address' => 'Laboratorio Central, Av. Insurgentes 456, CDMX',
            'lat' => 19.360000,
            'lng' => -99.170000,
            'contact' => 'Recepción Laboratorio',
            'phone' => '5500000099',
        ],
        'unam' => [
            'address' => 'Laboratorio de Diagnóstico FMVZ, Circuito Exterior s/n, Ciudad Universitaria, CDMX',
            'lat' => 19.333500,
            'lng' => -99.181500,
            'contact' => 'Mesa de muestras',
            'phone' => '5556220001',
        ],
        'narvarte' => [
            'address' => 'Vet-Lab Narvarte, Av. Universidad 780, Narvarte, CDMX',
            'lat' => 19.395000,
            'lng' => -99.155000,
            'contact' => 'Recepción Vet-Lab',
            'phone' => '5556220002',
        ],
        'condesa' => [
            'address' => 'Centro de Patología Veterinaria, Av. Michoacán 45, Condesa, CDMX',
            'lat' => 19.411000,
            'lng' => -99.175000,
            'contact' => 'Dra. Montes',
            'phone' => '5556220003',
        ],
        'lindavista' => [
            'address' => 'Laboratorio Biomédico Lindavista, Calz. de los Misterios 600, CDMX',
            'lat' => 19.490000,
            'lng' => -99.130000,
            'contact' => 'Recepción Biomédico',
            'phone' => '5556220004',
        ],
    ];

    /** Mensajeros. 'current' = ubicación actual si está en turno */
    private array $courierDefs = [
        'carlos' => [
            'name' => 'Carlos Ramírez',
            'email' => 'mensajero@infinityapp.test',
            'phone' => '5500000003',
            'vehicle' => 'motorcycle',
            'on_duty' => false,
            'current' => null,
        ],
        'luis' => [
            'name' => 'Luis Hernández',
            'email' => 'luis.hernandez@infinityapp.test',
            'phone' => '5511002201',
            'vehicle' => 'motorcycle',
            'on_duty' => true,
            'current' => [19.4010, -99.1650],
        ],
        'maria' => [
            'name' => 'María González',
            'email' => 'maria.gonzalez@infinityapp.test',
            'phone' => '5511002202',
            'vehicle' => 'bike',
            'on_duty' => true,
            'current' => [19.4180, -99.1700],
        ],
        'jorge' => [
            'name' => 'Jorge Sánchez',
            'email' => 'jorge.sanchez@infinityapp.test',
            'phone' => '5511002203',
            'vehicle' => 'car',
            'on_duty' => true,
            'current' => [19.3700, -99.1800],
        ],
        'sofia' => [
            'name' => 'Sofía Martínez',
            'email' => 'sofia.martinez@infinityapp.test',
            'phone' => '5511002204',
            'vehicle' => 'motorcycle',
            'on_duty' => false,
            'current' => null,
        ],
    ];

    private array $sampleNotes = [
        'Muestra de sangre, mantener refrigerada.',
        'Biopsias en frasco. Manejar con cuidado.',
        'Entregar con receta adjunta.',
        'Urgente: resultados para hoy.',
        'Dos tubos de ensayo en hielera.',
    ];

    public function run(): void
    {
        $this->call(RoleSeeder::class);

        if (User::where('email', 'luis.hernandez@infinityapp.test')->exists()) {
            $this->command->warn('Los datos demo ya están cargados. No se agregó nada.');

            return;
        }

        mt_srand(2026); // datos reproducibles
        $this->adminId = User::role('admin')->value('id');

        DB::transaction(function () {
            $this->seedClinics();
            $this->seedCouriers();
            $this->seedShifts();
            $this->seedOrders();
        });

        $this->command->info("Datos demo creados: {$this->ordersCreated} pedidos.");
        $this->command->info('Contraseña de las cuentas demo: ' . self::PASSWORD);
    }

    private function seedClinics(): void
    {
        foreach ($this->clinicDefs as $key => $def) {
            $user = User::firstOrCreate(
                ['email' => $def['email']],
                [
                    'name' => $def['user_name'],
                    'password' => Hash::make(self::PASSWORD),
                    'phone' => $def['phone'],
                    'status' => 'active',
                ]
            );

            if ($user->wasRecentlyCreated) {
                $user->assignRole('cliente');
            }

            $clinic = Clinic::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'business_name' => $def['business'],
                    'tax_id' => $def['tax_id'],
                    'default_address' => $def['address'],
                    'default_lat' => $def['lat'],
                    'default_lng' => $def['lng'],
                ]
            );

            $this->clinics[$key] = array_merge($def, [
                'user_id' => $user->id,
                'clinic_id' => $clinic->id,
            ]);
        }
    }

    private function seedCouriers(): void
    {
        foreach ($this->courierDefs as $key => $def) {
            $user = User::firstOrCreate(
                ['email' => $def['email']],
                [
                    'name' => $def['name'],
                    'password' => Hash::make(self::PASSWORD),
                    'phone' => $def['phone'],
                    'status' => 'active',
                    'must_change_password' => false,
                ]
            );

            if ($user->wasRecentlyCreated) {
                $user->assignRole('mensajero');
            }

            CourierProfile::firstOrCreate(
                ['user_id' => $user->id],
                ['vehicle_type' => $def['vehicle'], 'is_on_duty' => $def['on_duty']]
            );

            $this->couriers[$key] = [
                'id' => $user->id,
                'on_duty' => $def['on_duty'],
                'current' => $def['current'],
            ];
        }
    }

    private function seedShifts(): void
    {
        foreach ($this->couriers as $courier) {
            // Turnos pasados (cerrados), con algunos puntos de ubicación
            for ($d = 1; $d <= 12; $d++) {
                if (mt_rand(1, 100) > 65) {
                    continue;
                }

                $start = now()->subDays($d)->setTime(8, mt_rand(0, 45));
                $end = $start->copy()->addHours(mt_rand(7, 9));

                $shift = CourierShift::create([
                    'courier_id' => $courier['id'],
                    'started_at' => $start,
                    'ended_at' => $end,
                ]);

                for ($i = 0; $i < 4; $i++) {
                    CourierLocation::create([
                        'courier_id' => $courier['id'],
                        'shift_id' => $shift->id,
                        'lat' => $this->randFloat(19.34, 19.45),
                        'lng' => $this->randFloat(-99.21, -99.13),
                        'recorded_at' => $start->copy()->addMinutes($i * 90 + 30),
                    ]);
                }
            }

            // Turno actual abierto, con recorrido reciente hasta su ubicación actual
            if ($courier['on_duty'] && $courier['current']) {
                $shift = CourierShift::create([
                    'courier_id' => $courier['id'],
                    'started_at' => now()->subHours(3),
                    'ended_at' => null,
                ]);

                [$curLat, $curLng] = $courier['current'];
                $startLat = $curLat + 0.025;
                $startLng = $curLng - 0.02;

                for ($i = 0; $i < 8; $i++) {
                    $t = $i / 7;
                    $jitter = $i < 7 ? $this->randFloat(-0.0004, 0.0004) : 0;

                    CourierLocation::create([
                        'courier_id' => $courier['id'],
                        'shift_id' => $shift->id,
                        'lat' => $startLat + ($curLat - $startLat) * $t + $jitter,
                        'lng' => $startLng + ($curLng - $startLng) * $t + $jitter,
                        'recorded_at' => now()->subMinutes((7 - $i) * 5),
                    ]);
                }
            }
        }
    }

    private function seedOrders(): void
    {
        // [clínica, laboratorio, mensajero, estado, hace cuántos minutos se creó, notas]
        $scenarios = [
            // --- Sin asignar (para ver el resaltado) ---
            ['san_angel', 'narvarte', null, 'pending', 7, 'Entregar con receta adjunta.'],
            ['del_valle', 'unam', null, 'pending', 12, 'Muestra de sangre, mantener refrigerada.'],
            ['polanco', 'condesa', null, 'pending', 25, null],
            ['roma', 'lindavista', null, 'pending', 48, 'Biopsias en frasco. Manejar con cuidado.'],
            ['satelite', 'central', null, 'pending', 95, null],

            // --- En curso ---
            ['san_angel', 'unam', 'carlos', 'assigned', 35, null],
            ['roma', 'central', 'luis', 'assigned', 40, null],
            ['del_valle', 'condesa', 'maria', 'en_route_pickup', 55, 'Urgente: resultados para hoy.'],
            ['polanco', 'narvarte', 'jorge', 'picked_up', 70, null],
            ['coyoacan', 'lindavista', 'luis', 'en_route_delivery', 85, null],
            ['san_angel', 'condesa', 'maria', 'en_route_delivery', 100, 'Dos tubos de ensayo en hielera.'],
        ];

        foreach ($scenarios as [$clinic, $lab, $courier, $status, $minutesAgo, $notes]) {
            $this->buildOrder($clinic, $lab, $courier, $status, now()->subMinutes($minutesAgo), $notes);
        }

        // --- Historial: entregados y algunos cancelados en los últimos 30 días ---
        $clinicPool = [
            'san_angel', 'san_angel', 'san_angel',
            'del_valle', 'del_valle', 'polanco',
            'roma', 'roma', 'coyoacan', 'satelite',
        ];
        $labKeys = array_keys($this->labs);
        $courierKeys = array_keys($this->couriers);
        $cancelledAt = [7, 15, 22];

        for ($i = 0; $i < 31; $i++) {
            $isCancelled = in_array($i, $cancelledAt, true);
            $createdAt = now()->subDays(mt_rand(1, 30))->setTime(mt_rand(8, 17), mt_rand(0, 59));

            $this->buildOrder(
                $clinicPool[array_rand($clinicPool)],
                $labKeys[array_rand($labKeys)],
                $isCancelled ? null : $courierKeys[array_rand($courierKeys)],
                $isCancelled ? 'cancelled' : 'delivered',
                $createdAt,
                mt_rand(1, 100) <= 30 ? $this->sampleNotes[array_rand($this->sampleNotes)] : null
            );
        }
    }

    private function buildOrder(
        string $clinicKey,
        string $labKey,
        ?string $courierKey,
        string $status,
        $createdAt,
        ?string $notes = null
    ): void {
        $clinic = $this->clinics[$clinicKey];
        $lab = $this->labs[$labKey];
        $courier = $courierKey ? $this->couriers[$courierKey] : null;

        $km = $this->haversineKm($clinic['lat'], $clinic['lng'], $lab['lat'], $lab['lng']) * 1.3;
        $price = $this->price($km);

        $automatic = $courier && $status !== 'cancelled' && mt_rand(1, 100) <= 30;
        $paid = ! in_array($status, ['pending', 'cancelled'], true);

        // Línea de tiempo de eventos
        $chain = ['created', 'assigned', 'en_route_pickup', 'picked_up', 'en_route_delivery', 'delivered'];
        $statusIndex = [
            'pending' => 0,
            'assigned' => 1,
            'en_route_pickup' => 2,
            'picked_up' => 3,
            'en_route_delivery' => 4,
            'delivered' => 5,
        ];

        $types = $status === 'cancelled'
            ? ['created', 'cancelled']
            : array_slice($chain, 0, $statusIndex[$status] + 1);

        $stepMinutes = [
            'created' => 0,
            'assigned' => mt_rand(4, 15),
            'en_route_pickup' => mt_rand(3, 8),
            'picked_up' => mt_rand(10, 25),
            'en_route_delivery' => mt_rand(2, 6),
            'delivered' => mt_rand(15, 35),
            'cancelled' => mt_rand(10, 40),
        ];

        // Para pedidos recientes, comprime los pasos para que no queden en el futuro
        $totalSeconds = array_sum(array_map(fn ($t) => $stepMinutes[$t], $types)) * 60;
        $available = max(60, now()->timestamp - $createdAt->timestamp - 120);
        $scale = $totalSeconds > $available ? $available / $totalSeconds : 1;

        $eventTimes = [];
        $cursor = $createdAt->copy();
        foreach ($types as $type) {
            $cursor = $cursor->copy()->addSeconds((int) ($stepMinutes[$type] * 60 * $scale));
            $eventTimes[$type] = $cursor;
        }
        $lastEventAt = end($eventTimes);

        $order = new Order([
            'clinic_id' => $clinic['clinic_id'],
            'courier_id' => $courier['id'] ?? null,
            'status' => $status,
            'assignment_type' => $automatic ? 'automatic' : 'manual',
            'pickup_address' => $clinic['address'],
            'pickup_lat' => $clinic['lat'],
            'pickup_lng' => $clinic['lng'],
            'pickup_contact_name' => $clinic['contact'],
            'pickup_contact_phone' => $clinic['phone'],
            'delivery_address' => $lab['address'],
            'delivery_lat' => $lab['lat'],
            'delivery_lng' => $lab['lng'],
            'delivery_contact_name' => $lab['contact'],
            'delivery_contact_phone' => $lab['phone'],
            'price' => $price,
            'distance_km' => round($km, 2),
            'quoted_price' => $price,
            'amount_paid' => $paid ? $price : 0,
            'payment_status' => $paid ? 'paid' : 'pending',
            'notes' => $notes,
        ]);
        $order->created_at = $createdAt;
        $order->updated_at = $lastEventAt;
        $order->save();

        foreach ($types as $type) {
            $createdBy = match ($type) {
                'created', 'cancelled' => $clinic['user_id'],
                'assigned' => $automatic ? null : $this->adminId,
                default => $courier['id'] ?? null,
            };

            $event = new OrderEvent([
                'order_id' => $order->id,
                'event_type' => $type,
                'created_by' => $createdBy,
            ]);
            $event->created_at = $eventTimes[$type];
            $event->updated_at = $eventTimes[$type];
            $event->save();
        }

        $payment = new Payment([
            'order_id' => $order->id,
            'amount' => $price,
            'gateway' => 'stripe',
            'gateway_reference' => $paid ? 'pi_demo_' . Str::lower(Str::random(14)) : null,
            'status' => $paid ? 'paid' : 'pending',
            'paid_at' => $paid ? $createdAt->copy()->addMinutes(2) : null,
        ]);
        $payment->created_at = $createdAt;
        $payment->updated_at = $createdAt;
        $payment->save();

        $this->ordersCreated++;
    }

    private function price(float $km): float
    {
        $price = config('pricing.base_fee', 80) + config('pricing.per_km', 12) * $km;

        return (float) ceil(max(config('pricing.minimum', 100), $price));
    }

    private function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 6371 * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function randFloat(float $min, float $max): float
    {
        return $min + ($max - $min) * (mt_rand() / mt_getrandmax());
    }
}