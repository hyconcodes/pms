<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->enum('payment_method', ['cash', 'card', 'bank_transfer', 'other'])->nullable();
            $table->decimal('payment_amount', 10, 2)->nullable();
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'payment_amount', 'payment_status']);
        });
    }
};
