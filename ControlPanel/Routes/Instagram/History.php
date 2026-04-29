<?php

namespace JavidFazaeli\SocialPoster\ControlPanel\Routes\Instagram;

use ExpressionEngine\Service\Addon\Controllers\Mcp\AbstractRoute;
use JavidFazaeli\SocialPoster\ControlPanel\Routes\LoadsStyle;

class History extends AbstractRoute
{
    use LoadsStyle;

    protected $route_path = 'instagram/history';
    protected $cp_page_title = 'SocialPoster Instagram Generated Posts';

    public function process($id = false)
    {
        $this->addBreadcrumb('index', 'SocialPoster');
        $this->addBreadcrumb('instagram', 'Instagram');
        $this->addBreadcrumb('instagram/history', 'Generated Posts');
        $this->loadStyle();

        $generator = ee('socialposter:instagramGenerator');
        $templates = ee('socialposter:templates');
        $templateMeta = $templates->metaByPage('instagram');
        $templateOptions = $templates->optionsForPage('instagram', false);

        // EE's buildObject() corrupts $id for nested sub-routes; fall back to URI
        $id = (int) $id;
        if ($id === 0) {
            $uri = (string) ee()->uri->uri_string;
            if (preg_match('~/instagram/history/(\d+)~', $uri, $m)) {
                $id = (int) $m[1];
            }
        }

        if ($id > 0 && ee('Request')->post('regenerate_image')) {
            try {
                $post = $generator->regenerateImage($id, [
                    'image_brief' => ee()->input->post('image_brief', false),
                    'image_prompt' => ee()->input->post('image_prompt', false),
                ]);

                if (! $post || ! $this->isInstagramPost($post, $templateMeta)) {
                    ee('CP/Alert')->makeBanner('socialposter-instagram-history')
                        ->asIssue()
                        ->withTitle('Not found')
                        ->addToBody('That Instagram generated post does not exist.')
                        ->defer();
                } else {
                    ee('CP/Alert')->makeBanner('socialposter-instagram-history')
                        ->asSuccess()
                        ->withTitle('Image regenerated')
                        ->addToBody('The Instagram cover image was updated.')
                        ->defer();
                }
            } catch (\Throwable $e) {
                ee('CP/Alert')->makeBanner('socialposter-instagram-history')
                    ->asIssue()
                    ->withTitle('Image was not regenerated')
                    ->addToBody($e->getMessage())
                    ->defer();
            }

            ee()->functions->redirect(ee('CP/URL')->make('addons/settings/socialposter/instagram/history/' . $id));
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

            ee('CP/Alert')->makeBanner('socialposter-instagram-history')
                ->asSuccess()
                ->withTitle('Saved')
                ->addToBody('The Instagram generated post was updated.')
                ->defer();

            ee()->functions->redirect(ee('CP/URL')->make('addons/settings/socialposter/instagram/history/' . $id));
        }

        if (ee('Request')->post('delete_id')) {
            $generator->delete((int) ee()->input->post('delete_id'));

            ee('CP/Alert')->makeBanner('socialposter-instagram-history')
                ->asSuccess()
                ->withTitle('Deleted')
                ->addToBody('The Instagram generated post was deleted.')
                ->defer();

            ee()->functions->redirect(ee('CP/URL')->make('addons/settings/socialposter/instagram/history'));
        }

        if ($id > 0 && ee('Request')->post('publish_to_instagram')) {
            try {
                ee('socialposter:publisher')->publishToInstagram($id);

                ee('CP/Alert')->makeBanner('socialposter-instagram-history')
                    ->asSuccess()
                    ->withTitle('Published to Instagram')
                    ->addToBody('The post was published to your Instagram account.')
                    ->defer();
            } catch (\Throwable $e) {
                ee('CP/Alert')->makeBanner('socialposter-instagram-history')
                    ->asIssue()
                    ->withTitle('Instagram publish failed')
                    ->addToBody($e->getMessage())
                    ->defer();
            }

            ee()->functions->redirect(ee('CP/URL')->make('addons/settings/socialposter/instagram/history/' . $id));
        }

        if ($id > 0) {
            $post = $generator->find($id);
            if (! $post) {
                ee('CP/Alert')->makeBanner('socialposter-instagram-history')
                    ->asIssue()
                    ->withTitle('Not found')
                    ->addToBody('That Instagram generated post does not exist.')
                    ->defer();
                ee()->functions->redirect(ee('CP/URL')->make('addons/settings/socialposter/instagram/history'));
            }

            $publishedRecord = ee()->db->table_exists('socialposter_published_instagram')
                ? (ee()->db->where('generation_id', $id)->order_by('created_at', 'DESC')->limit(1)->get('socialposter_published_instagram')->row_array() ?: null)
                : null;

            $this->setBody('History', [
                'mode' => 'edit',
                'page_mode' => 'instagram',
                'post' => $post,
                'published_record' => $publishedRecord,
                'seo_score' => [],
                'rows' => [],
                'index_url' => ee('CP/URL')->make('addons/settings/socialposter/instagram/generate')->compile(),
                'settings_url' => ee('CP/URL')->make('addons/settings/socialposter/instagram')->compile(),
                'history_url' => ee('CP/URL')->make('addons/settings/socialposter/instagram/history')->compile(),
                'history_item_url_pattern' => ee('CP/URL')->make('addons/settings/socialposter/instagram/history/__ID__')->compile(),
                'save_url' => ee('CP/URL')->make('addons/settings/socialposter/instagram/history/' . $id)->compile(),
                'publish_instagram_url' => ee('CP/URL')->make('addons/settings/socialposter/instagram/history/' . $id)->compile(),
                'instagram_nav' => $this->instagramNav(),
                'template_options' => $templateOptions,
                'template_meta' => $templateMeta,
                'history_title' => 'Generated Instagram Posts',
            ]);

            return $this;
        }

        $rows = $generator->latest(50);

        $publishedIds = [];
        if (ee()->db->table_exists('socialposter_published_instagram') && ! empty($rows)) {
            $genIds = array_column($rows, 'id');
            $pubRows = ee()->db->select('generation_id, created_at')->where_in('generation_id', $genIds)->get('socialposter_published_instagram')->result_array();
            foreach ($pubRows as $pub) {
                $gid = (int) $pub['generation_id'];
                if (! isset($publishedIds[$gid]) || (int) $pub['created_at'] > $publishedIds[$gid]) {
                    $publishedIds[$gid] = (int) $pub['created_at'];
                }
            }
        }

        $this->setBody('History', [
            'mode' => 'list',
            'page_mode' => 'instagram',
            'rows' => $rows,
            'published_ids' => $publishedIds,
            'index_url' => ee('CP/URL')->make('addons/settings/socialposter/instagram/generate')->compile(),
            'settings_url' => ee('CP/URL')->make('addons/settings/socialposter/instagram')->compile(),
            'history_url' => ee('CP/URL')->make('addons/settings/socialposter/instagram/history')->compile(),
            'history_item_url_pattern' => ee('CP/URL')->make('addons/settings/socialposter/instagram/history/__ID__')->compile(),
            'instagram_nav' => $this->instagramNav(),
            'template_options' => $templateOptions,
            'template_meta' => $templateMeta,
            'history_title' => 'Generated Instagram Posts',
        ]);

        return $this;
    }

    private function instagramNav(): array
    {
        $base = 'addons/settings/socialposter/';
        return [
            'create'       => ee('CP/URL')->make($base . 'instagram/create')->compile(),
            'history'      => ee('CP/URL')->make($base . 'instagram/history')->compile(),
            'carousels'    => ee('CP/URL')->make($base . 'instagram/carousel')->compile(),
            'news_sources' => ee('CP/URL')->make($base . 'instagram/news-sources')->compile(),
            'publish'      => ee('CP/URL')->make($base . 'publish/instagram')->compile(),
            'settings'     => ee('CP/URL')->make($base . 'instagram')->compile(),
        ];
    }
}
