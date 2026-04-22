<?php

use ExpressionEngine\Service\Migration\Migration;

class AddBlogMetadataToSocialposterGenerations extends Migration
{
    public function up()
    {
        ee()->load->dbforge();

        if (! ee()->db->table_exists('socialposter_generations')) {
            return;
        }

        if (! ee()->db->field_exists('category', 'socialposter_generations')) {
            ee()->dbforge->add_column('socialposter_generations', [
                'category' => [
                    'type' => 'varchar',
                    'constraint' => 120,
                    'default' => '',
                    'after' => 'keywords',
                ],
            ]);
        }

        if (! ee()->db->field_exists('hashtags', 'socialposter_generations')) {
            ee()->dbforge->add_column('socialposter_generations', [
                'hashtags' => [
                    'type' => 'text',
                    'null' => true,
                    'after' => 'category',
                ],
            ]);
        }

        if (! ee()->db->field_exists('image_brief', 'socialposter_generations')) {
            ee()->dbforge->add_column('socialposter_generations', [
                'image_brief' => [
                    'type' => 'text',
                    'null' => true,
                    'after' => 'recommended_topics',
                ],
            ]);
        }
    }

    public function down()
    {
        ee()->load->dbforge();

        if (! ee()->db->table_exists('socialposter_generations')) {
            return;
        }

        foreach (['image_brief', 'hashtags', 'category'] as $field) {
            if (ee()->db->field_exists($field, 'socialposter_generations')) {
                ee()->dbforge->drop_column('socialposter_generations', $field);
            }
        }
    }
}
