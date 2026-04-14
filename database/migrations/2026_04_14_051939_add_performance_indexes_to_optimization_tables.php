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
        Schema::table('software_discoveries', function (Blueprint $table) {
            $table->index('catalog_id');
            $table->index('computer_id');
        });

        Schema::table('license_inventories', function (Blueprint $table) {
            $table->index('catalog_id');
        });

        Schema::table('compliance_reports', function (Blueprint $table) {
            $table->index(['computer_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('compliance_reports', function (Blueprint $table) {
            $table->dropIndex(['computer_id', 'created_at']);
        });

        Schema::table('license_inventories', function (Blueprint $table) {
            $table->dropIndex(['catalog_id']);
        });

        Schema::table('software_discoveries', function (Blueprint $table) {
            $table->dropIndex(['computer_id']);
            $table->dropIndex(['catalog_id']);
        });
    }
};
