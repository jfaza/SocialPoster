<?php

use ExpressionEngine\Service\Migration\Migration;

class CreateSocialposterPublishedBlogs extends Migration
{
    public function up()
    {
        ee()->load->dbforge();

        if (ee()->db->table_exists('socialposter_published_blogs')) {
            return;
        }

        ee()->dbforge->add_field([
            'id' => [
                'type' => 'int',
                'constraint' => 10,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'generation_id' => [
                'type' => 'int',
                'constraint' => 10,
                'unsigned' => true,
                'default' => 0,
            ],
            'entry_id' => [
                'type' => 'int',
                'constraint' => 10,
                'unsigned' => true,
                'default' => 0,
            ],
            'title' => [
                'type' => 'varchar',
                'constraint' => 255,
                'default' => '',
            ],
            'status' => [
                'type' => 'varchar',
                'constraint' => 50,
                'default' => '',
            ],
            'url_title' => [
                'type' => 'varchar',
                'constraint' => 255,
                'default' => '',
            ],
            'created_at' => [
                'type' => 'int',
                'constraint' => 10,
                'unsigned' => true,
                'default' => 0,
            ],
        ]);

        ee()->dbforge->add_key('id', true);
        ee()->dbforge->add_key('generation_id');
        ee()->dbforge->add_key('entry_id');
        ee()->dbforge->add_key('created_at');
        ee()->dbforge->create_table('socialposter_published_blogs');
    }

    public function down()
    {
        ee()->load->dbforge();
        ee()->dbforge->drop_table('socialposter_published_blogs', true);
    }
}
