<?php

use ExpressionEngine\Service\Migration\Migration;

class DropSlidePathsFromCarouselGenerations extends Migration
{
    public function up()
    {
        ee()->load->dbforge();

        if (! ee()->db->table_exists('socialposter_carousel_generations')) {
            return;
        }

        if (! ee()->db->field_exists('slide_paths', 'socialposter_carousel_generations')) {
            return;
        }

        ee()->dbforge->drop_column('socialposter_carousel_generations', 'slide_paths');
    }

    public function down()
    {
        ee()->load->dbforge();

        if (! ee()->db->table_exists('socialposter_carousel_generations')) {
            return;
        }

        if (ee()->db->field_exists('slide_paths', 'socialposter_carousel_generations')) {
            return;
        }

        ee()->dbforge->add_column('socialposter_carousel_generations', [
            'slide_paths' => [
                'type'  => 'TEXT',
                'null'  => true,
                'after' => 'hashtags',
            ],
        ]);
    }
}
