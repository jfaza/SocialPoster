<?php

namespace JavidFazaeli\SocialPoster\ControlPanel\Routes;

use ExpressionEngine\Service\Addon\Controllers\Mcp\AbstractRoute;

class Calendar extends AbstractRoute
{
    use LoadsStyle;
    use BuildsRouteContext;

    /**
     * @var string
     */
    protected $route_path = 'calendar';

    /**
     * @var string
     */
    protected $cp_page_title = 'SocialPoster Calendar';

    /**
     * @param false $id
     * @return AbstractRoute
     */
    public function process($id = false)
    {
        $this->addBreadcrumb('index', 'SocialPoster');
        $this->addBreadcrumb('calendar', 'Calendar');
        $this->loadStyle();

        $scheduler = ee('socialposter:scheduler');
        $coreUrls = $this->coreRouteContext();
        $instagramUrls = $this->instagramRouteContext();
        $id = (int) $id;
        $editing = $id > 0 ? $scheduler->find($id) : null;

        if ($id > 0 && ! $editing) {
            ee('CP/Alert')->makeBanner('socialposter-calendar')
                ->asIssue()
                ->withTitle('Schedule not found')
                ->addToBody('That schedule does not exist.')
                ->defer();
            ee()->functions->redirect(ee('CP/URL')->make('addons/settings/socialposter/calendar'));
        }

        if (ee('Request')->post('save_schedule') || ee('Request')->post('create_schedule')) {
            try {
                $scheduleInput = [
                    'title' => ee()->input->post('title', true),
                    'frequency' => ee()->input->post('frequency', true),
                    'start_date' => ee()->input->post('start_date', true),
                    'start_time' => ee()->input->post('start_time', true),
                    'template_id' => ee()->input->post('template_id', true),
                    'planned_topics' => ee()->input->post('planned_topics', false),
                    'recurrence_mode' => ee()->input->post('recurrence_mode', true),
                    'recurrence_interval' => ee()->input->post('recurrence_interval', true),
                    'recurrence_unit' => ee()->input->post('recurrence_unit', true),
                    'weekdays' => (array) (ee()->input->post('weekdays') ?: []),
                ];

                $scheduleId = (int) ee()->input->post('schedule_id', true);
                if ($scheduleId > 0) {
                    $scheduler->update($scheduleId, $scheduleInput);
                    $alertTitle = 'Schedule updated';
                    $alertBody = 'Schedule settings are ready for the next run.';
                } else {
                    $scheduler->create($scheduleInput);
                    $alertTitle = 'Schedule created';
                    $alertBody = 'SocialPoster will generate this content on schedule.';
                }

                ee('CP/Alert')->makeBanner('socialposter-calendar')
                    ->asSuccess()
                    ->withTitle($alertTitle)
                    ->addToBody($alertBody)
                    ->defer();
                ee()->functions->redirect(ee('CP/URL')->make('addons/settings/socialposter/calendar'));
            } catch (\Throwable $e) {
                ee('CP/Alert')->makeBanner('socialposter-calendar')
                    ->asIssue()
                    ->withTitle($id > 0 ? 'Schedule was not updated' : 'Schedule was not created')
                    ->addToBody($e->getMessage())
                    ->now();
            }
        }

        if (ee('Request')->post('delete_schedule')) {
            $scheduler->delete((int) ee()->input->post('delete_schedule'));
            ee()->functions->redirect(ee('CP/URL')->make('addons/settings/socialposter/calendar'));
        }

        if (ee('Request')->post('toggle_schedule')) {
            $scheduler->toggle((int) ee()->input->post('toggle_schedule'));
            ee()->functions->redirect(ee('CP/URL')->make('addons/settings/socialposter/calendar'));
        }

        if (ee('Request')->post('run_schedule_now')) {
            try {
                $result = $scheduler->runNow((int) ee()->input->post('run_schedule_now'));
                $alert = ee('CP/Alert')->makeBanner('socialposter-calendar');
                if (! empty($result['ok'])) {
                    $alert->asSuccess()
                        ->withTitle('Schedule ran now')
                        ->addToBody((string) ($result['message'] ?? 'The schedule ran successfully.'));
                } else {
                    $alert->asIssue()
                        ->withTitle('Schedule did not run')
                        ->addToBody((string) ($result['error'] ?? 'The schedule run failed.'));
                }
                $alert->defer();
            } catch (\Throwable $e) {
                ee('CP/Alert')->makeBanner('socialposter-calendar')
                    ->asIssue()
                    ->withTitle('Schedule did not run')
                    ->addToBody($e->getMessage())
                    ->defer();
            }
            ee()->functions->redirect(ee('CP/URL')->make('addons/settings/socialposter/calendar'));
        }

        if (ee('Request')->post('retry_failed_schedule')) {
            try {
                $result = $scheduler->retryLastFailedRun((int) ee()->input->post('retry_failed_schedule'));
                $alert = ee('CP/Alert')->makeBanner('socialposter-calendar');
                if (! empty($result['ok'])) {
                    $alert->asSuccess()
                        ->withTitle('Failed run retried')
                        ->addToBody((string) ($result['message'] ?? 'The failed run was retried successfully.'));
                } else {
                    $alert->asIssue()
                        ->withTitle('Failed run was not retried')
                        ->addToBody((string) ($result['error'] ?? 'The retry failed.'));
                }
                $alert->defer();
            } catch (\Throwable $e) {
                ee('CP/Alert')->makeBanner('socialposter-calendar')
                    ->asIssue()
                    ->withTitle('Failed run was not retried')
                    ->addToBody($e->getMessage())
                    ->defer();
            }
            ee()->functions->redirect(ee('CP/URL')->make('addons/settings/socialposter/calendar'));
        }

        if (ee('Request')->post('skip_schedule_topic')) {
            try {
                $scheduler->skipCurrentTopic((int) ee()->input->post('skip_schedule_topic'));
                ee('CP/Alert')->makeBanner('socialposter-calendar')
                    ->asSuccess()
                    ->withTitle('Planned topic skipped')
                    ->addToBody('The queued topic was skipped and the schedule will move to the next one.')
                    ->defer();
            } catch (\Throwable $e) {
                ee('CP/Alert')->makeBanner('socialposter-calendar')
                    ->asIssue()
                    ->withTitle('Topic was not skipped')
                    ->addToBody($e->getMessage())
                    ->defer();
            }
            ee()->functions->redirect(ee('CP/URL')->make('addons/settings/socialposter/calendar'));
        }

        if (ee('Request')->post('run_due')) {
            $result = $scheduler->runDue(5);
            $summary = $result['generated'] . ' generated, ' . $result['failed'] . ' failed.';
            if ((int) ($result['duplicate_protected'] ?? 0) > 0) {
                $summary .= ' ' . (int) $result['duplicate_protected'] . ' protected by duplicate retry.';
            }
            if ((int) ($result['skipped'] ?? 0) > 0) {
                $summary .= ' ' . (int) $result['skipped'] . ' skipped.';
            }
            ee('CP/Alert')->makeBanner('socialposter-calendar')
                ->asSuccess()
                ->withTitle('Scheduler run complete')
                ->addToBody($summary)
                ->defer();
            ee()->functions->redirect(ee('CP/URL')->make('addons/settings/socialposter/calendar'));
        }

        $year = (int) (ee()->input->get('year', true) ?: date('Y'));
        $month = max(1, min(12, (int) (ee()->input->get('month', true) ?: date('n'))));
        $scheduleFilter = $this->normalizeScheduleFilter((string) ee()->input->get('schedule_filter', true));
        $current = strtotime(sprintf('%04d-%02d-01', $year, $month));
        $prev = strtotime('-1 month', $current);
        $next = strtotime('+1 month', $current);

        $templates = ee('socialposter:templates');

        $this->setBody('Calendar', [
            'calendar_url' => $coreUrls['calendar_url'],
            'calendar_item_url_pattern' => $this->cpUrl('calendar/__ID__'),
            'index_url' => $coreUrls['index_url'],
            'history_url' => $coreUrls['history_url'],
            'published_url' => $coreUrls['published_url'],
            'templates_url' => $coreUrls['templates_url'],
            'save_url' => ee('CP/URL')->make('addons/settings/socialposter/calendar' . ($id > 0 ? '/' . $id : ''))->compile(),
            'month_label' => date('F Y', $current),
            'schedule_filter' => $scheduleFilter,
            'prev_url' => ee('CP/URL')->make('addons/settings/socialposter/calendar', ['year' => date('Y', $prev), 'month' => date('n', $prev)])->compile(),
            'next_url' => ee('CP/URL')->make('addons/settings/socialposter/calendar', ['year' => date('Y', $next), 'month' => date('n', $next)])->compile(),
            'weeks' => $scheduler->calendar($year, $month),
            'schedules' => $this->scheduleRows($scheduler, $coreUrls, $instagramUrls, $scheduleFilter),
            'schedule_filter_counts' => $this->scheduleFilterCounts($scheduler),
            'frequencies' => $scheduler->frequencies(),
            'recurrence_modes' => $scheduler->recurrenceModes(),
            'recurrence_units' => $scheduler->recurrenceUnits(),
            'weekday_options' => $scheduler->weekdayOptions(),
            'template_options' => $templates->options(false),
            'template_preview' => $this->templatePreviewData($templates),
            'template_starters' => $templates->starterPresetsById(),
            'schedule_form' => $scheduler->formValues($editing),
            'editing_schedule' => $editing,
            'editor_active' => $id > 0,
            'recent_runs' => $editing ? $this->decorateRuns($scheduler->recentRunsForSchedule($id, 5), $coreUrls, $instagramUrls) : [],
        ]);

        return $this;
    }

