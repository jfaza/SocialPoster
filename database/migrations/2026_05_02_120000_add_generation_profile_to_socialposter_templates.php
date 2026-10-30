<?php

use ExpressionEngine\Service\Migration\Migration;

class AddGenerationProfileToSocialposterTemplates extends Migration
{
    public function up()
    {
        ee()->load->dbforge();

        if (! ee()->db->table_exists('socialposter_templates') || ee()->db->field_exists('generation_profile', 'socialposter_templates')) {
            return;
        }

        ee()->dbforge->add_column('socialposter_templates', [
            'generation_profile' => [
                'type' => 'varchar',
                'constraint' => 80,
                'default' => '',
                'after' => 'platform',
            ],
        ]);
    }

    public function down()
    {
        ee()->load->dbforge();

        if (ee()->db->table_exists('socialposter_templates') && ee()->db->field_exists('generation_profile', 'socialposter_templates')) {
            ee()->dbforge->drop_column('socialposter_templates', 'generation_profile');
        }
    }
}
