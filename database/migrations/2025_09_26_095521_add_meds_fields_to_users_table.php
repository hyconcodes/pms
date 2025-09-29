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
        Schema::table('users', function (Blueprint $table) {
            $table->string('gender', 10)->nullable()->after('date_of_birth');
            $table->string('blood_type', 3)->nullable()->after('gender');
            $table->string('genotype', 10)->nullable()->after('blood_type');
            $table->decimal('height_cm', 5, 1)->nullable()->after('genotype');
            $table->decimal('weight_kg', 5, 1)->nullable()->after('height_cm');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['gender', 'blood_type', 'genotype', 'height_cm', 'weight_kg']);
        });
    }
};
