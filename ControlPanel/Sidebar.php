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
        $current = ee()->uri->uri_string;

        $mk         = fn($suffix) => ee('CP/URL')->make($this->base . $suffix);
        $path       = fn($suffix) => $this->base . $suffix;
        $isExact    = fn($suffix) => rtrim($current, '/') === rtrim($path($suffix), '/');
        $startsWith = fn($suffix) => strpos($current, $path($suffix)) === 0;

        // ── Workflows ───────────────────────────────────────────
        $main = $sidebar->addHeader($this->header)->addBasicList();

        $main->addItem('Generate', $mk('index'))
            ->withIcon('pencil')
            ->isActive(
                $startsWith('index')
                || rtrim($current, '/') === rtrim($this->base, '/')
            );

        $main->addItem('Calendar', $mk('calendar'))
            ->withIcon('calendar')
            ->isActive($startsWith('calendar'));

        $main->addItem('History', $mk('history'))
            ->withIcon('list')
            ->isActive($startsWith('history'));

        $main->addItem('Published', $mk('published'))
            ->withIcon('check')
            ->isActive($isExact('published'));

        $main->addItem('Publish Blog', $mk('publish'))
            ->withIcon('upload')
            ->isActive($isExact('publish'));

        // ── Instagram (content tools + publishing) ──────────────
        $instagram = $sidebar->addHeader('Instagram')->addBasicList();

        $instagram->addItem('Create', $mk('instagram/create'))
            ->withIcon('magic')
            ->isActive($startsWith('instagram/create'));

        $instagram->addItem('History', $mk('instagram/history'))
            ->withIcon('clock')
            ->isActive($startsWith('instagram/history'));

        $instagram->addItem('Carousels', $mk('instagram/carousel'))
            ->withIcon('images')
            ->isActive($startsWith('instagram/carousel'));

        $instagram->addItem('News Sources', $mk('instagram/news-sources'))
            ->withIcon('rss')
            ->isActive($startsWith('instagram/news-sources'));

        $instagram->addItem('Publish', $mk('publish/instagram'))
            ->withIcon('paper-plane')
            ->isActive($startsWith('publish/instagram'));

        // ── Settings (admin / config) ───────────────────────────
        $settings = $sidebar->addHeader('Settings')->addBasicList();

        $settings->addItem('OpenAI', $mk('settings'))
            ->withIcon('key')
            ->isActive($startsWith('settings'));

        $settings->addItem('Instagram', $mk('instagram'))
            ->withIcon('instagram')
            ->isActive($isExact('instagram'));

        $settings->addItem('Templates', $mk('templates'))
            ->withIcon('layer-group')
            ->isActive($startsWith('templates'));

        $settings->addItem('Token Usage', $mk('usage'))
            ->withIcon('chart-line')
            ->isActive($startsWith('usage'));

        ee()->view->sidebar = $sidebar->render();
    }
}
