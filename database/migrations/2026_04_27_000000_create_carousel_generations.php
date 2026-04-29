<?php

use ExpressionEngine\Service\Migration\Migration;

class CreateCarouselGenerations extends Migration
{
    public function up()
    {
        ee()->load->dbforge();

        if (ee()->db->table_exists('socialposter_carousel_generations')) {
            return;
        }

        ee()->dbforge->add_field([
            'id' => [
                'type'          => 'int',
                'constraint'    => 10,
                'unsigned'      => true,
                'auto_increment' => true,
            ],
            'site_id' => [
                'type'       => 'int',
                'constraint' => 10,
                'unsigned'   => true,
                'default'    => 1,
            ],
            'member_id' => [
                'type'       => 'int',
                'constraint' => 10,
                'unsigned'   => true,
                'default'    => 0,
            ],
            'topic' => [
                'type'       => 'varchar',
                'constraint' => 500,
                'default'    => '',
            ],
            'style' => [
                'type'       => 'varchar',
                'constraint' => 50,
                'default'    => 'base',
            ],
            'carousel_template' => [
                'type'       => 'varchar',
                'constraint' => 100,
                'default'    => '',
            ],
            'caption'     => ['type' => 'text',     'null' => true],
            'hashtags'    => ['type' => 'text',     'null' => true],
            'slide_paths' => ['type' => 'text',     'null' => true],
            'data_json'   => ['type' => 'longtext', 'null' => true],
            'created_at'  => [
                'type'       => 'int',
                'constraint' => 10,
                'unsigned'   => true,
                'default'    => 0,
            ],
        ]);

        ee()->dbforge->add_key('id', true);
        ee()->dbforge->add_key('site_id');
        ee()->dbforge->add_key('created_at');
        ee()->dbforge->create_table('socialposter_carousel_generations');
    }

    public function down()
    {
        ee()->load->dbforge();
        ee()->dbforge->drop_table('socialposter_carousel_generations', true);
    }
}
