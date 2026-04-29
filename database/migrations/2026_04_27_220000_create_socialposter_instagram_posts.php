<?php

use ExpressionEngine\Service\Migration\Migration;

class CreateSocialposterInstagramPosts extends Migration
{
    public function up()
    {
        ee()->load->dbforge();

        if (! ee()->db->table_exists('socialposter_instagram_posts')) {
            ee()->dbforge->add_field([
                'id'                  => ['type' => 'int',        'constraint' => 10,  'unsigned' => true, 'auto_increment' => true],
                'site_id'             => ['type' => 'int',        'constraint' => 10,  'unsigned' => true, 'default' => 1],
                'member_id'           => ['type' => 'int',        'constraint' => 10,  'unsigned' => true, 'default' => 0],
                'prompt'              => ['type' => 'text',       'null' => true],
                'title'               => ['type' => 'varchar',    'constraint' => 255, 'default' => ''],
                'post_text'           => ['type' => 'text',       'null' => true],
                'intro_text'          => ['type' => 'text',       'null' => true],
                'table_of_contents'   => ['type' => 'text',       'null' => true],
                'keywords'            => ['type' => 'text',       'null' => true],
                'external_link'       => ['type' => 'text',       'null' => true],
                'internal_link'       => ['type' => 'text',       'null' => true],
                'recommended_topics'  => ['type' => 'text',       'null' => true],
                'image_prompt'        => ['type' => 'text',       'null' => true],
                'image_path'          => ['type' => 'varchar',    'constraint' => 255, 'default' => ''],
                'raw_response'        => ['type' => 'mediumtext', 'null' => true],
                'created_at'          => ['type' => 'int',        'constraint' => 10,  'unsigned' => true, 'default' => 0],
                'schedule_id'         => ['type' => 'int',        'constraint' => 10,  'unsigned' => true, 'default' => 0],
                'scheduled_for'       => ['type' => 'int',        'constraint' => 10,  'unsigned' => true, 'default' => 0],
                'source'              => ['type' => 'varchar',    'constraint' => 30,  'default' => 'instagram'],
                'template_id'         => ['type' => 'int',        'constraint' => 10,  'unsigned' => true, 'default' => 0],
                'category'            => ['type' => 'varchar',    'constraint' => 120, 'default' => ''],
                'hashtags'            => ['type' => 'text',       'null' => true],
                'image_brief'         => ['type' => 'text',       'null' => true],
                'slide_images'        => ['type' => 'text',       'null' => true],
            ]);

            ee()->dbforge->add_key('id', true);
            ee()->dbforge->add_key('site_id');
            ee()->dbforge->add_key('created_at');
            ee()->dbforge->create_table('socialposter_instagram_posts');
        }

        // Move existing Instagram records out of socialposter_generations
        if (
            ee()->db->table_exists('socialposter_generations') &&
            ee()->db->table_exists('socialposter_instagram_posts')
        ) {
            $rows = ee()->db
                ->where_in('source', ['news', 'instagram'])
                ->or_where('template_id IN (SELECT id FROM ' . ee()->db->dbprefix . 'socialposter_templates WHERE LOWER(platform) = "instagram")', null, false)
                ->get('socialposter_generations')
                ->result_array();

            foreach ($rows as $row) {
                $existing = ee()->db
                    ->where('id', (int) $row['id'])
                    ->get('socialposter_instagram_posts')
                    ->row_array();

                if (! $existing) {
                    ee()->db->insert('socialposter_instagram_posts', $row);
                }

                ee()->db->where('id', (int) $row['id'])->delete('socialposter_generations');
            }
        }
    }

    public function down()
    {
        ee()->load->dbforge();
        ee()->dbforge->drop_table('socialposter_instagram_posts', true);
    }
}
