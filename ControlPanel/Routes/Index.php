<?php

namespace JavidFazaeli\SocialPoster\ControlPanel\Routes;

use ExpressionEngine\Service\Addon\Controllers\Mcp\AbstractRoute;

class Index extends AbstractRoute
{
    use LoadsStyle;

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

        $this->setBody('Index', [
            'page_mode' => 'general',
            'action_url' => ee('socialposter:generator')->actionUrl(),
            'general_settings_url' => ee('CP/URL')->make('addons/settings/socialposter/settings')->compile(),
            'instagram_generate_url' => ee('CP/URL')->make('addons/settings/socialposter/instagram/generate')->compile(),
            'history_url' => ee('CP/URL')->make('addons/settings/socialposter/history')->compile(),
            'history_item_url_pattern' => ee('CP/URL')->make('addons/settings/socialposter/history/__ID__')->compile(),
            'publish_url' => ee('CP/URL')->make('addons/settings/socialposter/publish')->compile(),
            'templates_url' => ee('CP/URL')->make('addons/settings/socialposter/templates')->compile(),
            'template_options' => ee('socialposter:templates')->optionsForPage('general'),
            'template_meta' => ee('socialposter:templates')->metaByPage('general'),
            'template_starters' => ee('socialposter:templates')->starterPresetsByPage('general'),
            'csrf_token' => ee('socialposter:generator')->csrfToken(),
        ]);

        return $this;
    }
}
