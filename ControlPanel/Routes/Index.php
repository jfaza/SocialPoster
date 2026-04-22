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
            'action_url' => ee('socialposter:generator')->actionUrl(),
            'settings_url' => ee('CP/URL')->make('addons/settings/socialposter/settings')->compile(),
            'history_url' => ee('CP/URL')->make('addons/settings/socialposter/history')->compile(),
            'publish_url' => ee('CP/URL')->make('addons/settings/socialposter/publish')->compile(),
            'templates_url' => ee('CP/URL')->make('addons/settings/socialposter/templates')->compile(),
            'template_options' => ee('socialposter:templates')->options(),
            'csrf_token' => ee('socialposter:generator')->csrfToken(),
        ]);

        return $this;
    }
}
