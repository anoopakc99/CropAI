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
        Schema::create('pre_land_preparation', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plot_id')->constrained()->onDelete('cascade');
            $table->foreignId('tractor_id')->nullable()->constrained('machines')->onDelete('set null');
            $table->decimal('fuel_consumption', 8, 2)->nullable();
            $table->decimal('tractor_running_hours', 8, 2)->nullable();
            $table->foreignId('machine_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('machine_running_hours', 8, 2)->nullable();
            $table->string('leveling')->nullable();
            $table->decimal('area_worked', 10, 2)->nullable();
            $table->date('pre_sowing_irrigation_date')->nullable();
            $table->enum('land_quality', ['excellent', 'good', 'average', 'poor'])->nullable();
            $table->integer('manpower_count')->nullable();
            $table->decimal('time_duration', 8, 2)->nullable(); // in hours
            $table->text('major_maintenance')->nullable();
            $table->integer('maintenance_manpower')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pre_land_preparation');
    }
};
