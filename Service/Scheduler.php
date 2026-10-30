<?php

namespace JavidFazaeli\SocialPoster\Service;

use JavidFazaeli\SocialPoster\Service\InstagramPostGenerator;

class Scheduler
{
    private const RUN_TABLE = 'socialposter_schedule_runs';

    private SocialPostGenerator $generator;
    private SocialPostGenerator $instagramGenerator;

    public function __construct(?SocialPostGenerator $generator = null, ?SocialPostGenerator $instagramGenerator = null)
    {
        $this->generator          = $generator          ?: new SocialPostGenerator();
        $this->instagramGenerator = $instagramGenerator ?: new InstagramPostGenerator();
    }

    public function frequencies(): array
    {
        return [
            'daily' => 'Every day',
            'every_other_day' => 'Every other day',
            'every_3_days' => 'Every 3 days',
            'weekdays' => 'Every weekday',
            'weekly' => 'Weekly',
            'biweekly' => 'Every 2 weeks',
            'monthly' => 'Monthly',
        ];
    }

    public function recurrenceModes(): array
    {
        return [
            'once' => 'Once',
            'repeat' => 'Every',
        ];
    }

    public function recurrenceUnits(): array
    {
        return [
            'day' => 'day(s)',
            'week' => 'week(s)',
            'month' => 'month(s)',
        ];
    }

    public function weekdayOptions(): array
    {
        return [
            0 => 'Sun',
            1 => 'Mon',
            2 => 'Tue',
            3 => 'Wed',
            4 => 'Thu',
            5 => 'Fri',
            6 => 'Sat',
        ];
    }

