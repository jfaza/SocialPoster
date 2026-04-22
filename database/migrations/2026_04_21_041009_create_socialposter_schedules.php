<?php

use ExpressionEngine\Service\Migration\Migration;

class CreateSocialposterSchedules extends Migration
{
    /**
     * Execute the migration
     * @return void
     */
    public function up()
    {
        ee()->load->dbforge();

        if (! ee()->db->table_exists('socialposter_schedules')) {
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
                'member_id' => [
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'default' => 0,
                ],
                'title' => ['type' => 'varchar', 'constraint' => 255, 'default' => ''],
                'prompt' => ['type' => 'text'],
                'frequency' => ['type' => 'varchar', 'constraint' => 30, 'default' => 'weekly'],
                'is_active' => [
                    'type' => 'tinyint',
                    'constraint' => 1,
                    'unsigned' => true,
                    'default' => 1,
                ],
                'next_run_at' => [
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'default' => 0,
                ],
                'last_run_at' => [
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'default' => 0,
                ],
                'run_count' => [
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'default' => 0,
                ],
                'last_error' => ['type' => 'text', 'null' => true],
                'created_at' => [
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'default' => 0,
                ],
                'updated_at' => [
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'default' => 0,
                ],
            ]);
            ee()->dbforge->add_key('id', true);
            ee()->dbforge->add_key('site_id');
            ee()->dbforge->add_key('next_run_at');
            ee()->dbforge->create_table('socialposter_schedules');
        }

        if (ee()->db->table_exists('socialposter_generations')) {
            $fields = [];

            if (! ee()->db->field_exists('schedule_id', 'socialposter_generations')) {
                $fields['schedule_id'] = [
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'default' => 0,
                ];
            }

            if (! ee()->db->field_exists('scheduled_for', 'socialposter_generations')) {
                $fields['scheduled_for'] = [
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'default' => 0,
                ];
            }

            if (! ee()->db->field_exists('source', 'socialposter_generations')) {
                $fields['source'] = [
                    'type' => 'varchar',
                    'constraint' => 30,
                    'default' => 'manual',
                ];
            }

            if ($fields) {
                ee()->dbforge->add_column('socialposter_generations', $fields);
            }
        }
    }

    /**
     * Rollback the migration
     * @return void
     */
    public function down()
    {
        ee()->load->dbforge();
        ee()->dbforge->drop_table('socialposter_schedules', true);

        if (ee()->db->table_exists('socialposter_generations')) {
            foreach (['schedule_id', 'scheduled_for', 'source'] as $field) {
                if (ee()->db->field_exists($field, 'socialposter_generations')) {
                    ee()->dbforge->drop_column('socialposter_generations', $field);
                }
            }
        }
    }
}
