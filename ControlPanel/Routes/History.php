<?php

namespace JavidFazaeli\SocialPoster\ControlPanel\Routes;

use ExpressionEngine\Service\Addon\Controllers\Mcp\AbstractRoute;

class History extends AbstractRoute
{
    use LoadsStyle;
    use BuildsRouteContext;

    /**
     * @var string
     */
    protected $route_path = 'history';

    /**
     * @var string
     */
    protected $cp_page_title = 'SocialPoster History';

    /**
     * @param false $id
     * @return AbstractRoute
     */
    public function process($id = false)
    {
        $this->addBreadcrumb('index', 'SocialPoster');
        $this->addBreadcrumb('history', 'History');
        $this->loadStyle();

        $generator = ee('socialposter:generator');
        $scheduler = ee('socialposter:scheduler');
        $templates = ee('socialposter:templates');
        $templateMeta = $templates->metaById();
        $publisher = ee('socialposter:publisher');
        $coreUrls = $this->coreRouteContext();
        $publishUrls = $this->publishRouteContext();

        $id = (int) $id;

        if ($id > 0 && ee('Request')->post('regenerate_image')) {
            try {
                $post = $generator->regenerateImage($id, [
                    'image_brief' => ee()->input->post('image_brief', false),
                    'image_prompt' => ee()->input->post('image_prompt', false),
                ]);

                if (! $post) {
                    ee('CP/Alert')->makeBanner('socialposter-history')
                        ->asIssue()
                        ->withTitle('Not found')
                        ->addToBody('That generated post does not exist.')
                        ->defer();
                } else {
                    ee('CP/Alert')->makeBanner('socialposter-history')
                        ->asSuccess()
                        ->withTitle('Image regenerated')
                        ->addToBody('The generated image was updated without saving the full post.')
                        ->defer();
                }
            } catch (\Throwable $e) {
                ee('CP/Alert')->makeBanner('socialposter-history')
                    ->asIssue()
                    ->withTitle('Image was not regenerated')
                    ->addToBody($e->getMessage())
                    ->defer();
            }

            ee()->functions->redirect(ee('CP/URL')->make('addons/settings/socialposter/history/' . $id));
        }

        if ($id > 0 && ee('Request')->post('run_review')) {
            try {
                ee('socialposter:review')->review($id);
                ee('CP/Alert')->makeBanner('socialposter-history')
                    ->asSuccess()
                    ->withTitle('Review updated')
                    ->addToBody('The editor review was run on the current draft.')
                    ->defer();
            } catch (\Throwable $e) {
                ee('CP/Alert')->makeBanner('socialposter-history')
                    ->asIssue()
                    ->withTitle('Review failed')
                    ->addToBody($e->getMessage())
                    ->defer();
            }

            ee()->functions->redirect(ee('CP/URL')->make('addons/settings/socialposter/history/' . $id));
        }

        if (ee('Request')->post('save_post')) {
            $generator->update($id, [
                'title' => ee()->input->post('title', true),
                'prompt' => ee()->input->post('prompt', true),
                'post_text' => ee()->input->post('post_text', false),
                'intro_text' => ee()->input->post('intro_text', false),
                'table_of_contents' => ee()->input->post('table_of_contents', false),
                'keywords' => ee()->input->post('keywords', true),
                'category' => ee()->input->post('category', true),
                'hashtags' => ee()->input->post('hashtags', true),
                'external_link' => ee()->input->post('external_link', true),
                'internal_link' => ee()->input->post('internal_link', true),
                'recommended_topics' => ee()->input->post('recommended_topics', false),
                'template_id' => ee()->input->post('template_id', true),
            ]);

            ee('CP/Alert')->makeBanner('socialposter-history')
                ->asSuccess()
                ->withTitle('Saved')
                ->addToBody('The generated post was updated.')
                ->defer();

            ee()->functions->redirect(ee('CP/URL')->make('addons/settings/socialposter/history/' . $id));
        }

        if (ee('Request')->post('delete_id')) {
            $generator->delete((int) ee()->input->post('delete_id'));

            ee('CP/Alert')->makeBanner('socialposter-history')
                ->asSuccess()
                ->withTitle('Deleted')
                ->addToBody('The generated post was deleted.')
                ->defer();

            ee()->functions->redirect(ee('CP/URL')->make('addons/settings/socialposter/history'));
        }

        if ($id > 0) {
            $post = $generator->find($id);
            if (! $post) {
                ee('CP/Alert')->makeBanner('socialposter-history')
                    ->asIssue()
                    ->withTitle('Not found')
                    ->addToBody('That generated post does not exist.')
                    ->defer();
                ee()->functions->redirect(ee('CP/URL')->make('addons/settings/socialposter/history'));
            }

            if ($this->isInstagramPost($post, $templateMeta)) {
                ee()->functions->redirect(ee('CP/URL')->make('addons/settings/socialposter/instagram/history/' . $id));
            }

            $publishedBlogMap = $this->publishedBlogMap([$post['id'] ?? 0], $publisher);
            $publishedFacebookMap = $this->publishedFacebookMap([$post['id'] ?? 0]);
            $scheduleMap = $this->scheduleMap([$post['schedule_id'] ?? 0]);
            $scheduleRunMap = $scheduler->latestRunsByContent('website', [(int) ($post['id'] ?? 0)]);

            $this->setBody('History', [
                'mode' => 'edit',
                'post' => $post,
                'seo_score' => ee('socialposter:seoScorer')->score($post),
                'review' => ee('socialposter:review')->latest($id),
                'review_available' => ee('socialposter:review')->available(),
                'duplicates' => ee('socialposter:review')->duplicates($post),
                'rows' => [],
                'index_url' => $coreUrls['index_url'],
                'settings_url' => $coreUrls['settings_url'],
                'history_url' => $coreUrls['history_url'],
                'history_item_url_pattern' => $this->cpUrl('history/__ID__'),
                'publish_url' => $coreUrls['publish_url'],
                'publish_facebook_url' => $publishUrls['publish_facebook_url'],
                'published_url' => $coreUrls['published_url'],
                'calendar_url' => $coreUrls['calendar_url'],
                'save_url' => ee('CP/URL')->make('addons/settings/socialposter/history/' . $id)->compile(),
                'template_options' => $templates->options(),
                'template_meta' => $templateMeta,
                'published_blog_record' => $publishedBlogMap[(int) ($post['id'] ?? 0)] ?? null,
                'published_facebook_record' => $publishedFacebookMap[(int) ($post['id'] ?? 0)] ?? null,
                'schedule_record' => $scheduleMap[(int) ($post['schedule_id'] ?? 0)] ?? null,
                'schedule_run_record' => $scheduleRunMap[(int) ($post['id'] ?? 0)] ?? null,
            ]);

            return $this;
        }

        $allRows = $generator->latest(50);
        $blogRows = array_values(array_filter(
            $allRows,
            fn(array $row) => ! $this->isInstagramPost($row, $templateMeta)
        ));
        $generationIds = array_map(fn(array $row) => (int) ($row['id'] ?? 0), $blogRows);
        $scheduleIds = array_map(fn(array $row) => (int) ($row['schedule_id'] ?? 0), $blogRows);
        $publishedBlogMap = $this->publishedBlogMap($generationIds, $publisher);
        $publishedFacebookMap = $this->publishedFacebookMap($generationIds);
        $scheduleMap = $this->scheduleMap($scheduleIds);
        $scheduleRunMap = $scheduler->latestRunsByContent('website', $generationIds);
        $stats = $this->reviewStats($blogRows, $publishedBlogMap, $publishedFacebookMap);

        $this->setBody('History', [
            'mode' => 'list',
            'rows' => ee('socialposter:seoScorer')->scoreMany($blogRows),
            'index_url' => $coreUrls['index_url'],
            'settings_url' => $coreUrls['settings_url'],
            'history_url' => $coreUrls['history_url'],
            'history_item_url_pattern' => $this->cpUrl('history/__ID__'),
            'publish_url' => $coreUrls['publish_url'],
            'publish_facebook_url' => $publishUrls['publish_facebook_url'],
            'published_url' => $coreUrls['published_url'],
            'calendar_url' => $coreUrls['calendar_url'],
            'template_options' => $templates->options(),
            'template_meta' => $templateMeta,
            'published_blog_map' => $publishedBlogMap,
            'published_facebook_map' => $publishedFacebookMap,
            'schedule_map' => $scheduleMap,
            'schedule_run_map' => $scheduleRunMap,
            'review_stats' => $stats,
        ]);

        return $this;
    }

