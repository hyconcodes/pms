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
        if (Schema::hasColumn('medical_records', 'visit_type')) {
            Schema::table('medical_records', function (Blueprint $table) {
                $table->dropColumn('visit_type');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('medical_records', 'visit_type')) {
            Schema::table('medical_records', function (Blueprint $table) {
                $table->enum('visit_type', ['in-person', 'virtual'])->default('in-person')->after('status');
            });
        }
    }
};
