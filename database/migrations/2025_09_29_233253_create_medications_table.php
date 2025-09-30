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
        Schema::create('medications', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();            // e.g. Lisinopril 10mg
            $table->string('status')->nullable();          // e.g. In Stock
            $table->integer('stock_level')->nullable();    // e.g. 150 units
            $table->date('expiry')->nullable();            // e.g. Dec 2025
            $table->string('supplier')->nullable();        // e.g. PharmaCorp
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medications');
    }
};
