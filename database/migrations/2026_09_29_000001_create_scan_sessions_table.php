<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scan_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('computer_id')->nullable()->constrained('computers')->nullOnDelete();
            $table->uuid('scan_uuid')->unique();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('status')->default('completed'); // pending, running, completed, failed, partial
            $table->string('trigger')->default('scheduled'); // scheduled, manual, on_demand
            $table->unsignedInteger('software_count')->default(0);
            $table->text('error_message')->nullable();
            $table->string('agent_version')->nullable();
            $table->timestamps();

            $table->index(['computer_id', 'status']);
            $table->index('started_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scan_sessions');
    }
};
