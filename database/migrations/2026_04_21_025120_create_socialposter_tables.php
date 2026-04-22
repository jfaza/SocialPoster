<?php

use ExpressionEngine\Service\Migration\Migration;

class CreateSocialposterTables extends Migration
{
    /**
     * Execute the migration
     * @return void
     */
    public function up()
    {
        ee()->load->dbforge();

        if (! ee()->db->table_exists('socialposter_settings')) {
            ee()->dbforge->add_field([
                'id' => [
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'setting_key' => [
                    'type' => 'varchar',
                    'constraint' => 80,
                ],
                'setting_value' => [
                    'type' => 'text',
                    'null' => true,
                ],
                'created_at' => [
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'default' => 0,
                ],
                'updated_at' => [
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'default' => 0,
                ],
            ]);
            ee()->dbforge->add_key('id', true);
            ee()->dbforge->add_key('setting_key');
            ee()->dbforge->create_table('socialposter_settings');
        }

        if (! ee()->db->table_exists('socialposter_generations')) {
            ee()->dbforge->add_field([
                'id' => [
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'site_id' => [
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'default' => 1,
                ],
                'member_id' => [
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'default' => 0,
                ],
                'prompt' => ['type' => 'text'],
                'title' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
                'post_text' => ['type' => 'text', 'null' => true],
                'intro_text' => ['type' => 'text', 'null' => true],
                'table_of_contents' => ['type' => 'text', 'null' => true],
                'keywords' => ['type' => 'text', 'null' => true],
                'category' => ['type' => 'varchar', 'constraint' => 120, 'default' => ''],
                'hashtags' => ['type' => 'text', 'null' => true],
                'external_link' => ['type' => 'text', 'null' => true],
                'internal_link' => ['type' => 'text', 'null' => true],
                'recommended_topics' => ['type' => 'text', 'null' => true],
                'image_brief' => ['type' => 'text', 'null' => true],
                'image_prompt' => ['type' => 'text', 'null' => true],
                'image_path' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
                'raw_response' => ['type' => 'mediumtext', 'null' => true],
                'created_at' => [
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'default' => 0,
                ],
            ]);
            ee()->dbforge->add_key('id', true);
            ee()->dbforge->add_key('site_id');
            ee()->dbforge->add_key('created_at');
            ee()->dbforge->create_table('socialposter_generations');
        }
    }

    /**
     * Rollback the migration
     * @return void
     */
    public function down()
    {
        ee()->load->dbforge();
        ee()->dbforge->drop_table('socialposter_generations', true);
        ee()->dbforge->drop_table('socialposter_settings', true);
    }
}
