<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('orders', 'sample_type')) {
            Schema::table('orders', function (Blueprint $table) {
                // Laminillas, Líquido, Sobre o el texto libre cuando eligen "Otro"
                $table->string('sample_type', 120)->nullable();
            });
        }

        if (! Schema::hasTable('red_zones')) {
            Schema::create('red_zones', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->json('polygon'); // [[lat, lng], ...]
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        Schema::table('clinics', function (Blueprint $table) {
            if (! Schema::hasColumn('clinics', 'trust_red_zone')) {
                $table->boolean('trust_red_zone')->default(false);
            }
            if (! Schema::hasColumn('clinics', 'credit_cycle_days')) {
                // Días máximos que un pedido a crédito puede estar sin pagar (7 o 15)
                $table->unsignedTinyInteger('credit_cycle_days')->default(15);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('red_zones');

        Schema::table('clinics', function (Blueprint $table) {
            foreach (['trust_red_zone', 'credit_cycle_days'] as $col) {
                if (Schema::hasColumn('clinics', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        if (Schema::hasColumn('orders', 'sample_type')) {
            Schema::table('orders', fn (Blueprint $t) => $t->dropColumn('sample_type'));
        }
    }
};