    private function scheduleRows($scheduler, array $coreUrls, array $instagramUrls, string $scheduleFilter): array
    {
        $rows = $scheduler->schedules();
        $latestRuns = $scheduler->latestRunsBySchedule(array_map(fn(array $schedule) => (int) ($schedule['id'] ?? 0), $rows));

        $rows = array_map(function ($schedule) use ($scheduler, $latestRuns, $coreUrls, $instagramUrls) {
            $schedule['next_topic'] = $scheduler->nextTopic($schedule);
            $schedule['topic_count'] = $scheduler->topicCount($schedule);
            $schedule['topics_used'] = $scheduler->topicsUsed($schedule);
            $schedule['frequency_label'] = $scheduler->frequencyLabel($schedule);
            $schedule['status_display'] = $scheduler->displayStatus($schedule);
            $schedule['status_key'] = $this->scheduleStatusKey($schedule, $schedule['status_display']);
            $schedule['latest_run'] = $latestRuns[(int) ($schedule['id'] ?? 0)] ?? null;
            if ($schedule['latest_run']) {
                $schedule['latest_run_link'] = $this->runLinkData($schedule['latest_run'], $coreUrls, $instagramUrls);
            }
            return $schedule;
        }, $rows);

        if ($scheduleFilter !== 'all') {
            $rows = array_values(array_filter($rows, fn(array $schedule) => (string) ($schedule['status_key'] ?? 'all') === $scheduleFilter));
        }

        return $rows;
    }

