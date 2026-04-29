<?php

use ExpressionEngine\Service\Migration\Migration;

class AddCategoryToNewsSources extends Migration
{
    public function up()
    {
        ee()->load->dbforge();

        if (! ee()->db->table_exists('socialposter_news_sources')) {
            return;
        }

        if (ee()->db->field_exists('category', 'socialposter_news_sources')) {
            return;
        }

        ee()->dbforge->add_column('socialposter_news_sources', [
            'category' => [
                'type'       => 'varchar',
                'constraint' => 80,
                'default'    => '',
                'after'      => 'name',
            ],
        ]);
    }

    public function down()
    {
        ee()->load->dbforge();

        if (! ee()->db->table_exists('socialposter_news_sources')) {
            return;
        }

        ee()->dbforge->drop_column('socialposter_news_sources', 'category');
    }
}
