<?php

namespace JavidFazaeli\SocialPoster\ControlPanel\Routes\Instagram;

use ExpressionEngine\Service\Addon\Controllers\Mcp\AbstractRoute;
use JavidFazaeli\SocialPoster\ControlPanel\Routes\BuildsRouteContext;
use JavidFazaeli\SocialPoster\ControlPanel\Routes\LoadsStyle;

class Carousel extends AbstractRoute
{
    use LoadsStyle;
    use BuildsRouteContext;

    protected $route_path = 'instagram/carousel';
    protected $cp_page_title = 'Instagram Carousel';

    public function process($id = false)
    {
        $this->addBreadcrumb('index', 'SocialPoster');
        $this->addBreadcrumb('instagram', 'Instagram');
        $this->addBreadcrumb('instagram/carousel', 'Carousel');
        $this->loadStyle();

        $carousel = ee('socialposter:carousel');
        $instagramUrls = $this->instagramRouteContext();

        // EE's buildObject() corrupts $id for nested sub-routes; fall back to URI
        $id = (int) $id;
        if ($id === 0) {
            $uri = (string) ee()->uri->uri_string;
            if (preg_match('~/instagram/carousel/(\d+)~', $uri, $m)) {
                $id = (int) $m[1];
            }
        }

        // Delete
        if (ee('Request')->post('delete_id')) {
            $carousel->delete((int) ee()->input->post('delete_id'));
            ee('CP/Alert')->makeBanner('sp-carousel')
                ->asSuccess()->withTitle('Deleted')
                ->addToBody('The carousel was deleted.')->defer();
            ee()->functions->redirect(ee('CP/URL')->make('addons/settings/socialposter/instagram/carousel'));
        }

        // Save edits on a specific carousel
        if ($id > 0 && ee('Request')->post('save_carousel')) {
            try {
                $carousel->update($id, [
                    'topic'    => ee()->input->post('topic', true),
                    'caption'  => ee()->input->post('caption', false),
                    'hashtags' => ee()->input->post('hashtags', true),
                    'slides'   => $this->decodePostedSlides(),
                ]);

                ee('CP/Alert')->makeBanner('sp-carousel')
                    ->asSuccess()->withTitle('Saved')
                    ->addToBody('Carousel content was updated.')->defer();
            } catch (\Throwable $e) {
                ee('CP/Alert')->makeBanner('sp-carousel')
                    ->asIssue()->withTitle('Save failed')
                    ->addToBody($e->getMessage())->defer();
            }

            ee()->functions->redirect(ee('CP/URL')->make('addons/settings/socialposter/instagram/carousel/' . $id));
        }

        // View / edit a specific carousel
        if ($id > 0) {
            $post = $carousel->find($id);
            if (! $post) {
                ee('CP/Alert')->makeBanner('sp-carousel')
                    ->asIssue()->withTitle('Not found')
                    ->addToBody('That carousel does not exist.')->defer();
                ee()->functions->redirect(ee('CP/URL')->make('addons/settings/socialposter/instagram/carousel'));
            }

            $this->setBody('Carousel', [
                'mode'             => 'edit',
                'post'             => $post,
                'templates'        => $carousel->listTemplates(),
                'news_sources'     => ee('socialposter:newsFeed')->allSources(),
                'news_sources_url' => $instagramUrls['instagram_news_sources_url'],
                'action_url'       => ee('socialposter:generator')->actionUrl(),
                'csrf_token'       => ee('socialposter:generator')->csrfToken(),
                'save_url'         => ee('CP/URL')->make('addons/settings/socialposter/instagram/carousel/' . $id)->compile(),
                'index_url'        => $instagramUrls['instagram_carousel_url'],
                'general_url'      => $instagramUrls['general_url'],
                'instagram_create_url' => $instagramUrls['instagram_create_url'],
                'instagram_history_url' => $instagramUrls['instagram_history_url'],
                'instagram_url'    => $instagramUrls['instagram_settings_url'],
                'templates_url'    => $this->cpUrl('templates'),
                'publish_url'      => $instagramUrls['publish_instagram_url'],
                'delete_url'       => $instagramUrls['instagram_carousel_url'],
                'item_url_pattern' => $this->cpUrl('instagram/carousel/__ID__'),
            ]);

            return $this;
        }

        // Index: generate form + recent carousels list
        $rows = $carousel->latest(30);

        $this->setBody('Carousel', [
            'mode'             => 'generate',
            'rows'             => $rows,
            'templates'        => $carousel->listTemplates(),
            'news_sources'     => ee('socialposter:newsFeed')->allSources(),
            'news_sources_url' => $instagramUrls['instagram_news_sources_url'],
            'action_url'       => ee('socialposter:generator')->actionUrl(),
            'csrf_token'       => ee('socialposter:generator')->csrfToken(),
            'index_url'        => $instagramUrls['instagram_carousel_url'],
            'general_url'      => $instagramUrls['general_url'],
            'instagram_create_url' => $instagramUrls['instagram_create_url'],
            'instagram_history_url' => $instagramUrls['instagram_history_url'],
            'instagram_url'    => $instagramUrls['instagram_settings_url'],
            'templates_url'    => $this->cpUrl('templates'),
            'publish_url'      => $instagramUrls['publish_instagram_url'],
            'item_url_pattern' => $this->cpUrl('instagram/carousel/__ID__'),
        ]);

        return $this;
    }

    private function decodePostedSlides(): array
    {
        $raw = ee()->input->post('slides_json', false);
        if (! $raw) {
            return [];
        }

        $decoded = json_decode((string) $raw, true);
        return is_array($decoded) ? $decoded : [];
    }
}
