<?php

namespace JavidFazaeli\SocialPoster\ControlPanel\Routes\Instagram;

use ExpressionEngine\Service\Addon\Controllers\Mcp\AbstractRoute;
use JavidFazaeli\SocialPoster\ControlPanel\Routes\LoadsStyle;

class NewsSources extends AbstractRoute
{
    use LoadsStyle;

    protected $route_path = 'instagram/news-sources';
    protected $cp_page_title = 'News Sources';

    public function process($id = false)
    {
        $this->addBreadcrumb('index', 'SocialPoster');
        $this->addBreadcrumb('instagram', 'Instagram');
        $this->addBreadcrumb('instagram/news-sources', 'News Sources');
        $this->loadStyle();

        $newsFeed = ee('socialposter:newsFeed');
        $selfUrl  = ee('CP/URL')->make('addons/settings/socialposter/instagram/news-sources');

        if (ee('Request')->isPost()) {
            if (ee()->input->post('add_source', true)) {
                try {
                    $newsFeed->addSource(
                        (string) ee()->input->post('source_name', true),
                        (string) ee()->input->post('feed_url', true),
                        (string) ee()->input->post('category', true)
                    );
                    ee('CP/Alert')->makeBanner('socialposter-news-sources')
                        ->asSuccess()
                        ->withTitle('Source added')
                        ->addToBody('The news source was saved.')
                        ->defer();
                } catch (\Throwable $e) {
                    ee('CP/Alert')->makeBanner('socialposter-news-sources')
                        ->asIssue()
                        ->withTitle('Could not add source')
                        ->addToBody($e->getMessage())
                        ->now();
                }
            }

            if (ee()->input->post('delete_source', true)) {
                $newsFeed->deleteSource((int) ee()->input->post('source_id', true));
                ee('CP/Alert')->makeBanner('socialposter-news-sources')
                    ->asSuccess()
                    ->withTitle('Source removed')
                    ->defer();
            }

            ee()->functions->redirect($selfUrl);
        }

        $filterCategory = (string) ee()->input->get('category', true);

        $this->setBody('NewsSources', [
            'sources'         => $newsFeed->allSources($filterCategory),
            'categories'      => \JavidFazaeli\SocialPoster\Service\NewsFeedService::categories(),
            'filter_category' => $filterCategory,
            'save_url'        => $selfUrl->compile(),
            'create_url'      => ee('CP/URL')->make('addons/settings/socialposter/instagram/create')->compile(),
        ]);

        return $this;
    }
}