    public function create(array $input): int
    {
        if (! ee()->db->table_exists('socialposter_schedules')) {
            throw new \RuntimeException('SocialPoster schedule table is missing. Run add-on migrations.');
        }

        $templateId = (int) ($input['template_id'] ?? 0);
        $template = $templateId > 0 ? ee('socialposter:templates')->find($templateId) : null;
        if (! $template) {
            throw new \InvalidArgumentException('Please choose a generation template.');
        }

        $startAt = $this->parseStartAt((string) ($input['start_date'] ?? ''), (string) ($input['start_time'] ?? '09:00'));
        $recurrence = $this->normalizeRecurrenceInput($input, $startAt);
        $firstRunAt = $this->firstRunAtForRecurrence($startAt, $recurrence);
        $now = ee()->localize->now;

        $record = [
            'site_id' => (int) ee()->config->item('site_id'),
            'member_id' => (int) ee()->session->userdata('member_id'),
            'title' => trim((string) ($input['title'] ?? '')) ?: (string) $template['title'],
            'prompt' => $this->promptFromTemplate($template),
            'frequency' => $this->legacyFrequencyFromRecurrence($recurrence),
            'is_active' => 1,
            'next_run_at' => $firstRunAt,
            'last_run_at' => 0,
            'run_count' => 0,
            'last_error' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        if (ee()->db->field_exists('planned_topics', 'socialposter_schedules')) {
            $record['planned_topics'] = $this->normalizeTopicText((string) ($input['planned_topics'] ?? ''));
        }

        if (ee()->db->field_exists('topic_cursor', 'socialposter_schedules')) {
            $record['topic_cursor'] = 0;
        }

        if (ee()->db->field_exists('template_id', 'socialposter_schedules')) {
            $record['template_id'] = $templateId;
        }

        foreach (['recurrence_mode', 'recurrence_interval', 'recurrence_unit', 'weekdays', 'anchor_at'] as $field) {
            if (ee()->db->field_exists($field, 'socialposter_schedules') && array_key_exists($field, $recurrence)) {
                $record[$field] = $recurrence[$field];
            }
        }

        ee()->db->insert('socialposter_schedules', $record);

        return (int) ee()->db->insert_id();
    }

    public function find(int $id): ?array
    {
        if ($id < 1 || ! ee()->db->table_exists('socialposter_schedules')) {
            return null;
        }

        $row = ee()->db->where('id', $id)->get('socialposter_schedules')->row_array();
        return $row ?: null;
    }

    public function update(int $id, array $input): bool
    {
        $existing = $this->find($id);
        if (! $existing) {
            throw new \InvalidArgumentException('Schedule not found.');
        }

        $templateId = (int) ($input['template_id'] ?? 0);
        $template = $templateId > 0 ? ee('socialposter:templates')->find($templateId) : null;
        if (! $template) {
            throw new \InvalidArgumentException('Please choose a generation template.');
        }

        $plannedTopics = $this->normalizeTopicText((string) ($input['planned_topics'] ?? ''));
        $topicCursor = max(0, (int) ($existing['topic_cursor'] ?? 0));
        $topicCount = count(array_values(array_filter(array_map('trim', preg_split('/\R/', $plannedTopics) ?: []))));
        $startAt = $this->parseStartAt((string) ($input['start_date'] ?? ''), (string) ($input['start_time'] ?? '09:00'));
        $recurrence = $this->normalizeRecurrenceInput($input, $startAt);
        $firstRunAt = $this->firstRunAtForRecurrence($startAt, $recurrence);

        $record = [
            'title' => trim((string) ($input['title'] ?? '')) ?: (string) $template['title'],
            'prompt' => $this->promptFromTemplate($template),
            'frequency' => $this->legacyFrequencyFromRecurrence($recurrence),
            'next_run_at' => $firstRunAt,
            'last_error' => null,
            'updated_at' => ee()->localize->now,
        ];

        if (ee()->db->field_exists('planned_topics', 'socialposter_schedules')) {
            $record['planned_topics'] = $plannedTopics;
        }

        if (ee()->db->field_exists('topic_cursor', 'socialposter_schedules')) {
            $record['topic_cursor'] = min($topicCursor, $topicCount);
        }

        if (ee()->db->field_exists('template_id', 'socialposter_schedules')) {
            $record['template_id'] = $templateId;
        }

        foreach (['recurrence_mode', 'recurrence_interval', 'recurrence_unit', 'weekdays', 'anchor_at'] as $field) {
            if (ee()->db->field_exists($field, 'socialposter_schedules') && array_key_exists($field, $recurrence)) {
                $record[$field] = $recurrence[$field];
            }
        }

        ee()->db->where('id', $id)->update('socialposter_schedules', $record);

        return ee()->db->affected_rows() >= 0;
    }

    public function delete(int $id): bool
    {
        ee()->db->where('id', $id)->delete('socialposter_schedules');
        return ee()->db->affected_rows() > 0;
    }

    public function toggle(int $id): bool
    {
        $row = ee()->db->where('id', $id)->get('socialposter_schedules')->row_array();
        if (! $row) {
            return false;
        }

        ee()->db->where('id', $id)->update('socialposter_schedules', [
            'is_active' => empty($row['is_active']) ? 1 : 0,
            'updated_at' => ee()->localize->now,
        ]);

        return true;
    }

    public function runNow(int $id): array
    {
        $row = $this->find($id);
        if (! $row) {
            throw new \InvalidArgumentException('Schedule not found.');
        }

        return $this->runSchedule($row, ee()->localize->now);
    }

    public function retryLastFailedRun(int $id): array
    {
        $row = $this->find($id);
        if (! $row) {
            throw new \InvalidArgumentException('Schedule not found.');
        }

        $runs = $this->recentRunsForSchedule($id, 10);
        $failedRun = null;
        foreach ($runs as $run) {
            if (in_array((string) ($run['status'] ?? ''), ['failed', 'duplicate_exhausted', 'image_failed'], true)) {
                $failedRun = $run;
                break;
            }
        }

        if (! $failedRun) {
            throw new \RuntimeException('No failed run is available to retry.');
        }

        return $this->runSchedule($row, ee()->localize->now, [
            'topic_source' => (string) ($failedRun['topic_source'] ?? ''),
            'planned_topic' => (string) ($failedRun['planned_topic'] ?? ''),
            'retry_failed_run' => true,
        ]);
    }

    public function skipCurrentTopic(int $id): bool
    {
        $row = $this->find($id);
        if (! $row) {
            throw new \InvalidArgumentException('Schedule not found.');
        }

        $topic = $this->nextTopic($row);
        if ($topic === '') {
            throw new \RuntimeException('This schedule has no queued planned topic to skip.');
        }

        if (! ee()->db->field_exists('topic_cursor', 'socialposter_schedules')) {
            throw new \RuntimeException('Topic queue controls are not available on this installation.');
        }

        $nextCursor = (int) ($row['topic_cursor'] ?? 0) + 1;
        ee()->db->where('id', $id)->update('socialposter_schedules', [
            'topic_cursor' => $nextCursor,
            'last_status' => 'skipped',
            'last_message' => 'Skipped planned topic: ' . $topic,
            'last_error' => null,
            'updated_at' => ee()->localize->now,
        ] + $this->scheduleAuditSummaryFields([
            'last_generation_table' => '',
            'last_generation_id' => 0,
        ]));

        $this->createAndFinishStandaloneRunLog($row, [
            'scheduled_for' => (int) ($row['next_run_at'] ?? 0),
            'status' => 'skipped',
            'topic_source' => 'planned',
            'planned_topic' => $topic,
            'message' => 'Skipped planned topic: ' . $topic,
        ]);

        return true;
    }

    public function schedules(): array
    {
        if (! ee()->db->table_exists('socialposter_schedules')) {
            return [];
        }

        return ee()->db
            ->order_by('next_run_at', 'ASC')
            ->get('socialposter_schedules')
            ->result_array();
    }

    public function recentRunsForSchedule(int $scheduleId, int $limit = 5): array
    {
        if ($scheduleId < 1 || ! ee()->db->table_exists(self::RUN_TABLE)) {
            return [];
        }

        return ee()->db
            ->where('schedule_id', $scheduleId)
            ->order_by('started_at', 'DESC')
            ->limit(max(1, $limit))
            ->get(self::RUN_TABLE)
            ->result_array();
    }

    public function latestRunsBySchedule(array $scheduleIds): array
    {
        $scheduleIds = array_values(array_filter(array_map('intval', $scheduleIds)));
        if (! $scheduleIds || ! ee()->db->table_exists(self::RUN_TABLE)) {
            return [];
        }

        $rows = ee()->db
            ->where_in('schedule_id', $scheduleIds)
            ->order_by('started_at', 'DESC')
            ->get(self::RUN_TABLE)
            ->result_array();

        $records = [];
        foreach ($rows as $row) {
            $scheduleId = (int) ($row['schedule_id'] ?? 0);
            if ($scheduleId > 0 && ! isset($records[$scheduleId])) {
                $records[$scheduleId] = $row;
            }
        }

        return $records;
    }

    public function latestRunsByContent(string $contentType, array $contentRecordIds): array
    {
        $contentRecordIds = array_values(array_filter(array_map('intval', $contentRecordIds)));
        $contentType = trim($contentType);
        if ($contentType === '' || ! $contentRecordIds || ! ee()->db->table_exists(self::RUN_TABLE)) {
            return [];
        }

        $rows = ee()->db
            ->where('content_type', $contentType)
            ->where_in('content_record_id', $contentRecordIds)
            ->order_by('started_at', 'DESC')
            ->get(self::RUN_TABLE)
            ->result_array();

        $records = [];
        foreach ($rows as $row) {
            $contentRecordId = (int) ($row['content_record_id'] ?? 0);
            if ($contentRecordId > 0 && ! isset($records[$contentRecordId])) {
                $records[$contentRecordId] = $row;
            }
        }

        return $records;
    }

    public function formValues(?array $schedule = null): array
    {
        $schedule = $schedule ?: [];
        $nextRunAt = (int) ($schedule['next_run_at'] ?? 0);
        $config = $this->recurrenceConfig($schedule);

        return [
            'id' => (int) ($schedule['id'] ?? 0),
            'title' => (string) ($schedule['title'] ?? ''),
            'template_id' => (int) ($schedule['template_id'] ?? 0),
            'next_run_at' => $nextRunAt > 0 ? $nextRunAt : time(),
            'planned_topics' => (string) ($schedule['planned_topics'] ?? ''),
            'recurrence_mode' => $config['recurrence_mode'],
            'recurrence_interval' => $config['recurrence_interval'],
            'recurrence_unit' => $config['recurrence_unit'],
            'weekdays' => $config['weekdays'],
        ];
    }

    public function frequencyLabel(array $schedule): string
    {
        $config = $this->recurrenceConfig($schedule);

        if ($config['recurrence_mode'] === 'once') {
            return 'Once';
        }

        if ($config['recurrence_unit'] === 'week' && $config['weekdays']) {
            $dayLabels = array_values(array_intersect_key($this->weekdayOptions(), array_flip($config['weekdays'])));
            $prefix = $config['recurrence_interval'] === 1 ? 'Every' : 'Every ' . (int) $config['recurrence_interval'] . ' weeks on';
            if ($config['recurrence_interval'] === 1) {
                return $prefix . ' ' . implode(', ', $dayLabels);
            }

            return $prefix . ' ' . implode(', ', $dayLabels);
        }

        $unit = $config['recurrence_unit'];
        $interval = max(1, (int) $config['recurrence_interval']);

        if ($interval === 1) {
            return match ($unit) {
                'day' => 'Every day',
                'week' => 'Every week',
                'month' => 'Every month',
                default => 'Every week',
            };
        }

        return 'Every ' . $interval . ' ' . $unit . ($interval === 1 ? '' : 's');
    }

    public function runDue(int $limit = 5): array
    {
        $now = ee()->localize->now;
        $rows = ee()->db
            ->where('is_active', 1)
            ->where('next_run_at <=', $now)
            ->order_by('next_run_at', 'ASC')
            ->limit(max(1, $limit))
            ->get('socialposter_schedules')
            ->result_array();

        $results = ['generated' => 0, 'failed' => 0, 'duplicate_protected' => 0, 'skipped' => 0, 'items' => []];

        foreach ($rows as $row) {
            $item = $this->runSchedule($row, (int) ($row['next_run_at'] ?? 0));
            if (! empty($item['ok'])) {
                $results['generated']++;
                if (($item['status'] ?? '') === 'duplicate_retry') {
                    $results['duplicate_protected']++;
                }
            } else {
                $results['failed']++;
            }
            if (($item['status'] ?? '') === 'skipped') {
                $results['skipped']++;
            }
            $results['items'][] = $item;
        }

        return $results;
    }

    public function calendar(int $year, int $month): array
    {
        $first = strtotime(sprintf('%04d-%02d-01 00:00:00', $year, $month));
        $daysInMonth = (int) date('t', $first);
        $startOffset = (int) date('w', $first);
        $eventsByDay = $this->eventsByDay($year, $month);
        $weeks = [];
        $week = array_fill(0, 7, null);

        for ($i = 0; $i < $startOffset; $i++) {
            $week[$i] = ['day' => null, 'events' => []];
        }

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $slot = ($startOffset + $day - 1) % 7;
            $dateKey = sprintf('%04d-%02d-%02d', $year, $month, $day);
            $week[$slot] = ['day' => $day, 'date' => $dateKey, 'events' => $eventsByDay[$dateKey] ?? []];

            if ($slot === 6 || $day === $daysInMonth) {
                for ($i = 0; $i < 7; $i++) {
                    if ($week[$i] === null) {
                        $week[$i] = ['day' => null, 'events' => []];
                    }
                }
                $weeks[] = $week;
                $week = array_fill(0, 7, null);
            }
        }

        return $weeks;
    }

