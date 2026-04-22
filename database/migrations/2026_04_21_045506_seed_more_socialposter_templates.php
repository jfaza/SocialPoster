<?php

use ExpressionEngine\Service\Migration\Migration;

class SeedMoreSocialposterTemplates extends Migration
{
    /**
     * Execute the migration
     * @return void
     */
    public function up()
    {
        if (ee()->db->table_exists('socialposter_templates')) {
            ee('socialposter:templates')->seedDefaults();
        }
    }

    /**
     * Rollback the migration
     * @return void
     */
    public function down()
    {
        // Keep templates in place; users may have edited generated presets.
    }
}
