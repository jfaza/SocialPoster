<?php

namespace JavidFazaeli\SocialPoster\ControlPanel;

use ExpressionEngine\Service\Addon\Controllers\Mcp\AbstractSidebar;

class Sidebar extends AbstractSidebar
{
    public $automatic = false;
    public $header = 'SocialPoster';

    private string $base = 'addons/settings/socialposter/';

    public function process()
    {
        $sidebar = ee('CP/Sidebar')->make();
        $list = $sidebar->addHeader($this->header)->addBasicList();

        $current = ee()->uri->uri_string;
        $mk = fn($suffix) => ee('CP/URL')->make($this->base . $suffix);

        $list->addItem('Calendar', $mk('calendar'))
            ->withIcon('calendar')
            ->isActive(strpos($current, $this->base . 'calendar') !== false);

        $list->addItem('Generate', $mk('index'))
            ->withIcon('pencil')
            ->isActive(
                strpos($current, $this->base . 'index') !== false
                || rtrim($current, '/') === rtrim($this->base, '/')
            );

        $list->addItem('Publish', $mk('publish'))
            ->withIcon('upload')
            ->isActive(strpos($current, $this->base . 'publish') !== false);

        $list->addItem('Templates', $mk('templates'))
            ->withIcon('list')
            ->isActive(strpos($current, $this->base . 'templates') !== false);

        $list->addItem('Token Usage', $mk('usage'))
            ->withIcon('bar-chart')
            ->isActive(strpos($current, $this->base . 'usage') !== false);

        $list->addItem('Settings', $mk('settings'))
            ->withIcon('cog')
            ->isActive(strpos($current, $this->base . 'settings') !== false);

        $list->addItem('History', $mk('history'))
            ->withIcon('list')
            ->isActive(strpos($current, $this->base . 'history') !== false);

        ee()->view->sidebar = $sidebar->render();
    }
}
