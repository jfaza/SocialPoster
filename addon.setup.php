<?php

use JavidFazaeli\SocialPoster\Service\OpenAiClient;
use JavidFazaeli\SocialPoster\Service\ImageStorage;
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
        'templates' => function($addon) {
            return new TemplateManager();
        },
        'TemplateManager' => function($addon) {
            return ee('socialposter:templates');
        },
        'scheduler' => function($addon) {
            return new Scheduler(ee('socialposter:generator'));
        },
        'Scheduler' => function($addon) {
            return ee('socialposter:scheduler');
        },
        'publisher' => function($addon) {
            return new Publisher(ee('socialposter:generator'));
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
        'openai' => function($addon) {
            return new OpenAiClient();
        },
        'generator' => function($addon) {
            return new SocialPostGenerator(ee('socialposter:openai'), ee('socialposter:imageStorage'));
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
        'socialposter:run-schedule' => JavidFazaeli\SocialPoster\Commands\CommandRunSchedule::class,
    ],
 
 
];
