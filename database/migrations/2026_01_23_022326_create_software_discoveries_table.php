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
        Schema::create("software_discoveries", function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId("computer_id")
                ->constrained("computers")
                ->onDelete("cascade");
            $table->string("raw_name");
            $table->string("version")->nullable();
            $table->string("vendor")->nullable();
            $table->date("install_date")->nullable();

            $table
                ->foreignId("catalog_id")
                ->nullable()
                ->constrained("software_catalogs")
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("software_discoveries");
    }
};
