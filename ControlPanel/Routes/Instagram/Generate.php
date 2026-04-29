<?php

namespace JavidFazaeli\SocialPoster\ControlPanel\Routes\Instagram;

use ExpressionEngine\Service\Addon\Controllers\Mcp\AbstractRoute;
use JavidFazaeli\SocialPoster\ControlPanel\Routes\LoadsStyle;

class Generate extends AbstractRoute
{
    use LoadsStyle;

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

        $this->setBody('Index', [
            'page_mode' => 'instagram',
            'action_url' => ee('socialposter:generator')->actionUrl(),
            'general_url' => ee('CP/URL')->make('addons/settings/socialposter')->compile(),
            'instagram_settings_url' => ee('CP/URL')->make('addons/settings/socialposter/instagram')->compile(),
            'history_url' => ee('CP/URL')->make('addons/settings/socialposter/instagram/history')->compile(),
            'history_item_url_pattern' => ee('CP/URL')->make('addons/settings/socialposter/history/__ID__')->compile(),
            'publish_url' => ee('CP/URL')->make('addons/settings/socialposter/publish')->compile(),
            'templates_url' => ee('CP/URL')->make('addons/settings/socialposter/templates')->compile(),
            'selected_template_id' => (int) ($instagramCarousel['id'] ?? 0),
            'template_options' => $templates->optionsForPage('instagram', false),
            'template_meta' => $templates->metaByPage('instagram'),
            'template_starters' => $templates->starterPresetsByPage('instagram'),
            'csrf_token' => ee('socialposter:generator')->csrfToken(),
        ]);

        return $this;
    }
}
