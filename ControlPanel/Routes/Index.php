<?php

namespace JavidFazaeli\SocialPoster\ControlPanel\Routes;

use ExpressionEngine\Service\Addon\Controllers\Mcp\AbstractRoute;

class Index extends AbstractRoute
{
    use LoadsStyle;
    use BuildsRouteContext;

    /**
     * @var string
     */
    protected $route_path = 'index';

    /**
     * @var string
     */
    protected $cp_page_title = 'SocialPoster';

    /**
     * @param false $id
     * @return AbstractRoute
     */
    public function process($id = false)
    {
        $this->addBreadcrumb('index', 'SocialPoster');
        $this->loadStyle();
        $newsFeed = ee('socialposter:newsFeed');
        $coreUrls = $this->coreRouteContext();

        $this->setBody('Index', [
            'page_mode' => 'general',
            'action_url' => ee('socialposter:generator')->actionUrl(),
            'general_settings_url' => $coreUrls['settings_url'],
            'instagram_generate_url' => $this->cpUrl('instagram/generate'),
            'history_url' => $coreUrls['history_url'],
            'history_item_url_pattern' => $this->cpUrl('history/__ID__'),
            'news_sources_url' => $coreUrls['news_sources_url'],
            'publish_url' => $coreUrls['publish_url'],
            'templates_url' => $coreUrls['templates_url'],
            'template_options' => ee('socialposter:templates')->optionsForPage('general'),
            'template_meta' => ee('socialposter:templates')->metaByPage('general'),
            'template_starters' => ee('socialposter:templates')->starterPresetsByPage('general'),
            'hot_articles' => $newsFeed->hotArticles(20),
            'hot_articles_meta' => $newsFeed->hotCacheMeta(),
            'csrf_token' => ee('socialposter:generator')->csrfToken(),
        ]);

        return $this;
    }
}
