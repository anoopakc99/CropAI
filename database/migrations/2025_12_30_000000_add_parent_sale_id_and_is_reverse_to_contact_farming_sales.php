<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('contact_farming_sales', function (Blueprint $table) {
            if (!Schema::hasColumn('contact_farming_sales', 'parent_sale_id')) {
                $table->unsignedBigInteger('parent_sale_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('contact_farming_sales', 'is_reverse')) {
                $table->tinyInteger('is_reverse')->default(0)->after('parent_sale_id');
            }
        });
    }

    public function down()
    {
        Schema::table('contact_farming_sales', function (Blueprint $table) {
            if (Schema::hasColumn('contact_farming_sales', 'is_reverse')) {
                $table->dropColumn('is_reverse');
            }
            if (Schema::hasColumn('contact_farming_sales', 'parent_sale_id')) {
                $table->dropColumn('parent_sale_id');
            }
        });
    }
};
