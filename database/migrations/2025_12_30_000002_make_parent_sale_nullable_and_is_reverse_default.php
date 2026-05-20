<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Attempt to alter columns safely even if doctrine/dbal is not installed
        DB::statement("ALTER TABLE `contact_farming_sales` MODIFY COLUMN `parent_sale_id` BIGINT UNSIGNED NULL;");
        DB::statement("ALTER TABLE `contact_farming_sales` MODIFY COLUMN `is_reverse` TINYINT(1) NOT NULL DEFAULT 0;");
    }

    public function down()
    {
        // Revert to NOT NULL without default (best-effort)
        DB::statement("ALTER TABLE `contact_farming_sales` MODIFY COLUMN `parent_sale_id` BIGINT UNSIGNED NOT NULL;");
        DB::statement("ALTER TABLE `contact_farming_sales` MODIFY COLUMN `is_reverse` TINYINT(1) NOT NULL;");
    }
};
