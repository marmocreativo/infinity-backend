<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('orders', 'scheduled_for')) {
            Schema::table('orders', function (Blueprint $table) {
                // Día en que se atenderá el pedido (los creados después de la hora límite van al día siguiente)
                $table->date('scheduled_for')->nullable()->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('orders', 'scheduled_for')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('scheduled_for');
            });
        }
    }
};
