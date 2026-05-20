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
        Schema::table('compliance_reports', function (Blueprint $table) {
            $table->dropColumn([
                'total_software_installed',
                'unlicensed_count',
                'blacklisted_count',
                'violation_details',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('compliance_reports', function (Blueprint $table) {
            // Kolom-kolom di bawah ini ditambahkan kembali jika rollback diperlukan.
            // Kolom ini merupakan sisa dari arsitektur lama (per-komputer)
            // sebelum digantikan oleh arsitektur per-software per-komputer.
            $table->integer('total_software_installed')->default(0);
            $table->integer('unlicensed_count')->default(0);
            $table->integer('blacklisted_count')->default(0);
            $table->json('violation_details')->nullable();
        });
    }
};
