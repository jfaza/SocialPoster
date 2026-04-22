<?php

use ExpressionEngine\Service\Migration\Migration;

class CreateSocialposterTemplates extends Migration
{
    /**
     * Execute the migration
     * @return void
     */
    public function up()
    {
        ee()->load->dbforge();

        if (! ee()->db->table_exists('socialposter_templates')) {
            ee()->dbforge->add_field([
                'id' => ['type' => 'int', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
                'site_id' => ['type' => 'int', 'constraint' => 10, 'unsigned' => true, 'default' => 1],
                'title' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
                'content_type' => ['type' => 'varchar', 'constraint' => 60, 'default' => 'social_post'],
                'platform' => ['type' => 'varchar', 'constraint' => 120, 'default' => 'Website'],
                'length_preset' => ['type' => 'varchar', 'constraint' => 60, 'default' => 'medium'],
                'word_count' => ['type' => 'int', 'constraint' => 10, 'unsigned' => true, 'default' => 0],
                'tone' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
                'audience' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
                'goal' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
                'research_mode' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
                'citation_count' => ['type' => 'int', 'constraint' => 10, 'unsigned' => true, 'default' => 0],
                'internal_link_count' => ['type' => 'int', 'constraint' => 10, 'unsigned' => true, 'default' => 0],
                'external_link_count' => ['type' => 'int', 'constraint' => 10, 'unsigned' => true, 'default' => 0],
                'schema_type' => ['type' => 'varchar', 'constraint' => 80, 'default' => ''],
                'image_style' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
                'cta_style' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
                'prompt_instructions' => ['type' => 'text', 'null' => true],
                'is_default' => ['type' => 'tinyint', 'constraint' => 1, 'unsigned' => true, 'default' => 0],
                'created_at' => ['type' => 'int', 'constraint' => 10, 'unsigned' => true, 'default' => 0],
                'updated_at' => ['type' => 'int', 'constraint' => 10, 'unsigned' => true, 'default' => 0],
            ]);
            ee()->dbforge->add_key('id', true);
            ee()->dbforge->add_key('site_id');
            ee()->dbforge->add_key('is_default');
            ee()->dbforge->create_table('socialposter_templates');
        }

        foreach (['socialposter_schedules', 'socialposter_generations'] as $table) {
            if (ee()->db->table_exists($table) && ! ee()->db->field_exists('template_id', $table)) {
                ee()->dbforge->add_column($table, [
                    'template_id' => [
                        'type' => 'int',
                        'constraint' => 10,
                        'unsigned' => true,
                        'default' => 0,
                    ],
                ]);
            }
        }
    }

    /**
     * Rollback the migration
     * @return void
     */
    public function down()
    {
        ee()->load->dbforge();
        ee()->dbforge->drop_table('socialposter_templates', true);

        foreach (['socialposter_schedules', 'socialposter_generations'] as $table) {
            if (ee()->db->table_exists($table) && ee()->db->field_exists('template_id', $table)) {
                ee()->dbforge->drop_column($table, 'template_id');
            }
        }
    }
}