    private function publishedBlogMap(array $generationIds, $publisher): array
    {
        $generationIds = array_values(array_filter(array_map('intval', $generationIds)));
        if (! $generationIds) {
            return [];
        }

        $records = [];
        foreach ($publisher->publishedBlogs(200) as $row) {
            $generationId = (int) ($row['generation_id'] ?? 0);
            if ($generationId < 1 || ! in_array($generationId, $generationIds, true)) {
                continue;
            }

            if (! isset($records[$generationId]) || (int) ($row['created_at'] ?? 0) > (int) ($records[$generationId]['created_at'] ?? 0)) {
                $records[$generationId] = $row;
            }
        }

        return $records;
    }

    private function publishedFacebookMap(array $generationIds): array
    {
        $generationIds = array_values(array_filter(array_map('intval', $generationIds)));
        if (! $generationIds || ! ee()->db->table_exists('socialposter_published_facebook')) {
            return [];
        }

        $rows = ee()->db
            ->where_in('generation_id', $generationIds)
            ->order_by('created_at', 'DESC')
            ->get('socialposter_published_facebook')
            ->result_array();

        $records = [];
        foreach ($rows as $row) {
            $generationId = (int) ($row['generation_id'] ?? 0);
            if ($generationId > 0 && ! isset($records[$generationId])) {
                $records[$generationId] = $row;
            }
        }

        return $records;
    }