    private function scheduleFilterCounts($scheduler): array
    {
        $counts = [
            'all' => 0,
            'needs_attention' => 0,
            'healthy' => 0,
            'paused' => 0,
            'completed' => 0,
        ];

        foreach ($scheduler->schedules() as $schedule) {
            $statusDisplay = $scheduler->displayStatus($schedule);
            $statusKey = $this->scheduleStatusKey($schedule, $statusDisplay);
            $counts['all']++;
            if (isset($counts[$statusKey])) {
                $counts[$statusKey]++;
            }
        }

        return $counts;
    }

    private function decorateRuns(array $runs, array $coreUrls, array $instagramUrls): array
    {
        return array_map(function ($run) use ($coreUrls, $instagramUrls) {
            $run['link'] = $this->runLinkData($run, $coreUrls, $instagramUrls);
            return $run;
        }, $runs);
    }

    private function runLinkData(array $run, array $coreUrls, array $instagramUrls): ?array
    {
        $contentType = trim((string) ($run['content_type'] ?? ''));
        $contentRecordId = (int) ($run['content_record_id'] ?? 0);
        if ($contentRecordId < 1) {
            return null;
        }

        if ($contentType === 'instagram') {
            return [
                'url' => rtrim((string) $instagramUrls['instagram_history_url'], '/') . '/' . $contentRecordId,
                'label' => 'Open Instagram Draft',
            ];
        }

        if ($contentType === 'website') {
            return [
                'url' => rtrim((string) $coreUrls['history_url'], '/') . '/' . $contentRecordId,
                'label' => 'Open Draft',
            ];
        }

        return null;
    }

