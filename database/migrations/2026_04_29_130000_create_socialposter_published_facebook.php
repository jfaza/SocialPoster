<?php

use ExpressionEngine\Service\Migration\Migration;

class CreateSocialposterPublishedFacebook extends Migration
{
    public function up()
    {
        ee()->load->dbforge();

        if (ee()->db->table_exists('socialposter_published_facebook')) {
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
            'post_id' => [
                'type' => 'varchar',
                'constraint' => 80,
                'default' => '',
            ],
            'message' => [
                'type' => 'text',
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
        ee()->dbforge->add_key('created_at');
        ee()->dbforge->create_table('socialposter_published_facebook');
    }

    public function down()
    {
        ee()->load->dbforge();
        ee()->dbforge->drop_table('socialposter_published_facebook', true);
    }
}
