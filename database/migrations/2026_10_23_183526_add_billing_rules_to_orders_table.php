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
            $table->decimal('distance_km', 8, 2)->default(0);
            $table->boolean('is_urgent')->default(false);
            $table->decimal('urgent_surcharge', 10, 2)->default(0);
            $table->timestamp('urgent_deadline_at')->nullable();
            $table->decimal('quoted_price', 10, 2)->default(0);
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->decimal('refunded_amount', 10, 2)->default(0);
            $table->decimal('cancellation_fee', 10, 2)->default(0);
            $table->decimal('return_surcharge', 10, 2)->default(0);
            $table->text('return_reason')->nullable();
        });

        DB::statement("ALTER TABLE orders MODIFY status ENUM('pending','assigned','en_route_pickup','picked_up','en_route_delivery','delivered','cancelled','returned') NOT NULL DEFAULT 'pending'");
        DB::statement("ALTER TABLE payments MODIFY status ENUM('pending','paid','failed','refunded','waived') NOT NULL DEFAULT 'pending'");
        DB::statement("ALTER TABLE order_evidences MODIFY type ENUM('pickup','delivery','return') NOT NULL");

        // Pedidos existentes: el precio original y lo pagado se toman del estado actual
        DB::statement('UPDATE orders SET quoted_price = price');
        DB::statement("UPDATE orders SET amount_paid = price WHERE payment_status = 'paid'");
    }

    public function down(): void
    {
        DB::statement("UPDATE orders SET status = 'cancelled' WHERE status = 'returned'");
        DB::statement("DELETE FROM order_evidences WHERE type = 'return'");
        DB::statement("UPDATE payments SET status = 'refunded' WHERE status = 'waived'");

        DB::statement("ALTER TABLE orders MODIFY status ENUM('pending','assigned','en_route_pickup','picked_up','en_route_delivery','delivered','cancelled') NOT NULL DEFAULT 'pending'");
        DB::statement("ALTER TABLE payments MODIFY status ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending'");
        DB::statement("ALTER TABLE order_evidences MODIFY type ENUM('pickup','delivery') NOT NULL");

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'distance_km', 'is_urgent', 'urgent_surcharge', 'urgent_deadline_at',
                'quoted_price', 'amount_paid', 'refunded_amount',
                'cancellation_fee', 'return_surcharge', 'return_reason',
            ]);
        });
    }
};