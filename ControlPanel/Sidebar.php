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
        $mkHash     = fn($suffix, $hash) => $mk($suffix)->compile() . $hash;
        $path       = fn($suffix) => $this->base . $suffix;
        $isExact    = fn($suffix) => rtrim($current, '/') === rtrim($path($suffix), '/');
        $startsWith = fn($suffix) => strpos($current, $path($suffix)) === 0;

        // ── Create ──────────────────────────────────────────────
        $create = $sidebar->addHeader('Create')->addBasicList();

        $create->addItem('New Post', $mk('index'))
            ->withIcon('pencil')
            ->isActive(
                $startsWith('index')
                || rtrim($current, '/') === rtrim($this->base, '/')
            );

        $create->addItem('Carousel', $mk('instagram/carousel'))
            ->withIcon('images')
            ->isActive($startsWith('instagram/carousel'));

        // ── Manage ─────────────────────────────────────────────
        $manage = $sidebar->addHeader('Manage')->addBasicList();

        $manage->addItem('Calendar', $mk('calendar'))
            ->withIcon('calendar')
            ->isActive($startsWith('calendar'));

        // No dedicated drafts screen exists yet, so map "Drafts" to the existing history page.
        $manage->addItem('Drafts', $mk('history'))
            ->withIcon('file')
            ->isActive(false);

        $manage->addItem('History', $mk('history'))
            ->withIcon('list')
            ->isActive($startsWith('history'));

        $manage->addItem('Published', $mk('published'))
            ->withIcon('check')
            ->isActive($isExact('published'));

        // ── Resources ─────────────────────────────────────────
        $resources = $sidebar->addHeader('Resources')->addBasicList();

        $resources->addItem('News Sources', $mk('news-sources'))
            ->withIcon('rss')
            ->isActive($startsWith('news-sources'));

        $resources->addItem('Content Templates', $mkHash('templates', '#tab=t-1'))
            ->withIcon('layer-group')
            ->isActive($startsWith('templates'));

        $resources->addItem('Carousel Templates', $mkHash('templates', '#tab=t-2'))
            ->withIcon('images')
            ->isActive($startsWith('templates'));

        // ── Channels ───────────────────────────────────────────
        $channels = $sidebar->addHeader('Channels')->addBasicList();

        $channels->addItem('Instagram', $mk('instagram/create'))
            ->withIcon('instagram')
            ->isActive(
                $startsWith('instagram/create')
                || $startsWith('instagram/history')
                || $startsWith('instagram/carousel')
                || $startsWith('publish/instagram')
                || $isExact('instagram')
            );

        $channels->addItem('Facebook', $mk('publish/facebook'))
            ->withIcon('facebook')
            ->isActive(
                $startsWith('publish/facebook')
                || $isExact('facebook')
            );

        // ── Settings (admin / config) ───────────────────────────
        $settings = $sidebar->addHeader('Settings')->addBasicList();

        $settings->addItem('OpenAI', $mk('settings'))
            ->withIcon('key')
            ->isActive($startsWith('settings'));

        $settings->addItem('Token Usage', $mk('usage'))
            ->withIcon('chart-line')
            ->isActive($startsWith('usage'));

        $settings->addItem('Instagram', $mk('instagram'))
            ->withIcon('instagram')
            ->isActive($isExact('instagram'));

        $settings->addItem('Facebook', $mk('facebook'))
            ->withIcon('facebook')
            ->isActive($isExact('facebook'));

        ee()->view->sidebar = $sidebar->render();
    }
}
