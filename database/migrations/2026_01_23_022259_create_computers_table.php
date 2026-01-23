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
            $table->string('os_info')->nullable();
            $table->string("os_license_status")->nullable();
            $table->string("os_partial_key")->nullable();
            $table->string("ip_address")->nullable();
            $table->string("location")->nullable();
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
