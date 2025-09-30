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
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('appointment_id')->nullable(); // FK to medical_records table
            $table->unsignedBigInteger('medication_id')->nullable(); // FK to medications table
            $table->string('quantity')->nullable();      // e.g., 30 tablets
            $table->text('instructions')->nullable();    // e.g., Take once daily with food
            $table->date('prescribed_date')->nullable();
            $table->timestamps();
            
            $table->foreign('appointment_id')->references('id')->on('medical_records')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prescriptions');
    }
};
