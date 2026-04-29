<?php

namespace JavidFazaeli\SocialPoster\ControlPanel\Routes;

use ExpressionEngine\Service\Addon\Controllers\Mcp\AbstractRoute;

class Publish extends AbstractRoute
{
    use LoadsStyle;

    protected $route_path = 'publish';
    protected $cp_page_title = 'SocialPoster Publish';

    public function process($id = false)
    {
        $this->addBreadcrumb('index', 'SocialPoster');
        $this->addBreadcrumb('publish', 'Publish Blog');
        $this->loadStyle();

        $publisher = ee('socialposter:publisher');
        $result = null;

        if (ee('Request')->post('publish_selected')) {
            $selectedTargets = ee()->input->post('publish_targets', true);
            $selectedTargets = is_array($selectedTargets) ? $selectedTargets : [];
            $selectedTargets = array_values(array_intersect(array_map('strval', $selectedTargets), array_keys($publisher->targets())));

            if (empty($selectedTargets)) {
                ee('CP/Alert')->makeBanner('socialposter-publish')
                    ->asIssue()
                    ->withTitle('Choose a publish target')
                    ->addToBody('Select at least one place to publish this generated post.')
                    ->now();
            }
        }

        if (ee('Request')->post('publish_selected') && in_array('blog', $selectedTargets ?? [], true)) {
            try {
                $result = $publisher->publishToBlog((int) ee()->input->post('generation_id'), [
                    'title' => ee()->input->post('title', true),
                    'status' => ee()->input->post('status', true),
                    'category_id' => ee()->input->post('category_id', true),
                    'include_image' => ee()->input->post('include_image', true),
                ]);

                ee('CP/Alert')->makeBanner('socialposter-publish')
                    ->asSuccess()
                    ->withTitle('Published to blog')
                    ->addToBody('Entry #' . $result['entry_id'] . ' was created.')
                    ->now();
            } catch (\Throwable $e) {
                ee('CP/Alert')->makeBanner('socialposter-publish')
                    ->asIssue()
                    ->withTitle('Blog publish failed')
                    ->addToBody($e->getMessage())
                    ->now();
            }
        }

        if (ee('Request')->post('publish_selected')) {
            $notReadyTargets = array_diff($selectedTargets ?? [], ['blog']);
            if (! empty($notReadyTargets)) {
                $labels = [];
                foreach ($publisher->targets() as $key => $target) {
                    if (in_array($key, $notReadyTargets, true)) {
                        $labels[] = (string) $target['label'];
                    }
                }

                ee('CP/Alert')->makeBanner('socialposter-publish-targets')
                    ->asIssue()
                    ->withTitle('Some targets are not publishable yet')
                    ->addToBody(implode(', ', $labels) . ' selected, but direct publishing for those targets is not implemented yet.')
                    ->now();
            }
        }

        $templateMeta = ee('socialposter:templates')->metaById();

        $rows = array_values(array_filter(
            ee('socialposter:generator')->latest(50),
            function (array $row) use ($templateMeta) {
                if ((string) ($row['source'] ?? '') === 'news') {
                    return false;
                }
                $template = $templateMeta[(int) ($row['template_id'] ?? 0)] ?? null;
                if ($template) {
                    $platform = strtolower(trim((string) ($template['platform'] ?? '')));
                    $title    = strtolower(trim((string) ($template['title'] ?? '')));
                    if ($platform === 'instagram' || strpos($title, 'instagram carousel') !== false) {
                        return false;
                    }
                }
                return true;
            }
        ));

        $this->setBody('Publish', [
            'publish_url' => ee('CP/URL')->make('addons/settings/socialposter/publish')->compile(),
            'history_url' => ee('CP/URL')->make('addons/settings/socialposter/history')->compile(),
            'index_url' => ee('CP/URL')->make('addons/settings/socialposter')->compile(),
            'targets' => $publisher->targets(),
            'status_options' => $publisher->statusOptions(),
            'category_options' => $publisher->categoryOptions(),
            'rows' => $rows,
            'result' => $result,
            'template_meta' => $templateMeta,
        ]);

        return $this;
    }
}
