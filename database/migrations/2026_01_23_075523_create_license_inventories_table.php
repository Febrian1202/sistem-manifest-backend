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
        Schema::create('license_inventories', function (Blueprint $table) {
            $table->id();

            // Relasi ke Katalog
            $table->foreignId('catalog_id')->constrained('software_catalogs')->onDelete('cascade');

            // Detail pembelian
            $table->string('purchase_order_number')->nullable(); // Nomor Faktur/SPK
            $table->integer('quota_limit')->default(1); // Jumlah yang dibeli (misal 50 user);
            $table->date('purchase_date')->nullable();
            $table->date('expiry_date')->nullable();

            // Harga (Opsional) 
            $table->decimal('price_per_unit', 15, 2)->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('license_inventories');
    }
};
