<?php

use ExpressionEngine\Service\Migration\Migration;

class CreateSocialposterReviews extends Migration
{
    public function up()
    {
        if (ee()->db->table_exists('socialposter_reviews')) {
            return;
        }

        ee()->load->dbforge();
        ee()->dbforge->add_field([
            'id' => ['type' => 'int', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'generation_id' => ['type' => 'int', 'constraint' => 10, 'unsigned' => true, 'default' => 0],
            'model' => ['type' => 'varchar', 'constraint' => 80, 'default' => ''],
            'verdict' => ['type' => 'varchar', 'constraint' => 20, 'default' => ''],
            'summary' => ['type' => 'text', 'null' => true],
            'issues' => ['type' => 'mediumtext', 'null' => true],
            'created_at' => ['type' => 'int', 'constraint' => 10, 'unsigned' => true, 'default' => 0],
        ]);
        ee()->dbforge->add_key('id', true);
        ee()->dbforge->add_key('generation_id');
        ee()->dbforge->create_table('socialposter_reviews');
    }

    public function down()
    {
        ee()->load->dbforge();
        ee()->dbforge->drop_table('socialposter_reviews');
    }
}
