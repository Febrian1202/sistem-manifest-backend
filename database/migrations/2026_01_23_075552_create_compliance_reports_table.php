<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('compliance_reports', function (Blueprint $table) {
            $table->id();

            // Laporan ini milik komputer mana
            $table->foreignId('computer_id')->constrained('computers')->onDelete('cascade');

            // Status Kepatuhan
            // Safe = Aman, Warning = Ada freeware aneh, Critical = Ada bajakan/game
            $table->enum('status', ['Safe', 'Warning', 'Critical'])->default('Safe');

            // Statistik Singkat
            $table->integer('total_software_installed')->default(0);
            $table->integer('unlicensed_count')->default(0); // Jumlah software ilegal ditemukan
            $table->integer('blacklisted_count')->default(0); // Jumlah software terlarang ditemukan

            // Simpan JSON detail pelanggaran (agar history tidak hilang)
            // Contoh isi: {["GTA V", "Crack Adobe", "Office Tanpa Lisensi"]}
            $table->json('violation_details')->nullable();

            $table->timestamp('scanned_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compliance_reports');
    }
};
