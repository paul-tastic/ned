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
        Schema::create('thresholds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('server_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('metric'); // e.g., 'cpu_load', 'memory_percent', 'disk_percent'
            $table->decimal('warning_value', 10, 2);
            $table->decimal('critical_value', 10, 2);
            $table->enum('comparison', ['>', '<', '>=', '<=', '=='])->default('>');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Unique constraint: one threshold per metric per server (or global if server_id is null)
            $table->unique(['user_id', 'server_id', 'metric']);
            $table->index(['user_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('thresholds');
    }
};
