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
        Schema::create('metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->onDelete('cascade');
            $table->timestamp('recorded_at');

            // System metrics
            $table->integer('uptime')->nullable(); // seconds
            $table->decimal('load_1m', 5, 2)->nullable();
            $table->decimal('load_5m', 5, 2)->nullable();
            $table->decimal('load_15m', 5, 2)->nullable();
            $table->integer('cpu_cores')->nullable();

            // Memory (MB)
            $table->integer('memory_total')->nullable();
            $table->integer('memory_used')->nullable();
            $table->integer('memory_available')->nullable();
            $table->integer('swap_total')->nullable();
            $table->integer('swap_used')->nullable();

            // JSON data for complex structures
            $table->json('disks')->nullable();
            $table->json('services')->nullable();
            $table->json('security')->nullable();

            $table->timestamps();

            $table->index(['server_id', 'recorded_at']);
            $table->index('recorded_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('metrics');
    }
};
