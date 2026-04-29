<?php

use ExpressionEngine\Service\Migration\Migration;

class CreateNewsSources extends Migration
{
    public function up()
    {
        ee()->load->dbforge();

        if (ee()->db->table_exists('socialposter_news_sources')) {
            return;
        }

        ee()->dbforge->add_field([
            'id' => [
                'type'           => 'int',
                'constraint'     => 10,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type'       => 'varchar',
                'constraint' => 255,
                'default'    => '',
            ],
            'feed_url' => [
                'type' => 'text',
                'null' => true,
            ],
            'is_active' => [
                'type'       => 'tinyint',
                'constraint' => 1,
                'unsigned'   => true,
                'default'    => 1,
            ],
            'created_at' => [
                'type'       => 'int',
                'constraint' => 10,
                'unsigned'   => true,
                'default'    => 0,
            ],
        ]);

        ee()->dbforge->add_key('id', true);
        ee()->dbforge->create_table('socialposter_news_sources');
    }

    public function down()
    {
        ee()->load->dbforge();
        ee()->dbforge->drop_table('socialposter_news_sources', true);
    }
}
