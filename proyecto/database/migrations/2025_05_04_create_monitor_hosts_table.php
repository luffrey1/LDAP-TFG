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
            $table->string('hostname')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('mac_address')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('status')->default('unknown');
            $table->timestamp('last_seen')->nullable();
            $table->timestamp('last_boot')->nullable();
            $table->json('system_info')->nullable();
            $table->json('disk_usage')->nullable();
            $table->json('memory_usage')->nullable();
            $table->json('cpu_usage')->nullable();
            $table->string('uptime')->nullable();
            $table->json('users')->nullable();
            $table->unsignedBigInteger('group_id')->nullable();
            $table->timestamps();
            
            $table->index(['ip_address', 'status']);
            $table->index(['group_id']);
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