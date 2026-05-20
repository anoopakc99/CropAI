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
        Schema::create('soil_moistures', function (Blueprint $table) {
            $table->id();
            $table->string('scene_id')->unique();
            $table->string('view_id')->nullable();
            $table->string('site_id')->nullable();
            $table->date('date');
            $table->float('q1')->nullable();
            $table->float('q3')->nullable();
            $table->float('max')->nullable();
            $table->float('min')->nullable();
            $table->float('p10')->nullable();
            $table->float('p90')->nullable();
            $table->float('std')->nullable();
            $table->float('median')->nullable();
            $table->float('average')->nullable();
            $table->float('variance')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('soil_moistures');
    }
};
