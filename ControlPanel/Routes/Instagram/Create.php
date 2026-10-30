<?php

namespace JavidFazaeli\SocialPoster\ControlPanel\Routes\Instagram;

use ExpressionEngine\Service\Addon\Controllers\Mcp\AbstractRoute;
use JavidFazaeli\SocialPoster\ControlPanel\Routes\BuildsRouteContext;
use JavidFazaeli\SocialPoster\ControlPanel\Routes\LoadsStyle;

class Create extends AbstractRoute
{
    use LoadsStyle;
    use BuildsRouteContext;

    protected $route_path = 'instagram/create';
    protected $cp_page_title = 'Create Instagram Post';

    public function process($id = false)
    {
        $this->addBreadcrumb('index', 'SocialPoster');
        $this->addBreadcrumb('instagram', 'Instagram');
        $this->addBreadcrumb('instagram/create', 'Create');
        $this->loadStyle();

        $templates  = ee('socialposter:templates');
        $carousel   = ee('socialposter:carousel');
        $newsFeed   = ee('socialposter:newsFeed');
        $instagramUrls = $this->instagramRouteContext();

        $this->setBody('InstagramCreate', [
            'action_url'          => ee('socialposter:generator')->actionUrl(),
            'csrf_token'          => ee('socialposter:generator')->csrfToken(),
            'general_url'         => $instagramUrls['general_url'],
            'instagram_url'       => $instagramUrls['instagram_settings_url'],
            'instagram_history_url' => $instagramUrls['instagram_history_url'],
            'instagram_carousel_url' => $instagramUrls['instagram_carousel_url'],
            'publish_instagram_url' => $instagramUrls['publish_instagram_url'],
            'templates_url'        => $this->cpUrl('templates'),
            'carousel_item_pat'   => $this->cpUrl('instagram/carousel/__ID__'),
            'template_options'    => $templates->optionsForPage('instagram', false),
            'selected_template_id'=> (int) (($templates->instagramCarouselTemplate())['id'] ?? 0),
            'carousel_templates'  => $carousel->listTemplates(),
            'news_sources'        => $newsFeed->allSources(),
            'news_sources_url'    => $instagramUrls['instagram_news_sources_url'],
        ]);

        return $this;
    }
}
