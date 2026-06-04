<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('software_catalogs', function (Blueprint $table) {
            $table->string('category')->change(); // Temporary change to string to allow enum update
        });

        // Use raw SQL to redefine enum with new values
        // Note: This is specific to MySQL.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE software_catalogs MODIFY COLUMN category ENUM('Freeware', 'Commercial', 'OpenSource', 'Shareware', 'Other') DEFAULT 'Freeware'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('software_catalogs', function (Blueprint $table) {
            $table->string('category')->change();
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE software_catalogs MODIFY COLUMN category ENUM('Freeware', 'Commercial', 'OpenSource') DEFAULT 'Freeware'");
        }
    }
};
