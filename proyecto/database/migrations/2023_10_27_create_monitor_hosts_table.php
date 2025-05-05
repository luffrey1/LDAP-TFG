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
        Schema::create('monitor_hosts', function (Blueprint $table) {
            $table->id();
            $table->string('hostname');
            $table->string('ip_address');
            $table->string('mac_address')->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['online', 'offline', 'warning', 'error', 'unknown'])->default('unknown');
            $table->timestamp('last_seen')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('group_id')->nullable();
            $table->float('cpu_usage')->nullable();
            $table->float('memory_usage')->nullable();
            $table->float('disk_usage')->nullable();
            $table->float('temperature')->nullable();
            $table->string('uptime')->nullable();
            $table->json('system_info')->nullable();
            $table->timestamps();
            
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitor_hosts');
    }
}; 