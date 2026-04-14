<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Clean existing duplicates safely
        // We keep the latest row (highest id) for each [computer_id, raw_name, version] group.
        // We handle NULL version by treating it as an empty string for the grouping logic.
        $duplicates = DB::table('software_discoveries')
            ->select('computer_id', 'raw_name', DB::raw('COALESCE(version, "") as version'), DB::raw('MAX(id) as max_id'))
            ->groupBy('computer_id', 'raw_name', DB::raw('COALESCE(version, "")'))
            ->having(DB::raw('COUNT(*)'), '>', 1)
            ->get();

        foreach ($duplicates as $duplicate) {
            DB::table('software_discoveries')
                ->where('computer_id', $duplicate->computer_id)
                ->where('raw_name', $duplicate->raw_name)
                // Use whereRaw to handle NULL vs empty string match correctly in the cleanup
                ->where(function($query) use ($duplicate) {
                    if ($duplicate->version === "") {
                        $query->whereNull('version')->orWhere('version', '');
                    } else {
                        $query->where('version', $duplicate->version);
                    }
                })
                ->where('id', '<', $duplicate->max_id)
                ->delete();
        }

        // 2. Add the composite unique index
        Schema::table('software_discoveries', function (Blueprint $table) {
            $table->unique(['computer_id', 'raw_name', 'version'], 'uq_discovery_computer_software');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('software_discoveries', function (Blueprint $table) {
            $table->dropUnique('uq_discovery_computer_software');
        });
    }
};
