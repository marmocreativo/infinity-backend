<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('requires_invoice')->default(false);
        });

        // Nuevo estado: el mensajero llegó al punto de recolección
        DB::statement("ALTER TABLE orders MODIFY status ENUM('pending','assigned','en_route_pickup','arrived_pickup','picked_up','en_route_delivery','delivered','cancelled','returned') NOT NULL DEFAULT 'pending'");

        // Paradas adicionales de recolección / entrega (la principal vive en orders)
        Schema::create('order_stops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['pickup', 'delivery']);
            $table->unsignedSmallInteger('sequence')->default(1);
            $table->string('address');
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->string('contact_name');
            $table->string('contact_phone');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'type', 'sequence']);
        });

        Schema::table('order_evidences', function (Blueprint $table) {
            $table->foreignId('stop_id')->nullable()->after('order_id')->constrained('order_stops')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('order_evidences', function (Blueprint $table) {
            $table->dropConstrainedForeignId('stop_id');
        });

        Schema::dropIfExists('order_stops');

        DB::statement("UPDATE orders SET status = 'en_route_pickup' WHERE status = 'arrived_pickup'");
        DB::statement("ALTER TABLE orders MODIFY status ENUM('pending','assigned','en_route_pickup','picked_up','en_route_delivery','delivered','cancelled','returned') NOT NULL DEFAULT 'pending'");

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('requires_invoice');
        });
    }
};
