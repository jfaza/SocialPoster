<?php

namespace JavidFazaeli\SocialPoster\ControlPanel\Routes;

use ExpressionEngine\Service\Addon\Controllers\Mcp\AbstractRoute;

class Calendar extends AbstractRoute
{
    use LoadsStyle;

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

        if (ee('Request')->post('create_schedule')) {
            try {
                $scheduler->create([
                    'title' => ee()->input->post('title', true),
                    'frequency' => ee()->input->post('frequency', true),
                    'start_date' => ee()->input->post('start_date', true),
                    'start_time' => ee()->input->post('start_time', true),
                    'template_id' => ee()->input->post('template_id', true),
                    'planned_topics' => ee()->input->post('planned_topics', false),
                ]);

                ee('CP/Alert')->makeBanner('socialposter-calendar')
                    ->asSuccess()
                    ->withTitle('Schedule created')
                    ->addToBody('SocialPoster will generate this content on schedule.')
                    ->defer();
                ee()->functions->redirect(ee('CP/URL')->make('addons/settings/socialposter/calendar'));
            } catch (\Throwable $e) {
                ee('CP/Alert')->makeBanner('socialposter-calendar')
                    ->asIssue()
                    ->withTitle('Schedule was not created')
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

        if (ee('Request')->post('run_due')) {
            $result = $scheduler->runDue(5);
            ee('CP/Alert')->makeBanner('socialposter-calendar')
                ->asSuccess()
                ->withTitle('Scheduler run complete')
                ->addToBody($result['generated'] . ' generated, ' . $result['failed'] . ' failed.')
                ->defer();
            ee()->functions->redirect(ee('CP/URL')->make('addons/settings/socialposter/calendar'));
        }

        $year = (int) (ee()->input->get('year', true) ?: date('Y'));
        $month = max(1, min(12, (int) (ee()->input->get('month', true) ?: date('n'))));
        $current = strtotime(sprintf('%04d-%02d-01', $year, $month));
        $prev = strtotime('-1 month', $current);
        $next = strtotime('+1 month', $current);

        $this->setBody('Calendar', [
            'calendar_url' => ee('CP/URL')->make('addons/settings/socialposter/calendar')->compile(),
            'history_url' => ee('CP/URL')->make('addons/settings/socialposter/history')->compile(),
            'settings_url' => ee('CP/URL')->make('addons/settings/socialposter/settings')->compile(),
            'templates_url' => ee('CP/URL')->make('addons/settings/socialposter/templates')->compile(),
            'month_label' => date('F Y', $current),
            'prev_url' => ee('CP/URL')->make('addons/settings/socialposter/calendar', ['year' => date('Y', $prev), 'month' => date('n', $prev)])->compile(),
            'next_url' => ee('CP/URL')->make('addons/settings/socialposter/calendar', ['year' => date('Y', $next), 'month' => date('n', $next)])->compile(),
            'weeks' => $scheduler->calendar($year, $month),
            'schedules' => $this->scheduleRows($scheduler),
            'frequencies' => $scheduler->frequencies(),
            'template_options' => ee('socialposter:templates')->options(false),
        ]);

        return $this;
    }

    private function scheduleRows($scheduler): array
    {
        return array_map(function ($schedule) use ($scheduler) {
            $schedule['next_topic'] = $scheduler->nextTopic($schedule);
            $schedule['topic_count'] = $scheduler->topicCount($schedule);
            $schedule['topics_used'] = $scheduler->topicsUsed($schedule);
            return $schedule;
        }, $scheduler->schedules());
    }
}
