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
        Schema::create("software_catalogs", function (Blueprint $table) {
            $table->id();
            $table->string("normalized_name")->unique();
            $table
                ->enum("category", ["Freeware", "Commercial", "OpenSource"])
                ->default("Freeware");
            $table
                ->enum("status", ["Whitelist", "Blacklist", "Unreviewed"])
                ->default("Unreviewed");
            $table->text("description")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("software_catalogs");
    }
};
