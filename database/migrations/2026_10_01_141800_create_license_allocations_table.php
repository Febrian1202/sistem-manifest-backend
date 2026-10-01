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
        Schema::create('license_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('license_inventory_id')
                ->constrained('license_inventories')
                ->restrictOnDelete()
                ->comment('Lisensi induk milik universitas');

            $table->foreignId('faculty_id')
                ->constrained('faculties')
                ->restrictOnDelete()
                ->comment('Fakultas penerima alokasi');

            $table->unsignedInteger('allocated_quota')->default(1)->comment('Jumlah kursi/lisensi yang dialokasikan');
            $table->date('allocation_date')->comment('Tanggal penetapan alokasi');
            $table->date('start_date')->nullable()->comment('Awal masa berlaku alokasi');
            $table->date('end_date')->nullable()->comment('Akhir masa berlaku alokasi');
            $table->string('status', 30)->default('active')->comment('active, inactive, revoked');
            $table->text('notes')->nullable()->comment('Catatan administrasi alokasi');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Indexes untuk optimasi query agregasi
            $table->index(['license_inventory_id', 'status']);
            $table->index(['faculty_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('license_allocations');
    }
};
