<?php

use ExpressionEngine\Service\Migration\Migration;

class CreateHotArticlesCache extends Migration
{
    public function up()
    {
        ee()->load->dbforge();

        if (ee()->db->table_exists('socialposter_hot_articles_cache')) {
            return;
        }

        ee()->dbforge->add_field([
            'id' => [
                'type'           => 'int',
                'constraint'     => 10,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'site_id' => [
                'type'       => 'int',
                'constraint' => 10,
                'unsigned'   => true,
                'default'    => 1,
            ],
            'source_id' => [
                'type'       => 'int',
                'constraint' => 10,
                'unsigned'   => true,
                'default'    => 0,
            ],
            'source_name' => [
                'type'       => 'varchar',
                'constraint' => 255,
                'default'    => '',
            ],
            'category' => [
                'type'       => 'varchar',
                'constraint' => 80,
                'default'    => '',
            ],
            'title' => [
                'type'       => 'varchar',
                'constraint' => 500,
                'default'    => '',
            ],
            'url'           => ['type' => 'text', 'null' => true],
            'url_hash'      => ['type' => 'char', 'constraint' => 40, 'default' => ''],
            'excerpt'       => ['type' => 'text', 'null' => true],
            'image_url'     => ['type' => 'text', 'null' => true],
            'published_at'  => ['type' => 'varchar', 'constraint' => 120, 'default' => ''],
            'published_ts'  => ['type' => 'int', 'constraint' => 10, 'unsigned' => true, 'default' => 0],
            'hot_score'     => ['type' => 'decimal', 'constraint' => '8,2', 'default' => '0.00'],
            'score_reasons' => ['type' => 'text', 'null' => true],
            'cached_at'     => ['type' => 'int', 'constraint' => 10, 'unsigned' => true, 'default' => 0],
        ]);

        ee()->dbforge->add_key('id', true);
        ee()->dbforge->add_key('site_id');
        ee()->dbforge->add_key('source_id');
        ee()->dbforge->add_key('url_hash');
        ee()->dbforge->add_key('hot_score');
        ee()->dbforge->add_key('cached_at');
        ee()->dbforge->create_table('socialposter_hot_articles_cache');
    }

    public function down()
    {
        ee()->load->dbforge();
        ee()->dbforge->drop_table('socialposter_hot_articles_cache', true);
    }
}
