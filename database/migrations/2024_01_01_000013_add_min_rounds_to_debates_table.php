<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('debates', function (Blueprint $table) {
            $table->integer('min_rounds')->default(5)->after('current_round');
        });
    }

    public function down(): void
    {
        Schema::table('debates', function (Blueprint $table) {
            $table->dropColumn('min_rounds');
        });
    }
};
