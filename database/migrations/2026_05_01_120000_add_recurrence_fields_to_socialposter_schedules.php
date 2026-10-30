<?php

use ExpressionEngine\Service\Migration\Migration;

class AddRecurrenceFieldsToSocialposterSchedules extends Migration
{
    public function up()
    {
        ee()->load->dbforge();

        if (! ee()->db->table_exists('socialposter_schedules')) {
            return;
        }

        $fields = [];

        if (! ee()->db->field_exists('recurrence_mode', 'socialposter_schedules')) {
            $fields['recurrence_mode'] = [
                'type' => 'varchar',
                'constraint' => 20,
                'default' => 'repeat',
            ];
        }

        if (! ee()->db->field_exists('recurrence_interval', 'socialposter_schedules')) {
            $fields['recurrence_interval'] = [
                'type' => 'int',
                'constraint' => 10,
                'unsigned' => true,
                'default' => 1,
            ];
        }

        if (! ee()->db->field_exists('recurrence_unit', 'socialposter_schedules')) {
            $fields['recurrence_unit'] = [
                'type' => 'varchar',
                'constraint' => 20,
                'default' => 'week',
            ];
        }

        if (! ee()->db->field_exists('weekdays', 'socialposter_schedules')) {
            $fields['weekdays'] = [
                'type' => 'varchar',
                'constraint' => 40,
                'default' => '',
            ];
        }

        if (! ee()->db->field_exists('anchor_at', 'socialposter_schedules')) {
            $fields['anchor_at'] = [
                'type' => 'int',
                'constraint' => 10,
                'unsigned' => true,
                'default' => 0,
            ];
        }

        if ($fields) {
            ee()->dbforge->add_column('socialposter_schedules', $fields);
        }

        $rows = ee()->db->get('socialposter_schedules')->result_array();
        foreach ($rows as $row) {
            $frequency = strtolower(trim((string) ($row['frequency'] ?? 'weekly')));
            $nextRunAt = (int) ($row['next_run_at'] ?? 0);

            $update = [
                'anchor_at' => $nextRunAt,
                'recurrence_mode' => 'repeat',
                'recurrence_interval' => 1,
                'recurrence_unit' => 'week',
                'weekdays' => '',
            ];

            switch ($frequency) {
                case 'daily':
                    $update['recurrence_unit'] = 'day';
                    break;
                case 'every_other_day':
                    $update['recurrence_unit'] = 'day';
                    $update['recurrence_interval'] = 2;
                    break;
                case 'every_3_days':
                    $update['recurrence_unit'] = 'day';
                    $update['recurrence_interval'] = 3;
                    break;
                case 'weekdays':
                    $update['recurrence_unit'] = 'week';
                    $update['weekdays'] = '1,2,3,4,5';
                    break;
                case 'biweekly':
                    $update['recurrence_unit'] = 'week';
                    $update['recurrence_interval'] = 2;
                    break;
                case 'monthly':
                    $update['recurrence_unit'] = 'month';
                    break;
                default:
                    $update['recurrence_unit'] = 'week';
                    break;
            }

            ee()->db->where('id', (int) $row['id'])->update('socialposter_schedules', $update);
        }
    }

    public function down()
    {
        ee()->load->dbforge();

        if (! ee()->db->table_exists('socialposter_schedules')) {
            return;
        }

        foreach (['recurrence_mode', 'recurrence_interval', 'recurrence_unit', 'weekdays', 'anchor_at'] as $field) {
            if (ee()->db->field_exists($field, 'socialposter_schedules')) {
                ee()->dbforge->drop_column('socialposter_schedules', $field);
            }
        }
    }
}
