<?php

namespace JavidFazaeli\SocialPoster\Service;

class Scheduler
{
    private SocialPostGenerator $generator;

    public function __construct(?SocialPostGenerator $generator = null)
    {
        $this->generator = $generator ?: new SocialPostGenerator();
    }

    public function frequencies(): array
    {
        return [
            'daily' => 'Daily',
            'weekdays' => 'Weekdays',
            'weekly' => 'Weekly',
            'biweekly' => 'Biweekly',
            'monthly' => 'Monthly',
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

        $frequency = (string) ($input['frequency'] ?? 'weekly');
        if (! array_key_exists($frequency, $this->frequencies())) {
            $frequency = 'weekly';
        }

        $startAt = $this->parseStartAt((string) ($input['start_date'] ?? ''), (string) ($input['start_time'] ?? '09:00'));
        $now = ee()->localize->now;

        $record = [
            'site_id' => (int) ee()->config->item('site_id'),
            'member_id' => (int) ee()->session->userdata('member_id'),
            'title' => trim((string) ($input['title'] ?? '')) ?: (string) $template['title'],
            'prompt' => $this->promptFromTemplate($template),
            'frequency' => $frequency,
            'is_active' => 1,
            'next_run_at' => $startAt,
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

        ee()->db->insert('socialposter_schedules', $record);

        return (int) ee()->db->insert_id();
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

        $results = ['generated' => 0, 'failed' => 0, 'items' => []];

        foreach ($rows as $row) {
            try {
                $topic = $this->nextTopic($row);
                $prompt = $this->promptWithTopic((string) $row['prompt'], $topic);

                $result = $this->generator->generate($prompt, [
                    'schedule_id' => (int) $row['id'],
                    'scheduled_for' => (int) $row['next_run_at'],
                    'source' => 'scheduled',
                    'template_id' => (int) ($row['template_id'] ?? 0),
                ]);

                $update = [
                    'last_run_at' => $now,
                    'next_run_at' => $this->nextRun((int) $row['next_run_at'], (string) $row['frequency']),
                    'run_count' => (int) $row['run_count'] + 1,
                    'last_error' => null,
                    'updated_at' => $now,
                ];

                if ($topic !== '' && ee()->db->field_exists('topic_cursor', 'socialposter_schedules')) {
                    $update['topic_cursor'] = (int) ($row['topic_cursor'] ?? 0) + 1;
                }

                ee()->db->where('id', (int) $row['id'])->update('socialposter_schedules', $update);

                $results['generated']++;
                $results['items'][] = ['schedule_id' => (int) $row['id'], 'generation_id' => (int) $result['id'], 'ok' => true];
            } catch (\Throwable $e) {
                ee()->db->where('id', (int) $row['id'])->update('socialposter_schedules', [
                    'last_error' => $e->getMessage(),
                    'updated_at' => $now,
                ]);

                $results['failed']++;
                $results['items'][] = ['schedule_id' => (int) $row['id'], 'ok' => false, 'error' => $e->getMessage()];
            }
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

    private function eventsByDay(int $year, int $month): array
    {
        $start = strtotime(sprintf('%04d-%02d-01 00:00:00', $year, $month));
        $end = strtotime('+1 month', $start) - 1;
        $events = [];

        foreach ($this->schedules() as $schedule) {
            $run = (int) $schedule['next_run_at'];
            if ($run >= $start && $run <= $end) {
                $events[date('Y-m-d', $run)][] = [
                    'type' => 'scheduled',
                    'title' => $this->calendarTitle($schedule),
                    'time' => date('g:ia', $run),
                    'active' => ! empty($schedule['is_active']),
                    'url' => '',
                ];
            }
        }

        if (ee()->db->table_exists('socialposter_generations')) {
            $rows = ee()->db
                ->where('created_at >=', $start)
                ->where('created_at <=', $end)
                ->order_by('created_at', 'ASC')
                ->get('socialposter_generations')
                ->result_array();

            foreach ($rows as $row) {
                $time = (int) $row['created_at'];
                $events[date('Y-m-d', $time)][] = [
                    'type' => 'generated',
                    'title' => $row['title'] ?: 'Generated post',
                    'time' => date('g:ia', $time),
                    'active' => true,
                    'url' => ee('CP/URL')->make('addons/settings/socialposter/history/' . (int) $row['id'])->compile(),
                ];
            }
        }

        return $events;
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

    private function parseStartAt(string $date, string $time): int
    {
        $date = trim($date) ?: date('Y-m-d');
        $time = preg_match('/^\d{2}:\d{2}$/', $time) ? $time : '09:00';
        $stamp = strtotime($date . ' ' . $time);
        return $stamp ?: ee()->localize->now;
    }

    private function promptFromTemplate(array $template): string
    {
        $parts = [
            'Generate a fresh content package using the selected SocialPoster template.',
            'Template: ' . (string) ($template['title'] ?? ''),
            'Goal: ' . (string) ($template['goal'] ?? ''),
            'Audience: ' . (string) ($template['audience'] ?? ''),
            'Platform: ' . (string) ($template['platform'] ?? ''),
            'Instructions: ' . (string) ($template['prompt_instructions'] ?? ''),
            'Article structure: ' . ee('socialposter:templates')->articleStructureFor($template),
            'Article body requirements: post_text must be the full publishable body in Markdown. Include one ## heading for each table_of_contents item, using the exact same heading text. Include actionable detail under each heading, not just a paragraph summary.',
            'Internal link requirements: use only relative internal URLs beginning with /, or exact site URLs supplied by the user. Do not invent placeholder domains.',
        ];

        return implode("\n", array_filter($parts, fn($part) => trim($part) !== ''));
    }

    private function nextRun(int $from, string $frequency): int
    {
        $next = match ($frequency) {
            'daily' => strtotime('+1 day', $from),
            'weekdays' => strtotime('+1 weekday', $from),
            'biweekly' => strtotime('+2 weeks', $from),
            'monthly' => strtotime('+1 month', $from),
            default => strtotime('+1 week', $from),
        };

        return $next ?: strtotime('+1 week', $from);
    }
}
