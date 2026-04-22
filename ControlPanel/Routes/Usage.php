<?php

namespace JavidFazaeli\SocialPoster\ControlPanel\Routes;

use ExpressionEngine\Service\Addon\Controllers\Mcp\AbstractRoute;

class Usage extends AbstractRoute
{
    use LoadsStyle;

    /**
     * @var string
     */
    protected $route_path = 'usage';

    /**
     * @var string
     */
    protected $cp_page_title = 'SocialPoster Token Usage';

    /**
     * @param false $id
     * @return AbstractRoute
     */
    public function process($id = false)
    {
        $this->addBreadcrumb('index', 'SocialPoster');
        $this->addBreadcrumb('usage', 'Token Usage');
        $this->loadStyle();

        $usage = ee('socialposter:generator')->tokenUsage(100);

        $this->setBody('Usage', [
            'summary' => $usage['summary'],
            'rows' => $usage['rows'],
            'costs' => $usage['costs'],
            'history_url' => ee('CP/URL')->make('addons/settings/socialposter/history')->compile(),
            'settings_url' => ee('CP/URL')->make('addons/settings/socialposter/settings')->compile(),
        ]);

        return $this;
    }
}
