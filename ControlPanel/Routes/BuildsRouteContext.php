<?php

namespace JavidFazaeli\SocialPoster\ControlPanel\Routes;

trait BuildsRouteContext
{
    private function cpUrl(string $path): string
    {
        return ee('CP/URL')->make('addons/settings/socialposter/' . ltrim($path, '/'))->compile();
    }

    private function coreRouteContext(): array
    {
        return [
            'index_url' => $this->cpUrl(''),
            'history_url' => $this->cpUrl('history'),
            'publish_url' => $this->cpUrl('publish'),
            'published_url' => $this->cpUrl('published'),
            'calendar_url' => $this->cpUrl('calendar'),
            'templates_url' => $this->cpUrl('templates'),
            'settings_url' => $this->cpUrl('settings'),
            'usage_url' => $this->cpUrl('usage'),
            'news_sources_url' => $this->cpUrl('news-sources'),
        ];
    }

    private function publishRouteContext(): array
    {
        return [
            'publish_instagram_url' => $this->cpUrl('publish/instagram'),
            'publish_facebook_url' => $this->cpUrl('publish/facebook'),
        ];
    }

    private function instagramRouteContext(): array
    {
        return [
            'general_url' => $this->cpUrl(''),
            'instagram_settings_url' => $this->cpUrl('instagram'),
            'instagram_history_url' => $this->cpUrl('instagram/history'),
            'instagram_create_url' => $this->cpUrl('instagram/create'),
            'instagram_generate_url' => $this->cpUrl('instagram/generate'),
            'instagram_carousel_url' => $this->cpUrl('instagram/carousel'),
            'instagram_news_sources_url' => $this->cpUrl('instagram/news-sources'),
            'publish_instagram_url' => $this->cpUrl('publish/instagram'),
        ];
    }

    private function facebookRouteContext(): array
    {
        return [
            'facebook_settings_url' => $this->cpUrl('facebook'),
            'publish_facebook_url' => $this->cpUrl('publish/facebook'),
        ];
    }
}
