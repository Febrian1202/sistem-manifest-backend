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
        Schema::table('laboratories', function (Blueprint $table) {
            if (! Schema::hasColumn('laboratories', 'faculty_id')) {
                $table->foreignId('faculty_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('faculties')
                    ->nullOnDelete();
            } else {
                $table->foreign('faculty_id')->references('id')->on('faculties')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('laboratories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('faculty_id');
        });
    }
};
