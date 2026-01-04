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
        Schema::create('banned_ip_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->string('ip_address', 45); // IPv6 max length
            $table->enum('event_type', ['ban', 'unban']);
            $table->string('jail')->nullable(); // fail2ban jail name (sshd, etc)
            $table->string('country_code', 2)->nullable();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->string('isp')->nullable();
            $table->timestamp('event_at');
            $table->timestamps();

            // Indexes for common queries
            $table->index(['server_id', 'ip_address']);
            $table->index(['server_id', 'event_at']);
            $table->index('event_at'); // For pruning old records
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('banned_ip_events');
    }
};
