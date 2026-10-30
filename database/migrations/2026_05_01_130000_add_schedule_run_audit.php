<?php

use ExpressionEngine\Service\Migration\Migration;

class AddScheduleRunAudit extends Migration
{
    public function up()
    {
        ee()->load->dbforge();

        if (! ee()->db->table_exists('socialposter_schedule_runs')) {
            ee()->dbforge->add_field([
                'id' => [
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'site_id' => [
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'default' => 1,
                ],
                'schedule_id' => [
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'default' => 0,
                ],
                'template_id' => [
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'default' => 0,
                ],
                'scheduled_for' => [
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'default' => 0,
                ],
                'started_at' => [
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'default' => 0,
                ],
                'finished_at' => [
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'default' => 0,
                ],
                'status' => [
                    'type' => 'varchar',
                    'constraint' => 40,
                    'default' => 'failed',
                ],
                'content_type' => [
                    'type' => 'varchar',
                    'constraint' => 30,
                    'default' => '',
                ],
                'content_record_id' => [
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'default' => 0,
                ],
                'topic_source' => [
                    'type' => 'varchar',
                    'constraint' => 20,
                    'default' => 'fresh',
                ],
                'planned_topic' => [
                    'type' => 'text',
                    'null' => true,
                ],
                'final_title' => [
                    'type' => 'varchar',
                    'constraint' => 255,
                    'default' => '',
                ],
                'duplicate_retry_count' => [
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'default' => 0,
                ],
                'duplicate_match_title' => [
                    'type' => 'varchar',
                    'constraint' => 255,
                    'default' => '',
                ],
                'message' => [
                    'type' => 'text',
                    'null' => true,
                ],
                'created_at' => [
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'default' => 0,
                ],
            ]);
            ee()->dbforge->add_key('id', true);
            ee()->dbforge->add_key('site_id');
            ee()->dbforge->add_key('schedule_id');
            ee()->dbforge->add_key('scheduled_for');
            ee()->dbforge->create_table('socialposter_schedule_runs');
        }

        if (! ee()->db->table_exists('socialposter_schedules')) {
            return;
        }

        $fields = [];
        if (! ee()->db->field_exists('last_status', 'socialposter_schedules')) {
            $fields['last_status'] = [
                'type' => 'varchar',
                'constraint' => 40,
                'default' => '',
            ];
        }
        if (! ee()->db->field_exists('last_message', 'socialposter_schedules')) {
            $fields['last_message'] = [
                'type' => 'text',
                'null' => true,
            ];
        }
        if (! ee()->db->field_exists('last_generation_table', 'socialposter_schedules')) {
            $fields['last_generation_table'] = [
                'type' => 'varchar',
                'constraint' => 80,
                'default' => '',
            ];
        }
        if (! ee()->db->field_exists('last_generation_id', 'socialposter_schedules')) {
            $fields['last_generation_id'] = [
                'type' => 'int',
                'constraint' => 10,
                'unsigned' => true,
                'default' => 0,
            ];
        }
        if (! ee()->db->field_exists('last_scheduled_for', 'socialposter_schedules')) {
            $fields['last_scheduled_for'] = [
                'type' => 'int',
                'constraint' => 10,
                'unsigned' => true,
                'default' => 0,
            ];
        }

        if ($fields) {
            ee()->dbforge->add_column('socialposter_schedules', $fields);
        }
    }

    public function down()
    {
        ee()->load->dbforge();

        if (ee()->db->table_exists('socialposter_schedule_runs')) {
            ee()->dbforge->drop_table('socialposter_schedule_runs', true);
        }

        if (! ee()->db->table_exists('socialposter_schedules')) {
            return;
        }

        foreach (['last_status', 'last_message', 'last_generation_table', 'last_generation_id', 'last_scheduled_for'] as $field) {
            if (ee()->db->field_exists($field, 'socialposter_schedules')) {
                ee()->dbforge->drop_column('socialposter_schedules', $field);
            }
        }
    }
}
