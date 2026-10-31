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

        // ── Write ───────────────────────────────────────────────
        $write = $sidebar->addHeader('Write')->addBasicList();

        $write->addItem('New post', $mk('index'))
            ->withIcon('pencil')
            ->isActive(
                $startsWith('index')
                || rtrim($current, '/') === rtrim($this->base, '/')
            );

        $write->addItem('Instagram post', $mk('instagram/create'))
            ->withIcon('instagram')
            ->isActive($startsWith('instagram/create') || $startsWith('instagram/generate') || $startsWith('instagram/news-sources'));

        $write->addItem('Instagram carousel', $mk('instagram/carousel'))
            ->withIcon('images')
            ->isActive($startsWith('instagram/carousel'));

        // ── Review ──────────────────────────────────────────────
        $review = $sidebar->addHeader('Review')->addBasicList();

        $review->addItem('Drafts', $mk('history'))
            ->withIcon('file')
            ->isActive($startsWith('history') || $isExact('publish'));

        $review->addItem('Instagram drafts', $mk('instagram/history'))
            ->withIcon('file')
            ->isActive($startsWith('instagram/history'));

        $review->addItem('Calendar', $mk('calendar'))
            ->withIcon('calendar')
            ->isActive($startsWith('calendar'));

        $review->addItem('Facebook queue', $mk('publish/facebook'))
            ->withIcon('facebook')
            ->isActive($startsWith('publish/facebook'));

        $review->addItem('Instagram queue', $mk('publish/instagram'))
            ->withIcon('instagram')
            ->isActive($startsWith('publish/instagram'));

        $review->addItem('Published', $mk('published'))
            ->withIcon('check')
            ->isActive($isExact('published'));

        // ── Setup ───────────────────────────────────────────────
        $setup = $sidebar->addHeader('Setup')->addBasicList();

        $setup->addItem('Templates', $mk('templates'))
            ->withIcon('layer-group')
            ->isActive($startsWith('templates'));

        $setup->addItem('News sources', $mk('news-sources'))
            ->withIcon('rss')
            ->isActive($startsWith('news-sources'));

        $setup->addItem('OpenAI & models', $mk('settings'))
            ->withIcon('key')
            ->isActive($startsWith('settings'));

        $setup->addItem('Instagram account', $mk('instagram'))
            ->withIcon('instagram')
            ->isActive($isExact('instagram'));

        $setup->addItem('Facebook account', $mk('facebook'))
            ->withIcon('facebook')
            ->isActive($isExact('facebook'));

        $setup->addItem('Usage & costs', $mk('usage'))
            ->withIcon('chart-line')
            ->isActive($startsWith('usage'));

        ee()->view->sidebar = $sidebar->render();
    }
}