    public function displayStatus(array $schedule): array
    {
        if (empty($schedule['is_active'])) {
            if (
                in_array((string) ($schedule['recurrence_mode'] ?? $schedule['frequency'] ?? ''), ['once'], true)
                && (int) ($schedule['run_count'] ?? 0) > 0
                && (int) ($schedule['next_run_at'] ?? 0) === 0
            ) {
                return ['label' => 'Completed', 'variant' => 'success'];
            }

            return ['label' => 'Paused', 'variant' => 'neutral'];
        }

        $lastStatus = strtolower(trim((string) ($schedule['last_status'] ?? '')));
        if ($lastStatus === '' || in_array($lastStatus, ['success', 'duplicate_retry'], true)) {
            return ['label' => 'Healthy', 'variant' => 'success'];
        }

        return ['label' => 'Needs Attention', 'variant' => 'danger'];
    }

    private function runSchedule(array $row, int $scheduledFor, array $options = []): array
    {
        $now = ee()->localize->now;
        $retryFailedRun = ! empty($options['retry_failed_run']);
        $topic = array_key_exists('planned_topic', $options)
            ? trim((string) ($options['planned_topic'] ?? ''))
            : $this->nextTopic($row);
        $topicSource = trim((string) ($options['topic_source'] ?? ($topic === '' ? 'fresh' : 'planned')));
        if ($topicSource === '') {
            $topicSource = $topic === '' ? 'fresh' : 'planned';
        }

        $runId = $this->createRunLog($row, [
            'scheduled_for' => $scheduledFor,
            'started_at' => $now,
            'topic_source' => $topicSource,
            'planned_topic' => $topic !== '' ? $topic : null,
            'status' => 'failed',
            'message' => $retryFailedRun ? 'Retry run started.' : 'Scheduled run started.',
        ]);

        try {
            $prompt = $topic !== '' ? $this->promptWithTopic((string) $row['prompt'], $topic) : (string) $row['prompt'];
            $gen = $this->generatorForTemplate((int) ($row['template_id'] ?? 0));
            $recentGenerations = $topicSource === 'fresh'
                ? $gen->recentGenerationMemory([
                    'template_id' => (int) ($row['template_id'] ?? 0),
                    'limit' => 18,
                ])
                : [];
            $result = $gen->generate($prompt, [
                'schedule_id' => (int) $row['id'],
                'scheduled_for' => $scheduledFor,
                'source' => 'scheduled',
                'template_id' => (int) ($row['template_id'] ?? 0),
                'recent_generations' => $recentGenerations,
                'max_topic_attempts' => $topicSource === 'fresh' ? 3 : 1,
                'topic_source' => $topicSource,
            ]);

            if (! empty($result['image_missing'])) {
                throw new \RuntimeException('Scheduled generation completed without a usable saved image.');
            }

            $duplicateRetryCount = (int) ($result['duplicate_retry_count'] ?? 0);
            $status = $duplicateRetryCount > 0 ? 'duplicate_retry' : 'success';
            $message = $retryFailedRun
                ? ($duplicateRetryCount > 0
                    ? 'Retry succeeded after ' . $duplicateRetryCount . ' duplicate ' . ($duplicateRetryCount === 1 ? 'retry.' : 'retries.')
                    : 'Retry succeeded.')
                : ($duplicateRetryCount > 0
                    ? 'Fresh topic accepted after ' . $duplicateRetryCount . ' duplicate ' . ($duplicateRetryCount === 1 ? 'retry.' : 'retries.')
                    : 'Scheduled generation completed successfully.');

            $update = [
                'last_run_at' => $now,
                'run_count' => (int) ($row['run_count'] ?? 0) + 1,
                'last_error' => null,
                'updated_at' => $now,
            ];
            $update += $this->scheduleAuditSummaryFields([
                'last_status' => $status,
                'last_message' => $message,
                'last_generation_table' => $gen->tableName(),
                'last_generation_id' => (int) ($result['id'] ?? 0),
                'last_scheduled_for' => $scheduledFor,
            ]);

            if (! $retryFailedRun) {
                $nextRunAt = $this->nextRunForSchedule($row, (int) ($row['next_run_at'] ?? $scheduledFor));
                if ($nextRunAt > 0) {
                    $update['next_run_at'] = $nextRunAt;
                } else {
                    $update['next_run_at'] = 0;
                    $update['is_active'] = 0;
                }
            }

            if ($topic !== '' && $topicSource === 'planned' && ! $retryFailedRun && ee()->db->field_exists('topic_cursor', 'socialposter_schedules')) {
                $update['topic_cursor'] = (int) ($row['topic_cursor'] ?? 0) + 1;
            }

            ee()->db->where('id', (int) $row['id'])->update('socialposter_schedules', $update);
            $this->finishRunLog($runId, [
                'finished_at' => ee()->localize->now,
                'status' => $status,
                'content_type' => $gen->contentType(),
                'content_record_id' => (int) ($result['id'] ?? 0),
                'final_title' => trim((string) ($result['title'] ?? '')),
                'duplicate_retry_count' => $duplicateRetryCount,
                'duplicate_match_title' => trim((string) ($result['duplicate_match_title'] ?? '')),
                'message' => $message,
            ]);

            return [
                'schedule_id' => (int) $row['id'],
                'generation_id' => (int) ($result['id'] ?? 0),
                'content_type' => $gen->contentType(),
                'ok' => true,
                'status' => $status,
                'message' => $message,
            ];
        } catch (\Throwable $e) {
            $status = $this->failureStatusForException($e);
            ee()->db->where('id', (int) $row['id'])->update('socialposter_schedules', [
                'last_error' => $e->getMessage(),
                'updated_at' => $now,
            ] + $this->scheduleAuditSummaryFields([
                'last_status' => $status,
                'last_message' => $e->getMessage(),
                'last_generation_table' => '',
                'last_generation_id' => 0,
                'last_scheduled_for' => $scheduledFor,
            ]));
            $this->finishRunLog($runId, [
                'finished_at' => ee()->localize->now,
                'status' => $status,
                'message' => $e->getMessage(),
            ]);

            return [
                'schedule_id' => (int) $row['id'],
                'ok' => false,
                'status' => $status,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function createAndFinishStandaloneRunLog(array $schedule, array $data): void
    {
        $runId = $this->createRunLog($schedule, [
            'scheduled_for' => (int) ($data['scheduled_for'] ?? 0),
            'started_at' => ee()->localize->now,
            'topic_source' => (string) ($data['topic_source'] ?? 'fresh'),
            'planned_topic' => (string) ($data['planned_topic'] ?? ''),
            'status' => (string) ($data['status'] ?? 'skipped'),
            'message' => (string) ($data['message'] ?? ''),
        ]);

        $this->finishRunLog($runId, [
            'finished_at' => ee()->localize->now,
            'status' => (string) ($data['status'] ?? 'skipped'),
            'message' => (string) ($data['message'] ?? ''),
        ]);
    }

    private function createRunLog(array $schedule, array $data): int
    {
        if (! ee()->db->table_exists(self::RUN_TABLE)) {
            return 0;
        }

        $record = [
            'site_id' => (int) ($schedule['site_id'] ?? ee()->config->item('site_id')),
            'schedule_id' => (int) ($schedule['id'] ?? 0),
            'template_id' => (int) ($schedule['template_id'] ?? 0),
            'scheduled_for' => (int) ($data['scheduled_for'] ?? 0),
            'started_at' => (int) ($data['started_at'] ?? ee()->localize->now),
            'finished_at' => 0,
            'status' => (string) ($data['status'] ?? 'failed'),
            'content_type' => '',
            'content_record_id' => 0,
            'topic_source' => (string) ($data['topic_source'] ?? 'fresh'),
            'planned_topic' => (string) ($data['planned_topic'] ?? ''),
            'final_title' => '',
            'duplicate_retry_count' => 0,
            'duplicate_match_title' => '',
            'message' => (string) ($data['message'] ?? ''),
            'created_at' => ee()->localize->now,
        ];

        ee()->db->insert(self::RUN_TABLE, $record);

        return (int) ee()->db->insert_id();
    }

    private function finishRunLog(int $runId, array $data): void
    {
        if ($runId < 1 || ! ee()->db->table_exists(self::RUN_TABLE)) {
            return;
        }

        ee()->db->where('id', $runId)->update(self::RUN_TABLE, [
            'finished_at' => (int) ($data['finished_at'] ?? ee()->localize->now),
            'status' => (string) ($data['status'] ?? 'failed'),
            'content_type' => (string) ($data['content_type'] ?? ''),
            'content_record_id' => (int) ($data['content_record_id'] ?? 0),
            'final_title' => (string) ($data['final_title'] ?? ''),
            'duplicate_retry_count' => (int) ($data['duplicate_retry_count'] ?? 0),
            'duplicate_match_title' => (string) ($data['duplicate_match_title'] ?? ''),
            'message' => (string) ($data['message'] ?? ''),
        ]);
    }

    private function failureStatusForException(\Throwable $e): string
    {
        $message = strtolower(trim($e->getMessage()));
        if ((int) $e->getCode() === 409 || strpos($message, 'landing too close to a recent post') !== false) {
            return 'duplicate_exhausted';
        }

        if (
            strpos($message, 'usable saved image') !== false
            || strpos($message, 'image file was not saved correctly') !== false
            || strpos($message, 'without a usable saved image') !== false
        ) {
            return 'image_failed';
        }

        return 'failed';
    }

    private function scheduleAuditSummaryFields(array $values): array
    {
        $fields = [];
        foreach ($values as $field => $value) {
            if (ee()->db->field_exists($field, 'socialposter_schedules')) {
                $fields[$field] = $value;
            }
        }

        return $fields;
    }

    private function eventsByDay(int $year, int $month): array
    {
        $start  = strtotime(sprintf('%04d-%02d-01 00:00:00', $year, $month));
        $end    = strtotime('+1 month', $start) - 1;
        $events = [];

        foreach ($this->schedules() as $schedule) {
            $run = (int) $schedule['next_run_at'];
            if ($run >= $start && $run <= $end) {
                $events[date('Y-m-d', $run)][] = [
                    'type'   => 'scheduled',
                    'kind'   => $this->scheduleKind((int) ($schedule['template_id'] ?? 0)),
                    'title'  => $this->calendarTitle($schedule),
                    'meta'   => $this->calendarScheduleMeta($schedule),
                    'time'   => date('g:ia', $run),
                    'active' => ! empty($schedule['is_active']),
                    'url'    => ee('CP/URL')->make('addons/settings/socialposter/calendar/' . (int) ($schedule['id'] ?? 0))->compile() . '#tab=t-1',
                ];
            }
        }

        // socialposter_generations — blog posts, news-mode IG, single-image IG
        if (ee()->db->table_exists('socialposter_generations')) {
            $rows = ee()->db
                ->where('created_at >=', $start)
                ->where('created_at <=', $end)
                ->order_by('created_at', 'ASC')
                ->get('socialposter_generations')
                ->result_array();

            foreach ($rows as $row) {
                $time = (int) $row['created_at'];
                $id   = (int) $row['id'];
                $events[date('Y-m-d', $time)][] = [
                    'type'   => 'generated',
                    'kind'   => $this->generationKind($row),
                    'title'  => $this->calendarPostTitle((string) ($row['title'] ?? 'Generated post'), $id),
                    'time'   => date('g:ia', $time),
                    'active' => true,
                    'url'    => ee('CP/URL')->make('addons/settings/socialposter/history/' . $id)->compile(),
                ];
            }
        }

        // socialposter_instagram_posts — older standalone IG posts table
        if (ee()->db->table_exists('socialposter_instagram_posts')) {
            $rows = ee()->db
                ->where('created_at >=', $start)
                ->where('created_at <=', $end)
                ->order_by('created_at', 'ASC')
                ->get('socialposter_instagram_posts')
                ->result_array();

            foreach ($rows as $row) {
                $time = (int) $row['created_at'];
                $id   = (int) $row['id'];
                $events[date('Y-m-d', $time)][] = [
                    'type'   => 'generated',
                    'kind'   => 'I',
                    'title'  => $this->calendarPostTitle((string) ($row['title'] ?? 'Instagram post'), $id),
                    'time'   => date('g:ia', $time),
                    'active' => true,
                    'url'    => ee('CP/URL')->make('addons/settings/socialposter/instagram/history/' . $id)->compile(),
                ];
            }
        }

        // socialposter_carousel_generations — Instagram carousels (no `title`, uses `topic`)
        if (ee()->db->table_exists('socialposter_carousel_generations')) {
            $rows = ee()->db
                ->where('created_at >=', $start)
                ->where('created_at <=', $end)
                ->order_by('created_at', 'ASC')
                ->get('socialposter_carousel_generations')
                ->result_array();

            foreach ($rows as $row) {
                $time = (int) $row['created_at'];
                $id   = (int) $row['id'];
                $events[date('Y-m-d', $time)][] = [
                    'type'   => 'generated',
                    'kind'   => 'IC',
                    'title'  => $this->calendarPostTitle((string) ($row['topic'] ?? 'Carousel'), $id),
                    'time'   => date('g:ia', $time),
                    'active' => true,
                    'url'    => ee('CP/URL')->make('addons/settings/socialposter/instagram/carousel/' . $id)->compile(),
                ];
            }
        }

        return $events;
    }

    private function generationKind(array $row): string
    {
        $source = strtolower(trim((string) ($row['source'] ?? '')));
        if ($source === 'news') {
            return 'IN';
        }

        $templateId = (int) ($row['template_id'] ?? 0);
        if ($templateId > 0 && $this->templateIsInstagram($templateId)) {
            return 'I';
        }

        // Single-image news/manual posts have no template but live as IG. Heuristic:
        // image-only with empty post_text fallback isn't reliable, so default to B.
        return 'B';
    }

    private function scheduleKind(int $templateId): string
    {
        return $templateId > 0 && $this->templateIsInstagram($templateId) ? 'I' : 'B';
    }

    private function templateIsInstagram(int $templateId): bool
    {
        static $platforms = null;
        if ($platforms === null) {
            $platforms = [];
            try {
                foreach (ee('socialposter:templates')->metaById() as $tid => $meta) {
                    $platforms[(int) $tid] = strtolower(trim((string) ($meta['platform'] ?? '')));
                }
            } catch (\Throwable $e) {
                $platforms = [];
            }
        }

        return ($platforms[$templateId] ?? '') === 'instagram';
    }

    private function generatorForTemplate(int $templateId): SocialPostGenerator
    {
        if ($templateId < 1) {
            return $this->generator;
        }

        $template = ee('socialposter:templates')->find($templateId);
        if (! $template) {
            return $this->generator;
        }

        $platform = strtolower(trim((string) ($template['platform'] ?? '')));
        return $platform === 'instagram' ? $this->instagramGenerator : $this->generator;
    }

    public function nextTopic(array $schedule): string
    {
        $topics = $this->topicsForSchedule($schedule);
        if (! $topics) {
            return '';
        }

        $cursor = max(0, (int) ($schedule['topic_cursor'] ?? 0));
        return (string) ($topics[$cursor] ?? '');
    }

    public function topicCount(array $schedule): int
    {
        return count($this->topicsForSchedule($schedule));
    }

    public function topicsUsed(array $schedule): int
    {
        return min(max(0, (int) ($schedule['topic_cursor'] ?? 0)), $this->topicCount($schedule));
    }

    private function calendarTitle(array $schedule): string
    {
        $topic = $this->nextTopic($schedule);
        if ($topic !== '') {
            return $topic;
        }

        return (string) ($schedule['title'] ?: 'Scheduled generation');
    }

    private function calendarScheduleMeta(array $schedule): string
    {
        $parts = [];

        $templateTitle = $this->scheduleTemplateTitle((int) ($schedule['template_id'] ?? 0));
        if ($templateTitle !== '') {
            $parts[] = $templateTitle;
        }

        $frequency = $this->frequencyLabel($schedule);
        if ($frequency !== '') {
            $parts[] = $frequency;
        }

        return implode(' · ', $parts);
    }

    private function scheduleTemplateTitle(int $templateId): string
    {
        if ($templateId < 1) {
            return '';
        }

        static $titles = null;
        if ($titles === null) {
            $titles = [];
            try {
                foreach (ee('socialposter:templates')->metaById() as $tid => $meta) {
                    $titles[(int) $tid] = trim((string) ($meta['title'] ?? ''));
                }
            } catch (\Throwable $e) {
                $titles = [];
            }
        }

        return (string) ($titles[$templateId] ?? '');
    }

    private function calendarPostTitle(string $title, int $id): string
    {
        $title = trim(preg_replace('/\s+/', ' ', $title) ?: '');
        if (strlen($title) > 18) {
            $title = rtrim(substr($title, 0, 18)) . '...';
        }

        return '#' . $id . ' ' . ($title !== '' ? $title : 'Generated post');
    }

    private function promptWithTopic(string $prompt, string $topic): string
    {
        if ($topic === '') {
            return $prompt;
        }

        return trim($prompt . "\n\nPlanned topic for this scheduled run:\n" . $topic . "\n\nUse this planned topic as the main article topic. Do not choose a different topic.");
    }

    private function topicsForSchedule(array $schedule): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/', (string) ($schedule['planned_topics'] ?? '')) ?: [])));
    }

    private function normalizeTopicText(string $value): string
    {
        return implode("\n", array_values(array_unique(array_filter(array_map('trim', preg_split('/\R/', $value) ?: [])))));
    }

    private function recurrenceConfig(array $schedule): array
    {
        $mode = trim((string) ($schedule['recurrence_mode'] ?? ''));
        $interval = max(1, (int) ($schedule['recurrence_interval'] ?? 0));
        $unit = trim((string) ($schedule['recurrence_unit'] ?? ''));
        $weekdays = $this->normalizeWeekdays($schedule['weekdays'] ?? '');

        if ($mode !== '' && in_array($mode, array_keys($this->recurrenceModes()), true) && in_array($unit, array_keys($this->recurrenceUnits()), true)) {
            if ($unit === 'week' && ! $weekdays && $mode === 'repeat') {
                $weekdays = [(int) date('w', (int) ($schedule['anchor_at'] ?? $schedule['next_run_at'] ?? time()))];
            }

            return [
                'recurrence_mode' => $mode,
                'recurrence_interval' => $interval,
                'recurrence_unit' => $unit,
                'weekdays' => $weekdays,
            ];
        }

        return $this->legacyRecurrenceConfig($schedule);
    }

    private function legacyRecurrenceConfig(array $schedule): array
    {
        $frequency = strtolower(trim((string) ($schedule['frequency'] ?? 'weekly')));
        $weekday = (int) date('w', (int) ($schedule['next_run_at'] ?? time()));

        return match ($frequency) {
            'daily' => ['recurrence_mode' => 'repeat', 'recurrence_interval' => 1, 'recurrence_unit' => 'day', 'weekdays' => []],
            'every_other_day' => ['recurrence_mode' => 'repeat', 'recurrence_interval' => 2, 'recurrence_unit' => 'day', 'weekdays' => []],
            'every_3_days' => ['recurrence_mode' => 'repeat', 'recurrence_interval' => 3, 'recurrence_unit' => 'day', 'weekdays' => []],
            'weekdays' => ['recurrence_mode' => 'repeat', 'recurrence_interval' => 1, 'recurrence_unit' => 'week', 'weekdays' => [1, 2, 3, 4, 5]],
            'biweekly' => ['recurrence_mode' => 'repeat', 'recurrence_interval' => 2, 'recurrence_unit' => 'week', 'weekdays' => [$weekday]],
            'monthly' => ['recurrence_mode' => 'repeat', 'recurrence_interval' => 1, 'recurrence_unit' => 'month', 'weekdays' => []],
            'once' => ['recurrence_mode' => 'once', 'recurrence_interval' => 1, 'recurrence_unit' => 'day', 'weekdays' => []],
            default => ['recurrence_mode' => 'repeat', 'recurrence_interval' => 1, 'recurrence_unit' => 'week', 'weekdays' => [$weekday]],
        };
    }

    private function normalizeRecurrenceInput(array $input, int $anchorAt): array
    {
        $mode = trim((string) ($input['recurrence_mode'] ?? 'repeat'));
        if (! array_key_exists($mode, $this->recurrenceModes())) {
            $mode = 'repeat';
        }

        $interval = max(1, (int) ($input['recurrence_interval'] ?? 1));
        $unit = trim((string) ($input['recurrence_unit'] ?? 'week'));
        if (! array_key_exists($unit, $this->recurrenceUnits())) {
            $unit = 'week';
        }

        $weekdays = $this->normalizeWeekdays($input['weekdays'] ?? []);
        if ($mode === 'repeat' && $unit === 'week' && ! $weekdays) {
            $weekdays = [(int) date('w', $anchorAt)];
        }

        return [
            'recurrence_mode' => $mode,
            'recurrence_interval' => $interval,
            'recurrence_unit' => $unit,
            'weekdays' => implode(',', $weekdays),
            'anchor_at' => $anchorAt,
        ];
    }

    private function normalizeWeekdays($value): array
    {
        $items = is_array($value) ? $value : explode(',', (string) $value);
        $days = array_values(array_unique(array_filter(array_map(function ($item) {
            if ($item === '' || $item === null) {
                return null;
            }

            $day = (int) $item;
            return $day >= 0 && $day <= 6 ? $day : null;
        }, $items), fn($item) => $item !== null)));

        sort($days);
        return $days;
    }

    private function parseStartAt(string $date, string $time): int
    {
        $date = trim($date) ?: date('Y-m-d');
        $time = preg_match('/^\d{2}:\d{2}$/', $time) ? $time : '09:00';
        $stamp = strtotime($date . ' ' . $time);
        return $stamp ?: ee()->localize->now;
    }

    private function firstRunAtForRecurrence(int $anchorAt, array $recurrence): int
    {
        if (($recurrence['recurrence_mode'] ?? 'repeat') === 'once') {
            return $anchorAt;
        }

        if (($recurrence['recurrence_unit'] ?? 'week') !== 'week') {
            return $anchorAt;
        }

        $weekdays = $this->normalizeWeekdays($recurrence['weekdays'] ?? '');
        if (! $weekdays) {
            return $anchorAt;
        }

        $time = date('H:i:s', $anchorAt);
        $startDay = strtotime(date('Y-m-d 00:00:00', $anchorAt));
        for ($i = 0; $i <= 6; $i++) {
            $day = strtotime('+' . $i . ' days', $startDay);
            if ($day === false || ! in_array((int) date('w', $day), $weekdays, true)) {
                continue;
            }

            $candidate = strtotime(date('Y-m-d', $day) . ' ' . $time);
            if ($candidate !== false && $candidate >= $anchorAt) {
                return $candidate;
            }
        }

        return $anchorAt;
    }

    private function promptFromTemplate(array $template): string
    {
        // The template's goal, audience, instructions and structure are added at
        // generation time from the template itself, so schedules never carry a
        // stale copy of them.
        return 'Generate a fresh content package using the selected SocialPoster template.';
    }

    private function legacyFrequencyFromRecurrence(array $recurrence): string
    {
        if (($recurrence['recurrence_mode'] ?? 'repeat') === 'once') {
            return 'once';
        }

        $interval = max(1, (int) ($recurrence['recurrence_interval'] ?? 1));
        $unit = (string) ($recurrence['recurrence_unit'] ?? 'week');
        $weekdays = $this->normalizeWeekdays($recurrence['weekdays'] ?? '');

        return match ($unit) {
            'day' => $interval === 1 ? 'daily' : ($interval === 2 ? 'every_other_day' : ($interval === 3 ? 'every_3_days' : 'daily')),
            'week' => ($interval === 1 && $weekdays === [1, 2, 3, 4, 5]) ? 'weekdays' : ($interval === 2 ? 'biweekly' : 'weekly'),
            'month' => 'monthly',
            default => 'weekly',
        };
    }

    private function nextRunForSchedule(array $schedule, int $from): int
    {
        $config = $this->recurrenceConfig($schedule);
        if ($config['recurrence_mode'] === 'once') {
            return 0;
        }

        $interval = max(1, (int) $config['recurrence_interval']);
        $unit = (string) $config['recurrence_unit'];
        $anchorAt = (int) ($schedule['anchor_at'] ?? $schedule['next_run_at'] ?? $from);
        $anchorAt = $anchorAt > 0 ? $anchorAt : $from;

        if ($unit === 'day') {
            return strtotime('+' . $interval . ' days', $from) ?: 0;
        }

        if ($unit === 'month') {
            return strtotime('+' . $interval . ' months', $from) ?: 0;
        }

        return $this->nextWeeklyRun($anchorAt, $from, $interval, $config['weekdays']);
    }

    private function nextWeeklyRun(int $anchorAt, int $from, int $intervalWeeks, array $weekdays): int
    {
        $weekdays = $weekdays ?: [(int) date('w', $anchorAt)];
        $time = date('H:i:s', $anchorAt);
        $anchorWeekStart = strtotime('last sunday', strtotime(date('Y-m-d 00:00:00', $anchorAt)));
        if ((int) date('w', $anchorAt) === 0) {
            $anchorWeekStart = strtotime(date('Y-m-d 00:00:00', $anchorAt));
        }

        $startDay = strtotime(date('Y-m-d 00:00:00', $from));
        for ($i = 1; $i <= 366 * 5; $i++) {
            $day = strtotime('+' . $i . ' days', $startDay);
            if ($day === false) {
                continue;
            }

            $dow = (int) date('w', $day);
            if (! in_array($dow, $weekdays, true)) {
                continue;
            }

            $candidate = strtotime(date('Y-m-d', $day) . ' ' . $time);
            if ($candidate === false || $candidate <= $from) {
                continue;
            }

            $candidateWeekStart = strtotime('last sunday', strtotime(date('Y-m-d 00:00:00', $candidate)));
            if ((int) date('w', $candidate) === 0) {
                $candidateWeekStart = strtotime(date('Y-m-d 00:00:00', $candidate));
            }

            $weeksBetween = (int) floor(($candidateWeekStart - $anchorWeekStart) / 604800);
            if ($weeksBetween >= 0 && $weeksBetween % $intervalWeeks === 0) {
                return $candidate;
            }
        }

        return strtotime('+' . $intervalWeeks . ' weeks', $from) ?: 0;
    }
}