    private function normalizeScheduleFilter(string $value): string
    {
        $value = strtolower(trim($value));
        return in_array($value, ['all', 'needs_attention', 'healthy', 'paused', 'completed'], true)
            ? $value
            : 'all';
    }

    private function scheduleStatusKey(array $schedule, array $statusDisplay): string
    {
        $label = strtolower(trim((string) ($statusDisplay['label'] ?? '')));
        return match ($label) {
            'needs attention' => 'needs_attention',
            'healthy' => 'healthy',
            'paused' => 'paused',
            'completed' => 'completed',
            default => 'all',
        };
    }

    private function templatePreviewData($templates): array
    {
        $previews = [];
        $fieldOptions = $templates->fieldOptions();

        foreach ($templates->all() as $template) {
            $id = (int) ($template['id'] ?? 0);
            if ($id < 1) {
                continue;
            }

            $previews[$id] = [
                'edit_url' => ee('CP/URL')->make('addons/settings/socialposter/templates/' . $id)->compile() . '#tab=t-0',
                'title' => (string) ($template['title'] ?? ''),
                'content_type' => $this->optionLabel($fieldOptions, 'content_type', (string) ($template['content_type'] ?? '')),
                'platform' => $this->optionLabel($fieldOptions, 'platform', (string) ($template['platform'] ?? '')),
                'length_preset' => $this->optionLabel($fieldOptions, 'length_preset', (string) ($template['length_preset'] ?? '')),
                'word_count' => $this->optionLabel($fieldOptions, 'word_count', (string) ((int) ($template['word_count'] ?? 0))),
                'citation_count' => $this->optionLabel($fieldOptions, 'citation_count', (string) ((int) ($template['citation_count'] ?? 0))),
                'schema_type' => $this->optionLabel($fieldOptions, 'schema_type', (string) ($template['schema_type'] ?? '')),
                'internal_link_count' => $this->optionLabel($fieldOptions, 'internal_link_count', (string) ((int) ($template['internal_link_count'] ?? 0))),
                'external_link_count' => $this->optionLabel($fieldOptions, 'external_link_count', (string) ((int) ($template['external_link_count'] ?? 0))),
                'is_default' => ! empty($template['is_default']) ? 'Use as default' : 'No',
                'tone' => $this->optionLabel($fieldOptions, 'tone', (string) ($template['tone'] ?? '')),
                'audience' => $this->optionLabel($fieldOptions, 'audience', (string) ($template['audience'] ?? '')),
                'goal' => $this->optionLabel($fieldOptions, 'goal', (string) ($template['goal'] ?? '')),
                'research_mode' => $this->optionLabel($fieldOptions, 'research_mode', (string) ($template['research_mode'] ?? '')),
                'image_style' => $this->optionLabel($fieldOptions, 'image_style', (string) ($template['image_style'] ?? '')),
                'cta_style' => $this->optionLabel($fieldOptions, 'cta_style', (string) ($template['cta_style'] ?? '')),
                'prompt_instructions' => (string) ($template['prompt_instructions'] ?? ''),
            ];
        }

        return $previews;
    }

    private function optionLabel(array $fieldOptions, string $field, string $value): string
    {
        if ($value === '') {
            return '';
        }

        return (string) ($fieldOptions[$field][$value] ?? $value);
    }
}