    private function scheduleMap(array $scheduleIds): array
    {
        $scheduleIds = array_values(array_filter(array_map('intval', $scheduleIds)));
        if (! $scheduleIds || ! ee()->db->table_exists('socialposter_schedules')) {
            return [];
        }

        $rows = ee()->db
            ->where_in('id', $scheduleIds)
            ->get('socialposter_schedules')
            ->result_array();

        $records = [];
        foreach ($rows as $row) {
            $records[(int) $row['id']] = $row;
        }

        return $records;
    }

    private function reviewStats(array $rows, array $publishedBlogMap, array $publishedFacebookMap): array
    {
        $stats = [
            'total' => count($rows),
            'ready_to_publish' => 0,
            'published_blog' => 0,
            'published_facebook' => 0,
            'scheduled_origin' => 0,
            'image_issues' => 0,
        ];

        foreach ($rows as $row) {
            $generationId = (int) ($row['id'] ?? 0);
            $hasBlog = isset($publishedBlogMap[$generationId]);
            $hasFacebook = isset($publishedFacebookMap[$generationId]);
            $hasImageIssue = ! empty($row['image_missing']);
            $hasSchedule = (int) ($row['schedule_id'] ?? 0) > 0;

            if ($hasBlog) {
                $stats['published_blog']++;
            }
            if ($hasFacebook) {
                $stats['published_facebook']++;
            }
            if ($hasSchedule) {
                $stats['scheduled_origin']++;
            }
            if ($hasImageIssue) {
                $stats['image_issues']++;
            }
            if (! $hasBlog && ! $hasImageIssue) {
                $stats['ready_to_publish']++;
            }
        }

        return $stats;
    }

    private function isInstagramPost(array $post, array $templateMeta): bool
    {
        $source = (string) ($post['source'] ?? '');
        if ($source === 'news' || $source === 'instagram') {
            return true;
        }

        $template = $templateMeta[(int) ($post['template_id'] ?? 0)] ?? null;
        if (! $template) {
            return false;
        }

        $platform = strtolower(trim((string) ($template['platform'] ?? '')));
        $title    = strtolower(trim((string) ($template['title'] ?? '')));

        return $platform === 'instagram' || strpos($title, 'instagram carousel') !== false;
    }
}
