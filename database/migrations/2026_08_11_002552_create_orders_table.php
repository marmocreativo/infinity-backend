<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->restrictOnDelete();
            $table->foreignId('courier_id')->nullable()->constrained('users')->nullOnDelete();

            $table->enum('status', [
                'pending',
                'assigned',
                'en_route_pickup',
                'picked_up',
                'en_route_delivery',
                'delivered',
                'cancelled',
            ])->default('pending');

            $table->enum('assignment_type', ['manual', 'automatic'])->default('manual');

            // Punto de recolección
            $table->string('pickup_address');
            $table->decimal('pickup_lat', 10, 7);
            $table->decimal('pickup_lng', 10, 7);
            $table->string('pickup_contact_name')->nullable();
            $table->string('pickup_contact_phone')->nullable();

            // Punto de entrega
            $table->string('delivery_address');
            $table->decimal('delivery_lat', 10, 7);
            $table->decimal('delivery_lng', 10, 7);
            $table->string('delivery_contact_name')->nullable();
            $table->string('delivery_contact_phone')->nullable();

            $table->decimal('price', 10, 2)->default(0);
            $table->enum('payment_status', ['pending', 'paid', 'failed'])->default('pending');

            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
