<?php

namespace JavidFazaeli\SocialPoster\ControlPanel\Routes\Instagram;

use ExpressionEngine\Service\Addon\Controllers\Mcp\AbstractRoute;
use JavidFazaeli\SocialPoster\ControlPanel\Routes\LoadsStyle;

class Create extends AbstractRoute
{
    use LoadsStyle;

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

        $this->setBody('InstagramCreate', [
            'action_url'          => ee('socialposter:generator')->actionUrl(),
            'csrf_token'          => ee('socialposter:generator')->csrfToken(),
            'instagram_url'       => ee('CP/URL')->make('addons/settings/socialposter/instagram')->compile(),
            'carousel_item_pat'   => ee('CP/URL')->make('addons/settings/socialposter/instagram/carousel/__ID__')->compile(),
            'template_options'    => $templates->optionsForPage('instagram', false),
            'selected_template_id'=> (int) (($templates->instagramCarouselTemplate())['id'] ?? 0),
            'carousel_templates'  => $carousel->listTemplates(),
            'news_sources'        => ee('socialposter:newsFeed')->allSources(),
            'news_sources_url'    => ee('CP/URL')->make('addons/settings/socialposter/instagram/news-sources')->compile(),
        ]);

        return $this;
    }
}
