<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('access_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('username');
            $table->string('nombre');
            $table->string('hostname');
            $table->string('ip');
            $table->timestamp('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('access_attempts');
    }
}; 