<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compliance_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scan_session_id')->constrained('scan_sessions')->cascadeOnDelete();
            $table->foreignId('computer_id')->nullable()->constrained('computers')->nullOnDelete();
            $table->foreignId('software_catalog_id')->nullable()->constrained('software_catalogs')->nullOnDelete();
            $table->string('software_name');
            $table->string('software_version')->nullable();
            $table->string('status'); // Berlisensi, Tidak Berlisensi, Grace Period, Perlu Ditinjau
            $table->text('keterangan')->nullable();
            $table->foreignId('license_inventory_id')->nullable()->constrained('license_inventories')->nullOnDelete();
            $table->timestamp('detected_at')->nullable();
            $table->timestamp('scanned_at');
            $table->timestamps();

            $table->index('scan_session_id');
            $table->index(['computer_id', 'status']);
            $table->index('scanned_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compliance_snapshots');
    }
};
