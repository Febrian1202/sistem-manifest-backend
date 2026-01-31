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
        Schema::create("computers", function (Blueprint $table) {
            $table->id();
            $table->string("hostname")->unique();

            // --- OS INFO ---
            $table->string('os_name')->nullable();       // Ex: Microsoft Windows 11 Pro
            $table->string('os_version')->nullable();    // Ex: 10.0.22621
            $table->string('os_architecture')->nullable(); // Ex: 64-bit
            $table->string("os_license_status")->nullable(); // Ex: Licensed, Grace Period
            $table->string("os_partial_key")->nullable();

            // --- HARDWARE INFO ---
            $table->string('processor')->nullable();     // Ex: Intel(R) Core(TM) i5...
            $table->integer('ram_gb')->nullable();       // Ex: 16
            $table->integer('disk_total_gb')->nullable(); // Ex: 512
            $table->integer('disk_free_gb')->nullable();  // Ex: 120

            // --- NETWORK & IDENTITY ---
            $table->string("ip_address")->nullable();
            $table->string('mac_address')->nullable();   // Ex: 00:1A:2B:3C:4D:5E
            $table->string('serial_number')->nullable(); // Ex: 5CD2345JKS

            // --- SYSTEM INFO ---
            $table->string('manufacturer')->nullable();  // Ex: HP, Dell
            $table->string('model')->nullable();         // Ex: Latitude 5420

            // --- META ---
            $table->string("location")->nullable();      // Ex: Lab 1
            $table->timestamp("last_seen_at")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("computers");
    }
};