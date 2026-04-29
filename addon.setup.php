<?php

use JavidFazaeli\SocialPoster\Service\AnthropicClient;
use JavidFazaeli\SocialPoster\Service\CarouselService;
use JavidFazaeli\SocialPoster\Service\GdSlideRenderer;
use JavidFazaeli\SocialPoster\Service\IdeogramClient;
use JavidFazaeli\SocialPoster\Service\ImageStorage;
use JavidFazaeli\SocialPoster\Service\InstagramPostGenerator;
use JavidFazaeli\SocialPoster\Service\NewsFeedService;
use JavidFazaeli\SocialPoster\Service\OpenAiClient;
use JavidFazaeli\SocialPoster\Service\Publisher;
use JavidFazaeli\SocialPoster\Service\Scheduler;
use JavidFazaeli\SocialPoster\Service\SeoScorer;
use JavidFazaeli\SocialPoster\Service\SocialPostGenerator;
use JavidFazaeli\SocialPoster\Service\TemplateManager;

return [
    'name'              => 'SocialPoster',
    'description'       => 'Generate social post content and images with OpenAI.',
    'version'           => '1.0.0',
    'author'            => 'Javid Fazaeli',
    'author_url'        => 'https://fazaeli.dev',
    'namespace'         => 'JavidFazaeli\SocialPoster',
    'settings_exist'    => true,
    'services.singletons' => [
        'carousel' => function($addon) {
            return new CarouselService();
        },
        'CarouselService' => function($addon) {
            return ee('socialposter:carousel');
        },
        'templates' => function($addon) {
            return new TemplateManager();
        },
        'TemplateManager' => function($addon) {
            return ee('socialposter:templates');
        },
        'instagramGenerator' => function($addon) {
            return new InstagramPostGenerator(
                ee('socialposter:openai'),
                ee('socialposter:imageStorage'),
                ee('socialposter:gdSlideRenderer'),
                ee('socialposter:ideogramClient')
            );
        },
        'InstagramPostGenerator' => function($addon) {
            return ee('socialposter:instagramGenerator');
        },
        'scheduler' => function($addon) {
            return new Scheduler(ee('socialposter:generator'), ee('socialposter:instagramGenerator'));
        },
        'Scheduler' => function($addon) {
            return ee('socialposter:scheduler');
        },
        'publisher' => function($addon) {
            return new Publisher(ee('socialposter:generator'), ee('socialposter:instagramGenerator'));
        },
        'seoScorer' => function($addon) {
            return new SeoScorer();
        },
        'SeoScorer' => function($addon) {
            return ee('socialposter:seoScorer');
        },
        'Publisher' => function($addon) {
            return ee('socialposter:publisher');
        },
        'newsFeed' => function($addon) {
            return new NewsFeedService();
        },
        'NewsFeedService' => function($addon) {
            return ee('socialposter:newsFeed');
        },
        'openai' => function($addon) {
            return new OpenAiClient();
        },
        'anthropic' => function($addon) {
            return new AnthropicClient();
        },
        'gdSlideRenderer' => function($addon) {
            return new GdSlideRenderer();
        },
        'ideogramClient' => function($addon) {
            return new IdeogramClient();
        },
        'generator' => function($addon) {
            return new SocialPostGenerator(
                ee('socialposter:openai'),
                ee('socialposter:imageStorage'),
                ee('socialposter:gdSlideRenderer'),
                ee('socialposter:ideogramClient')
            );
        },
        'imageStorage' => function($addon) {
            return new ImageStorage();
        },
        'ImageStorage' => function($addon) {
            return ee('socialposter:imageStorage');
        },
        'SocialPostGenerator' => function($addon) {
            return ee('socialposter:generator');
        },
        'OpenAiClient' => function($addon) {
            return ee('socialposter:openai');
        },
    ],
    'commands' => [
        'socialposter:costs' => JavidFazaeli\SocialPoster\Commands\CommandCosts::class,
        'socialposter:run-schedule' => JavidFazaeli\SocialPoster\Commands\CommandRunSchedule::class,
        'socialposter:test-instagram' => JavidFazaeli\SocialPoster\Commands\CommandTestInstagram::class,
    ],
 
 
];
