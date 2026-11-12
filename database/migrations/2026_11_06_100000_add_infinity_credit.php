<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('clinics', 'credit_enabled')) {
            Schema::table('clinics', function (Blueprint $table) {
                $table->boolean('credit_enabled')->default(false);
            });
        }

        if (! Schema::hasColumn('orders', 'payment_method')) {
            Schema::table('orders', function (Blueprint $table) {
                // card | oxxo | cash | credit
                $table->string('payment_method', 20)->nullable();
            });
        }

        if (! Schema::hasTable('credit_statements')) {
            Schema::create('credit_statements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
                $table->char('month', 7); // Y-m
                $table->decimal('total', 10, 2);
                $table->timestamp('paid_at');
                $table->string('note')->nullable();
                $table->foreignId('registered_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['clinic_id', 'month']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_statements');

        if (Schema::hasColumn('orders', 'payment_method')) {
            Schema::table('orders', fn (Blueprint $t) => $t->dropColumn('payment_method'));
        }
        if (Schema::hasColumn('clinics', 'credit_enabled')) {
            Schema::table('clinics', fn (Blueprint $t) => $t->dropColumn('credit_enabled'));
        }
    }
};
