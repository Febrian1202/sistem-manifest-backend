<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scan_software_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scan_session_id')->constrained('scan_sessions')->cascadeOnDelete();
            $table->foreignId('catalog_id')->nullable()->constrained('software_catalogs')->nullOnDelete();
            $table->string('raw_name');
            $table->string('version')->nullable();
            $table->string('vendor')->nullable();
            $table->date('install_date')->nullable();
            $table->timestamps();

            $table->index('scan_session_id');
            $table->index('catalog_id');
            $table->index('raw_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scan_software_results');
    }
};
