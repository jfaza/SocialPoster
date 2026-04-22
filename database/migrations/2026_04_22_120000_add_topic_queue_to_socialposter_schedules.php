<?php

use ExpressionEngine\Service\Migration\Migration;

class AddTopicQueueToSocialposterSchedules extends Migration
{
    /**
     * Execute the migration
     * @return void
     */
    public function up()
    {
        ee()->load->dbforge();

        if (! ee()->db->table_exists('socialposter_schedules')) {
            return;
        }

        $fields = [];

        if (! ee()->db->field_exists('planned_topics', 'socialposter_schedules')) {
            $fields['planned_topics'] = ['type' => 'text', 'null' => true];
        }

        if (! ee()->db->field_exists('topic_cursor', 'socialposter_schedules')) {
            $fields['topic_cursor'] = [
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

    /**
     * Rollback the migration
     * @return void
     */
    public function down()
    {
        ee()->load->dbforge();

        if (! ee()->db->table_exists('socialposter_schedules')) {
            return;
        }

        foreach (['planned_topics', 'topic_cursor'] as $field) {
            if (ee()->db->field_exists($field, 'socialposter_schedules')) {
                ee()->dbforge->drop_column('socialposter_schedules', $field);
            }
        }
    }
}
