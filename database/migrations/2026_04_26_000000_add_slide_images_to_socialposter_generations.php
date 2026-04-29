<?php

use ExpressionEngine\Service\Migration\Migration;

class AddSlideImagesToSocialposterGenerations extends Migration
{
    public function up()
    {
        ee()->load->dbforge();

        if (! ee()->db->table_exists('socialposter_generations')) {
            return;
        }

        if (! ee()->db->field_exists('slide_images', 'socialposter_generations')) {
            ee()->dbforge->add_column('socialposter_generations', [
                'slide_images' => ['type' => 'text', 'null' => true],
            ]);
        }
    }

    public function down()
    {
        ee()->load->dbforge();

        if (! ee()->db->table_exists('socialposter_generations')) {
            return;
        }

        if (ee()->db->field_exists('slide_images', 'socialposter_generations')) {
            ee()->dbforge->drop_column('socialposter_generations', 'slide_images');
        }
    }
}
