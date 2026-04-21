<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Truncate existing data because the schema change is fundamental 
        // (from per-computer to per-software reports)
        DB::table('compliance_reports')->truncate();

        Schema::table('compliance_reports', function (Blueprint $table) {
            // New foreign key to software catalogs
            $table->foreignId('software_catalog_id')->after('computer_id')->constrained('software_catalogs')->onDelete('cascade');

            // Snapshot data
            $table->string('software_name')->after('software_catalog_id');
            $table->string('software_version')->nullable()->after('software_name');

            // Modify status to string (from enum)
            $table->string('status')->change();

            // Additional details
            $table->string('keterangan')->after('status');
            $table->foreignId('license_inventory_id')->nullable()->after('keterangan')->constrained('license_inventories')->onDelete('set null');
            $table->timestamp('detected_at')->after('license_inventory_id');

            // Unique constraint: one record per software per computer
            $table->unique(['computer_id', 'software_catalog_id'], 'computer_software_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('compliance_reports', function (Blueprint $table) {
            $table->dropUnique('computer_software_unique');
            $table->dropForeign(['software_catalog_id']);
            $table->dropForeign(['license_inventory_id']);

            $table->dropColumn([
                'software_catalog_id',
                'software_name',
                'software_version',
                'keterangan',
                'license_inventory_id',
                'detected_at'
            ]);

            // Revert status to enum
            $table->enum('status', ['Safe', 'Warning', 'Critical'])->default('Safe')->change();
        });
    }
};
