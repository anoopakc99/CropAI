<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ndvi_data', function (Blueprint $table) {
            // Add missing columns from EOS API response
            $table->string('scene_id')->nullable()->after('plot_id');
            $table->string('view_id')->nullable()->after('scene_id');
            $table->unsignedBigInteger('site_id')->nullable()->after('view_id');

            // NDVI statistical values (EOS returns these at root level)
            $table->double('average', 10, 8)->nullable()->after('date')->comment('Average NDVI value');
            $table->double('min', 10, 8)->nullable()->after('average');
            $table->double('max', 10, 8)->nullable()->after('min');
            $table->double('std', 10, 8)->nullable()->after('max');
            $table->double('variance', 15, 12)->nullable()->after('std');
            $table->double('median', 10, 8)->nullable()->after('variance');
            $table->double('q1', 10, 8)->nullable()->after('median');
            $table->double('q3', 10, 8)->nullable()->after('q1');
            $table->double('p10', 10, 8)->nullable()->after('q3');
            $table->double('p90', 10, 8)->nullable()->after('p10');
            $table->double('cloud_coverage', 5, 2)->nullable()->after('p90');
        });
    }

    public function down(): void
    {
        Schema::table('ndvi_data', function (Blueprint $table) {
            $table->dropColumn([
                'scene_id', 'view_id', 'site_id',
                'average', 'min', 'max', 'std', 'variance',
                'median', 'q1', 'q3', 'p10', 'p90', 'cloud_coverage'
            ]);
        });
    }
};
