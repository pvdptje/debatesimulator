<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('debate_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('role');
            $table->string('stance');
            $table->string('color')->default('#3B82F6');
            $table->integer('turn_order')->default(0);
            $table->string('provider');
            $table->string('model');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agents');
    }
};
