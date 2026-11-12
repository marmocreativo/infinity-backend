<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reentrante: MySQL no revierte DDL, así que si una ejecución falla a la mitad
 * cada paso se omite si ya se aplicó, y lo que quedó incompleto se repara
 * (motor InnoDB y llaves foráneas).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('orders', 'requires_invoice')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->boolean('requires_invoice')->default(false);
            });
        }

        // Nuevo estado: el mensajero llegó al punto de recolección (idempotente)
        DB::statement("ALTER TABLE orders MODIFY status ENUM('pending','assigned','en_route_pickup','arrived_pickup','picked_up','en_route_delivery','delivered','cancelled','returned') NOT NULL DEFAULT 'pending'");

        // Paradas adicionales de recolección / entrega (la principal vive en orders)
        if (! Schema::hasTable('order_stops')) {
            Schema::create('order_stops', function (Blueprint $table) {
                $table->engine('InnoDB');
                $table->id();
                $table->unsignedBigInteger('order_id');
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
        }

        // Las llaves foráneas exigen InnoDB (una ejecución previa la dejó en MyISAM)
        DB::statement('ALTER TABLE order_stops ENGINE=InnoDB');

        if (! Schema::hasColumn('order_evidences', 'stop_id')) {
            Schema::table('order_evidences', function (Blueprint $table) {
                $table->unsignedBigInteger('stop_id')->nullable()->after('order_id');
            });
        }

        if (! $this->hasForeign('order_stops', 'order_id')) {
            // Evita que una llave falle por filas huérfanas
            DB::statement('DELETE FROM order_stops WHERE order_id NOT IN (SELECT id FROM orders)');

            Schema::table('order_stops', function (Blueprint $table) {
                $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            });
        }

        if (! $this->hasForeign('order_evidences', 'stop_id')) {
            DB::statement('UPDATE order_evidences SET stop_id = NULL WHERE stop_id IS NOT NULL AND stop_id NOT IN (SELECT id FROM order_stops)');

            Schema::table('order_evidences', function (Blueprint $table) {
                $table->foreign('stop_id')->references('id')->on('order_stops')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if ($this->hasForeign('order_evidences', 'stop_id')) {
            Schema::table('order_evidences', function (Blueprint $table) {
                $table->dropForeign(['stop_id']);
            });
        }

        if (Schema::hasColumn('order_evidences', 'stop_id')) {
            Schema::table('order_evidences', function (Blueprint $table) {
                $table->dropColumn('stop_id');
            });
        }

        Schema::dropIfExists('order_stops');

        DB::statement("UPDATE orders SET status = 'en_route_pickup' WHERE status = 'arrived_pickup'");
        DB::statement("ALTER TABLE orders MODIFY status ENUM('pending','assigned','en_route_pickup','picked_up','en_route_delivery','delivered','cancelled','returned') NOT NULL DEFAULT 'pending'");

        if (Schema::hasColumn('orders', 'requires_invoice')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('requires_invoice');
            });
        }
    }

    private function hasForeign(string $table, string $column): bool
    {
        foreach (Schema::getForeignKeys($table) as $fk) {
            if (in_array($column, $fk['columns'], true)) {
                return true;
            }
        }

        return false;
    }
};
