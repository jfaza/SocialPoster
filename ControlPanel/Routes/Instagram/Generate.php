<?php

namespace JavidFazaeli\SocialPoster\ControlPanel\Routes\Instagram;

use ExpressionEngine\Service\Addon\Controllers\Mcp\AbstractRoute;
use JavidFazaeli\SocialPoster\ControlPanel\Routes\BuildsRouteContext;
use JavidFazaeli\SocialPoster\ControlPanel\Routes\LoadsStyle;

class Generate extends AbstractRoute
{
    use LoadsStyle;
    use BuildsRouteContext;

    protected $route_path = 'instagram/generate';
    protected $cp_page_title = 'SocialPoster Instagram Generate';

    public function process($id = false)
    {
        $this->addBreadcrumb('index', 'SocialPoster');
        $this->addBreadcrumb('instagram', 'Instagram');
        $this->addBreadcrumb('instagram/generate', 'Generate Post');
        $this->loadStyle();

        $templates = ee('socialposter:templates');
        $instagramCarousel = $templates->instagramCarouselTemplate();
        $coreUrls = $this->coreRouteContext();
        $instagramUrls = $this->instagramRouteContext();

        $this->setBody('Index', [
            'page_mode' => 'instagram',
            'action_url' => ee('socialposter:generator')->actionUrl(),
            'general_url' => $instagramUrls['general_url'],
            'instagram_settings_url' => $instagramUrls['instagram_settings_url'],
            'history_url' => $instagramUrls['instagram_history_url'],
            'history_item_url_pattern' => $this->cpUrl('history/__ID__'),
            'publish_url' => $coreUrls['publish_url'],
            'templates_url' => $coreUrls['templates_url'],
            'selected_template_id' => (int) ($instagramCarousel['id'] ?? 0),
            'template_options' => $templates->optionsForPage('instagram', false),
            'template_meta' => $templates->metaByPage('instagram'),
            'template_starters' => $templates->starterPresetsByPage('instagram'),
            'csrf_token' => ee('socialposter:generator')->csrfToken(),
        ]);

        return $this;
    }
}
