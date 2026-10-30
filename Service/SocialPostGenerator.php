<?php

namespace JavidFazaeli\SocialPoster\Service;

class SocialPostGenerator
{
    private const DEFAULT_TEXT_MODEL = 'gpt-5.4';

    protected string $table = 'socialposter_generations';

    private OpenAiClient $openai;
    private ImageStorage $imageStorage;
    private GdSlideRenderer $gdRenderer;
    private IdeogramClient $ideogramClient;

    public function __construct(
        ?OpenAiClient $openai = null,
        ?ImageStorage $imageStorage = null,
        ?GdSlideRenderer $gdRenderer = null,
        ?IdeogramClient $ideogramClient = null
    ) {
        $this->openai         = $openai         ?: new OpenAiClient();
        $this->imageStorage   = $imageStorage   ?: new ImageStorage();
        $this->gdRenderer     = $gdRenderer     ?: new GdSlideRenderer();
        $this->ideogramClient = $ideogramClient ?: new IdeogramClient();
    }

    public function defaultSettings(): array
    {
        return [
            'api_key' => '',
            'admin_api_key' => '',
            'openai_project_id' => '',
            'anthropic_api_key' => '',
            'text_model' => self::DEFAULT_TEXT_MODEL,
            'image_model' => 'gpt-image-1.5',
            'image_size' => '1024x1024',
            'image_quality' => 'medium',
            'carousel_slides' => '6',
            'instagram_app_id' => '',
            'instagram_app_secret' => '',
            'instagram_ig_app_id' => '',
            'instagram_ig_app_secret' => '',
            'instagram_graph_version' => 'v24.0',
            'instagram_page_id' => '',
            'instagram_business_account_id' => '',
            'instagram_access_token' => '',
            'instagram_user_access_token' => '',
            'instagram_cover_provider' => 'openai',
            'instagram_slide_provider' => 'gd',
            'facebook_app_id' => '',
            'facebook_app_secret' => '',
            'facebook_graph_version' => 'v24.0',
            'facebook_page_id' => '',
            'facebook_access_token' => '',
            'facebook_user_access_token' => '',
            'ideogram_api_key' => '',
        ];
    }

    public function textModels(): array
    {
        return [
            'openai' => [
                'gpt-5.4'      => 'GPT-5.4',
                'gpt-5.4-mini' => 'GPT-5.4 Mini',
            ],
            'anthropic' => [
                'claude-opus-4-7'          => 'Claude Opus 4.7',
                'claude-sonnet-4-6'        => 'Claude Sonnet 4.6',
                'claude-haiku-4-5-20251001'=> 'Claude Haiku 4.5',
            ],
        ];
    }

    public function textModelsFlat(): array
    {
        return array_merge(...array_values($this->textModels()));
    }

    public function imageModels(): array
    {
        return [
            'gpt-image-2' => 'GPT Image 2',
            'gpt-image-1.5' => 'GPT Image 1.5',
            'chatgpt-image-latest' => 'ChatGPT Image Latest',
            'gpt-image-1' => 'GPT Image 1',
        ];
    }

    public function getSettings(): array
    {
        $settings = $this->defaultSettings();

        if (! ee()->db->table_exists('socialposter_settings')) {
            return $this->applyConfigOverrides($settings);
        }

        $query = ee()->db->get('socialposter_settings');
        foreach ($query->result_array() as $row) {
            $key = (string) $row['setting_key'];
            if (in_array($key, $this->secretSettingKeys(), true)) {
                $settings[$key] = $this->decrypt((string) $row['setting_value']);
            } elseif (array_key_exists($key, $settings)) {
                $settings[$key] = (string) $row['setting_value'];
            }
        }

        foreach ($this->secretSettingKeys() as $key) {
            if ($this->looksLikePlaceholderSecret($settings[$key])) {
                $settings[$key] = '';
            }
        }

        return $this->applyConfigOverrides($settings);
    }

    public function saveSettings(array $input): array
    {
        if (! ee()->db->table_exists('socialposter_settings')) {
            throw new \RuntimeException('SocialPoster settings table is missing. Install or update the add-on.');
        }

        $current = $this->getSettings();
        $apiKey = trim((string) ($input['api_key'] ?? ''));
        $adminApiKey = trim((string) ($input['admin_api_key'] ?? ''));
        $anthropicApiKey = trim((string) ($input['anthropic_api_key'] ?? ''));
        $instagramAppSecret = trim((string) ($input['instagram_app_secret'] ?? ''));
        $instagramIgAppSecret = trim((string) ($input['instagram_ig_app_secret'] ?? ''));
        $instagramAccessToken = trim((string) ($input['instagram_access_token'] ?? ''));
        $instagramUserAccessToken = trim((string) ($input['instagram_user_access_token'] ?? ''));
        $facebookAppSecret = trim((string) ($input['facebook_app_secret'] ?? ''));
        $facebookAccessToken = trim((string) ($input['facebook_access_token'] ?? ''));
        $facebookUserAccessToken = trim((string) ($input['facebook_user_access_token'] ?? ''));
        $ideogramApiKey = trim((string) ($input['ideogram_api_key'] ?? ''));
        $this->validateSecretInput($apiKey, 'OpenAI API Key');
        $this->validateSecretInput($adminApiKey, 'OpenAI Admin API Key');
        $this->validateSecretInput($anthropicApiKey, 'Anthropic API Key');
        $this->validateSecretInput($instagramAppSecret, 'Meta App Secret');
        $this->validateSecretInput($instagramIgAppSecret, 'Instagram App Secret');
        $this->validateSecretInput($instagramAccessToken, 'Instagram Access Token');
        $this->validateSecretInput($instagramUserAccessToken, 'Instagram User Access Token');
        $this->validateSecretInput($facebookAppSecret, 'Facebook App Secret');
        $this->validateSecretInput($facebookAccessToken, 'Facebook Page Access Token');
        $this->validateSecretInput($facebookUserAccessToken, 'Facebook User Access Token');
        $this->validateSecretInput($ideogramApiKey, 'Ideogram API Key');

        $carouselSlides = max(3, min(12, (int) ($input['carousel_slides'] ?? $current['carousel_slides'] ?? 6)));

        $settings = [
            'api_key' => $apiKey !== ''
                ? $apiKey
                : $current['api_key'],
            'admin_api_key' => $adminApiKey !== ''
                ? $adminApiKey
                : $current['admin_api_key'],
            'anthropic_api_key' => $anthropicApiKey !== ''
                ? $anthropicApiKey
                : $current['anthropic_api_key'],
            'openai_project_id' => trim((string) ($input['openai_project_id'] ?? $current['openai_project_id'])),
            'carousel_slides' => (string) $carouselSlides,
            'text_model' => trim((string) ($input['text_model'] ?? $current['text_model'] ?? self::DEFAULT_TEXT_MODEL)) ?: self::DEFAULT_TEXT_MODEL,
            'image_model' => array_key_exists(($input['image_model'] ?? ''), $this->imageModels())
                ? (string) $input['image_model']
                : (array_key_exists(($current['image_model'] ?? ''), $this->imageModels()) ? (string) $current['image_model'] : 'gpt-image-1.5'),
            'image_size' => in_array(($input['image_size'] ?? ''), ['1024x1024', '1024x1536', '1536x1024'], true)
                ? (string) $input['image_size']
                : (in_array(($current['image_size'] ?? ''), ['1024x1024', '1024x1536', '1536x1024'], true) ? (string) $current['image_size'] : '1024x1024'),
            'image_quality' => in_array(($input['image_quality'] ?? ''), ['low', 'medium', 'high'], true)
                ? (string) $input['image_quality']
                : (in_array(($current['image_quality'] ?? ''), ['low', 'medium', 'high'], true) ? (string) $current['image_quality'] : 'medium'),
            'instagram_app_id' => trim((string) ($input['instagram_app_id'] ?? $current['instagram_app_id'])),
            'instagram_app_secret' => $instagramAppSecret !== ''
                ? $instagramAppSecret
                : $current['instagram_app_secret'],
            'instagram_ig_app_id' => trim((string) ($input['instagram_ig_app_id'] ?? $current['instagram_ig_app_id'])),
            'instagram_ig_app_secret' => $instagramIgAppSecret !== ''
                ? $instagramIgAppSecret
                : $current['instagram_ig_app_secret'],
            'instagram_graph_version' => $this->normalizeGraphVersion((string) ($input['instagram_graph_version'] ?? $current['instagram_graph_version'])),
            'instagram_page_id' => trim((string) ($input['instagram_page_id'] ?? $current['instagram_page_id'])),
            'instagram_business_account_id' => trim((string) ($input['instagram_business_account_id'] ?? $current['instagram_business_account_id'])),
            'instagram_access_token' => $instagramAccessToken !== ''
                ? $instagramAccessToken
                : $current['instagram_access_token'],
            'instagram_user_access_token' => $instagramUserAccessToken !== ''
                ? $instagramUserAccessToken
                : $current['instagram_user_access_token'],
            'instagram_cover_provider' => in_array(($input['instagram_cover_provider'] ?? ''), ['openai', 'ideogram', 'gd', 'none'], true)
                ? (string) $input['instagram_cover_provider']
                : ($current['instagram_cover_provider'] ?? 'openai'),
            'instagram_slide_provider' => in_array(($input['instagram_slide_provider'] ?? ''), ['openai', 'ideogram', 'gd', 'none'], true)
                ? (string) $input['instagram_slide_provider']
                : ($current['instagram_slide_provider'] ?? 'gd'),
            'facebook_app_id' => trim((string) ($input['facebook_app_id'] ?? $current['facebook_app_id'])),
            'facebook_app_secret' => $facebookAppSecret !== ''
                ? $facebookAppSecret
                : $current['facebook_app_secret'],
            'facebook_graph_version' => $this->normalizeGraphVersion((string) ($input['facebook_graph_version'] ?? $current['facebook_graph_version'])),
            'facebook_page_id' => trim((string) ($input['facebook_page_id'] ?? $current['facebook_page_id'])),
            'facebook_access_token' => $facebookAccessToken !== ''
                ? $facebookAccessToken
                : $current['facebook_access_token'],
            'facebook_user_access_token' => $facebookUserAccessToken !== ''
                ? $facebookUserAccessToken
                : $current['facebook_user_access_token'],
            'ideogram_api_key' => $ideogramApiKey !== ''
                ? $ideogramApiKey
                : $current['ideogram_api_key'],
        ];

        if ($settings['admin_api_key'] !== '' && str_starts_with($settings['admin_api_key'], 'proj_')) {
            throw new \InvalidArgumentException('OpenAI Admin API Key contains a project ID. Paste the admin key secret into that field and put the proj_ value in OpenAI Project ID.');
        }

        if ($settings['admin_api_key'] !== '' && $settings['openai_project_id'] !== '' && hash_equals($settings['admin_api_key'], $settings['openai_project_id'])) {
            throw new \InvalidArgumentException('OpenAI Admin API Key and OpenAI Project ID cannot be the same value.');
        }

        foreach ($settings as $key => $value) {
            $stored = in_array($key, $this->secretSettingKeys(), true) ? $this->encrypt($value) : $value;
            $exists = ee()->db->where('setting_key', $key)->count_all_results('socialposter_settings') > 0;
            $row = [
                'setting_key' => $key,
                'setting_value' => $stored,
                'updated_at' => ee()->localize->now,
            ];

            if ($exists) {
                ee()->db->where('setting_key', $key)->update('socialposter_settings', $row);
            } else {
                $row['created_at'] = ee()->localize->now;
                ee()->db->insert('socialposter_settings', $row);
            }
        }

        return $settings;
    }

    private function applyConfigOverrides(array $settings): array
    {
        $overrides = [
            'api_key' => ['socialposter_openai_api_key', 'openai_api_key'],
            'admin_api_key' => ['socialposter_openai_admin_api_key', 'openai_admin_api_key'],
            'openai_project_id' => ['socialposter_openai_project_id', 'openai_project_id'],
            'instagram_app_id' => ['socialposter_instagram_app_id', 'instagram_app_id'],
            'instagram_app_secret' => ['socialposter_instagram_app_secret', 'instagram_app_secret'],
            'instagram_ig_app_id' => ['socialposter_instagram_ig_app_id', 'instagram_ig_app_id'],
            'instagram_ig_app_secret' => ['socialposter_instagram_ig_app_secret', 'instagram_ig_app_secret'],
            'instagram_graph_version' => ['socialposter_instagram_graph_version', 'instagram_graph_version'],
            'instagram_page_id' => ['socialposter_instagram_page_id', 'instagram_page_id'],
            'instagram_business_account_id' => ['socialposter_instagram_business_account_id', 'instagram_business_account_id'],
            'instagram_access_token' => ['socialposter_instagram_access_token', 'instagram_access_token'],
            'instagram_user_access_token' => ['socialposter_instagram_user_access_token', 'instagram_user_access_token'],
            'facebook_app_id' => ['socialposter_facebook_app_id', 'facebook_app_id'],
            'facebook_app_secret' => ['socialposter_facebook_app_secret', 'facebook_app_secret'],
            'facebook_graph_version' => ['socialposter_facebook_graph_version', 'facebook_graph_version'],
            'facebook_page_id' => ['socialposter_facebook_page_id', 'facebook_page_id'],
            'facebook_access_token' => ['socialposter_facebook_access_token', 'facebook_access_token'],
            'facebook_user_access_token' => ['socialposter_facebook_user_access_token', 'facebook_user_access_token'],
            'ideogram_api_key' => ['socialposter_ideogram_api_key', 'ideogram_api_key'],
        ];

        foreach ($overrides as $setting => $configKeys) {
            foreach ($configKeys as $configKey) {
                $value = $this->configString($configKey);
                if ($value !== '') {
                    $settings[$setting] = $value;
                    break;
                }
            }
        }

        return $settings;
    }

    private function configString(string $key): string
    {
        $value = ee()->config->item($key);
        return is_scalar($value) ? trim((string) $value) : '';
    }

    private function validateSecretInput(string $value, string $label): void
    {
        if ($value === '') {
            return;
        }

        if ($this->looksLikePlaceholderSecret($value)) {
            throw new \InvalidArgumentException($label . ' must be the actual key secret, not the field label or placeholder text.');
        }
    }

    private function secretSettingKeys(): array
    {
        return [
            'api_key',
            'admin_api_key',
            'anthropic_api_key',
            'instagram_app_secret',
            'instagram_ig_app_secret',
            'instagram_access_token',
            'instagram_user_access_token',
            'facebook_app_secret',
            'facebook_access_token',
            'facebook_user_access_token',
            'ideogram_api_key',
        ];
    }

    private function normalizeGraphVersion(string $version): string
    {
        $version = strtolower(trim($version));
        if ($version === '') {
            return 'v24.0';
        }

        if (preg_match('/^\d+(?:\.\d+)?$/', $version)) {
            $version = 'v' . $version;
        }

        return preg_match('/^v\d+(?:\.\d+)?$/', $version) ? $version : 'v24.0';
    }

    private function looksLikePlaceholderSecret(string $value): bool
    {
        $normalized = strtolower(trim($value));
        if ($normalized === '') {
            return false;
        }

        return in_array($normalized, [
            'openai api key',
            'openai admin api key',
            'api key',
            'admin api key',
            'instagram app secret',
            'instagram access token',
            'facebook app secret',
            'facebook page access token',
            'facebook user access token',
            'facebook access token',
            'app secret',
            'access token',
            'optional app secret',
            'long-lived token with publishing permissions',
            'sk-...',
            'sk-admin-...',
        ], true);
    }

    public function generate(string $prompt, array $meta = []): array
    {
        $prompt = trim($prompt);
        if ($prompt === '') {
            throw new \InvalidArgumentException('Please enter a prompt.');
        }

        $settings = $this->getSettings();
        if (trim($settings['api_key']) === '') {
            throw new \RuntimeException('OpenAI API key is not configured.');
        }

        $templateId = (int) ($meta['template_id'] ?? 0);
        $templates = ee('socialposter:templates');
        $template = $templateId > 0 ? $templates->find($templateId) : null;
        $templateProfile = $templates->generationProfileForTemplate($template);
        $templateInstructions = $template ? $templates->instructionsFor($templateId) : '';
        $baseGenerationPrompt = $this->assembleGenerationPrompt($prompt, $templateInstructions);
        $requestedSlideCount = $this->requestedInstagramSlideCount($prompt, $template);
        $recentGenerations = $this->normalizeRecentGenerationMemory((array) ($meta['recent_generations'] ?? []));
        if ($recentGenerations === [] && ($templateId > 0 || $templateProfile !== '')) {
            $recentGenerations = $this->recentGenerationMemory([
                'template_id' => $templateId,
                'generation_profile' => $templateProfile,
                'limit' => 18,
                'source' => (string) ($meta['source'] ?? ''),
            ]);
        }
        $maxTopicAttempts = max(1, min(3, (int) ($meta['max_topic_attempts'] ?? 1)));
        $siteContext = $this->siteContextInstructions($template);
        $requestedImageBrief = trim((string) ($meta['image_brief'] ?? ''));
        $content = [];
        $duplicateMatch = null;
        $lastDuplicateMatchTitle = '';
        $topicSource = trim((string) ($meta['topic_source'] ?? 'manual'));
        $topicAttemptCount = 1;

        for ($attempt = 1; $attempt <= $maxTopicAttempts; $attempt++) {
            $topicAttemptCount = $attempt;
            $generationPrompt = $baseGenerationPrompt;
            if ($requestedSlideCount !== null) {
                $generationPrompt .= "\n\nInstagram slide count requirement:\nReturn exactly {$requestedSlideCount} slides. "
                    . "table_of_contents must contain exactly {$requestedSlideCount} slide titles, one per slide. "
                    . "post_text must contain exactly {$requestedSlideCount} slide blocks before the Instagram Caption section.";
            }
            if ($siteContext !== '') {
                $generationPrompt .= "\n\n" . $siteContext;
            }
            $freshnessContract = $templates->profileFreshnessContract($templateProfile);
            if ($freshnessContract !== '') {
                $generationPrompt .= "\n\n" . $freshnessContract;
            }
            if ($requestedImageBrief !== '') {
                $generationPrompt .= "\n\nImage brief to follow:\n" . $requestedImageBrief;
            }
            $generationPrompt .= $this->recentTopicGuardPrompt($recentGenerations, $attempt);

            $content = $this->generateStructuredContent($settings['api_key'], $settings['text_model'], $generationPrompt, $template, $prompt);
            $this->validateGeneratedContent($content, $template, $prompt);

            $duplicateMatch = $this->duplicateTopicMatch($content, $recentGenerations);
            if (! $duplicateMatch) {
                break;
            }

            $lastDuplicateMatchTitle = trim((string) ($duplicateMatch['title'] ?? ''));

            if ($attempt >= $maxTopicAttempts) {
                throw new \RuntimeException(
                    'Topic generation kept landing too close to a recent post (' . $duplicateMatch['title'] . '). '
                    . 'Refresh hot articles or add a planned topic to force a different angle.',
                    409
                );
            }
        }

        $imageBrief = $requestedImageBrief !== '' ? $requestedImageBrief : trim((string) ($content['image_brief'] ?? ''));
        if ($imageBrief === '') {
            $slideImageBriefs = $this->instagramSlideImageBriefsFromContent($content);
            $imageBrief = trim((string) ($slideImageBriefs[0] ?? ''));
        }
        $imagePrompt = $this->composeImagePrompt(trim((string) ($content['image_prompt'] ?? $prompt)), $imageBrief, $template);
        $imageSettings = $this->resolvedImageSettings($settings, $template);
        $imageBase64 = $this->openai->generateImage(
            $settings['api_key'],
            $imageSettings['image_model'],
            $imagePrompt,
            $imageSettings['image_size'],
            $imageSettings['image_quality']
        );

        $imagePath = $this->imageStorage->saveBase64Png(
            $imageBase64,
            (string) ($content['title'] ?? 'social-post'),
            $this->imageResizeConfig($template)
        );
        $this->assertStoredImageExists($imagePath);

        $slidePaths = [];
        if (
            empty($meta['skip_slide_images'])
            && $this->isInstagramTemplate($template)
            && ! empty($content['slides'])
            && is_array($content['slides'])
        ) {
            $slidePaths = $this->generateSlideImages($content['slides'], $settings['api_key'], $imageSettings);
        }

        $record = [
            'site_id' => (int) ee()->config->item('site_id'),
            'member_id' => $this->memberId(),
            'prompt' => $prompt,
            'title' => (string) ($content['title'] ?? ''),
            'post_text' => (string) ($content['post_text'] ?? ''),
            'intro_text' => (string) ($content['intro_text'] ?? ''),
            'table_of_contents' => json_encode($content['table_of_contents'] ?? []),
            'keywords' => json_encode($content['seo_keywords'] ?? []),
            'category' => (string) ($content['category'] ?? ''),
            'hashtags' => json_encode($this->normalizeHashtags((array) ($content['hashtags'] ?? []))),
            'external_link' => (string) ($content['external_link'] ?? ''),
            'internal_link' => (string) ($content['internal_link'] ?? ''),
            'recommended_topics' => json_encode($content['recommended_topics'] ?? []),
            'image_brief' => $imageBrief,
            'image_prompt' => $imagePrompt,
            'image_path' => $imagePath,
            'raw_response' => json_encode($content['_raw_response'] ?? []),
            'created_at' => ee()->localize->now,
        ];

        if (ee()->db->field_exists('schedule_id', $this->table)) {
            $record['schedule_id'] = (int) ($meta['schedule_id'] ?? 0);
        }

        if (ee()->db->field_exists('scheduled_for', $this->table)) {
            $record['scheduled_for'] = (int) ($meta['scheduled_for'] ?? 0);
        }

        if (ee()->db->field_exists('source', $this->table)) {
            $record['source'] = (string) ($meta['source'] ?? 'manual');
        }

        if (ee()->db->field_exists('template_id', $this->table)) {
            $record['template_id'] = $templateId;
        }

        foreach (['image_model', 'image_size', 'image_quality'] as $field) {
            if (ee()->db->field_exists($field, $this->table)) {
                $record[$field] = (string) $imageSettings[$field];
            }
        }

        if (ee()->db->field_exists('slide_images', $this->table)) {
            $record['slide_images'] = json_encode($slidePaths);
        }

        ee()->db->insert($this->table, $record);
        $record['id'] = (int) ee()->db->insert_id();
        $record['topic_attempt_count'] = $topicAttemptCount;
        $record['duplicate_retry_count'] = max(0, $topicAttemptCount - 1);
        $record['duplicate_match_title'] = $lastDuplicateMatchTitle;
        $record['topic_source'] = $topicSource;
        $record['accepted_after_retry'] = $topicAttemptCount > 1;

        return $this->normalizeRecord($record);
    }

    public function recentGenerationMemory(array $options = []): array
    {
        if (! ee()->db->table_exists($this->table)) {
            return [];
        }

        $limit = max(1, min(50, (int) ($options['limit'] ?? 18)));
        $templateId = (int) ($options['template_id'] ?? 0);
        $generationProfile = trim((string) ($options['generation_profile'] ?? ''));
        $source = trim((string) ($options['source'] ?? ''));
        $templates = ee('socialposter:templates');

        if (ee()->db->field_exists('template_id', $this->table)) {
            if ($generationProfile === '' && $templateId > 0) {
                $generationProfile = $templates->generationProfileForTemplate($templates->find($templateId));
            }
        }
        $profileTemplateIds = $generationProfile !== '' ? $templates->templateIdsForProfile($generationProfile) : [];

        $db = ee()->db->select('id, title, prompt, created_at');

        if (ee()->db->field_exists('template_id', $this->table)) {
            if ($profileTemplateIds !== []) {
                $db->where_in('template_id', $profileTemplateIds);
            } elseif ($templateId > 0) {
                $db->where('template_id', $templateId);
            }
        }

        if ($source !== '' && ee()->db->field_exists('source', $this->table)) {
            $db->where('source', $source);
        }

        $rows = $db
            ->order_by('created_at', 'DESC')
            ->limit($limit)
            ->get($this->table)
            ->result_array();

        return $this->normalizeRecentGenerationMemory($rows);
    }

    public function recentTopicGuardText(array $recentGenerations, int $attempt = 1): string
    {
        return $this->recentTopicGuardPrompt($this->normalizeRecentGenerationMemory($recentGenerations), $attempt);
    }

    public function duplicateTopicMatchText(string $candidate, array $recentGenerations): ?array
    {
        return $this->duplicateTextMatch(trim($candidate), $this->normalizeRecentGenerationMemory($recentGenerations));
    }

    public function generateNewsPost(array $article, array $options = []): array
    {
        $settings = $this->getSettings();
        $apiKey   = trim((string) ($settings['api_key'] ?? ''));
        if ($apiKey === '') {
            throw new \RuntimeException('OpenAI API key is not configured.');
        }

        $articleTitle   = trim((string) ($article['title'] ?? ''));
        $articleUrl     = trim((string) ($article['url'] ?? ''));
        $articleExcerpt = trim((string) ($article['excerpt'] ?? ''));
        $articleImageUrl = trim((string) ($article['image_url'] ?? ''));

        if ($articleTitle === '') {
            throw new \InvalidArgumentException('Article title is required.');
        }

        $captionHint = trim((string) ($options['caption_hint'] ?? ''));
        $imageChoice = trim((string) ($options['image_choice'] ?? 'none'));
        $imageHint   = trim((string) ($options['image_hint'] ?? ''));

        $userPrompt = implode("\n", array_filter([
            'Article title: ' . $articleTitle,
            $articleUrl     !== '' ? 'Article URL: ' . $articleUrl : '',
            $articleExcerpt !== '' ? 'Article excerpt: ' . $articleExcerpt : '',
            $captionHint    !== '' ? "\nCaption instructions:\n" . $captionHint : '',
        ]));

        $content   = $this->openai->generateNewsPostText($apiKey, $settings['text_model'], $userPrompt);
        $postTitle = trim((string) ($content['title'] ?? $articleTitle));
        $caption   = trim((string) ($content['caption'] ?? ''));
        $hashtags  = $this->normalizeHashtags((array) ($content['hashtags'] ?? []));

        $imagePath       = '';
        $imagePromptUsed = '';
        $imageSettings   = $this->resolvedImageSettings($settings, null);

        if ($imageChoice === 'article' && $articleImageUrl !== '') {
            try {
                $imagePath = $this->downloadAndSaveImage($articleImageUrl, $postTitle);
            } catch (\Throwable $e) {
                // non-fatal — post is created without image
            }
        } elseif ($imageChoice === 'generate') {
            $basePrompt      = $imageHint !== '' ? $imageHint : ('Visual for news article: ' . $articleTitle);
            $imagePromptUsed = $this->composeImagePrompt($basePrompt, '', null);
            $base64          = $this->openai->generateImage(
                $apiKey,
                $imageSettings['image_model'],
                $imagePromptUsed,
                $imageSettings['image_size'],
                $imageSettings['image_quality']
            );
            $imagePath = $this->imageStorage->saveBase64Png($base64, $postTitle);
            $this->assertStoredImageExists($imagePath);
        }

        $record = [
            'site_id'             => (int) ee()->config->item('site_id'),
            'member_id'           => $this->memberId(),
            'prompt'              => $articleUrl !== '' ? $articleUrl : $articleTitle,
            'title'               => $postTitle,
            'post_text'           => $caption,
            'intro_text'          => $articleExcerpt,
            'table_of_contents'   => '[]',
            'keywords'            => '[]',
            'category'            => '',
            'hashtags'            => json_encode($hashtags),
            'external_link'       => $articleUrl,
            'internal_link'       => '',
            'recommended_topics'  => '[]',
            'image_brief'         => $imageHint,
            'image_prompt'        => $imagePromptUsed,
            'image_path'          => $imagePath,
            'raw_response'        => '{}',
            'created_at'          => ee()->localize->now,
        ];

        if (ee()->db->field_exists('source', $this->table)) {
            $record['source'] = 'news';
        }
        foreach (['image_model', 'image_size', 'image_quality'] as $field) {
            if (ee()->db->field_exists($field, $this->table)) {
                $record[$field] = (string) $imageSettings[$field];
            }
        }

        ee()->db->insert($this->table, $record);
        $record['id'] = (int) ee()->db->insert_id();

        return $this->normalizeRecord($record);
    }

    private function downloadAndSaveImage(string $url, string $title): string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_USERAGENT      => 'SocialPoster/1.0',
        ]);

        $body   = curl_exec($ch);
        $error  = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false || $body === '' || $status < 200 || $status >= 300) {
            throw new \RuntimeException('Could not download article image: ' . ($error ?: 'HTTP ' . $status));
        }

        // Convert to PNG via GD so we always write a valid PNG file
        $binary = (string) $body;
        if (function_exists('imagecreatefromstring')) {
            $src = @imagecreatefromstring($binary);
            if ($src !== false) {
                ob_start();
                imagepng($src);
                $converted = (string) ob_get_clean();
                imagedestroy($src);
                if ($converted !== '') {
                    $binary = $converted;
                }
            }
        }

        return $this->imageStorage->saveBase64Png(base64_encode($binary), $title);
    }

    private function assembleGenerationPrompt(string $prompt, string $templateInstructions = ''): string
    {
        return trim(implode("\n\n", array_filter([
            trim($templateInstructions),
            "User prompt:\n" . trim($prompt),
        ], static fn($segment) => trim((string) $segment) !== '')));
    }

    public function generateSingleImagePost(string $topic, array $options = []): array
    {
        $topic = trim($topic);
        if ($topic === '') {
            throw new \InvalidArgumentException('Please enter a topic.');
        }

        $settings = $this->getSettings();
        $apiKey   = trim((string) ($settings['api_key'] ?? ''));
        if ($apiKey === '') {
            throw new \RuntimeException('OpenAI API key is not configured.');
        }

        $recentGenerations = $this->normalizeRecentGenerationMemory((array) ($options['recent_generations'] ?? []));
        $duplicateTopic = $this->duplicateTextMatch($topic, $recentGenerations);
        if ($duplicateTopic) {
            throw new \RuntimeException(
                'That topic is too close to a recent post (' . $duplicateTopic['title'] . '). '
                . 'Please choose a more distinct angle or topic.'
            );
        }

        $captionHint = trim((string) ($options['caption_prompt'] ?? ''));
        $imageHint   = trim((string) ($options['image_prompt'] ?? ''));
        $imageMode   = trim((string) ($options['image_prompt_mode'] ?? 'combined'));
        if (! in_array($imageMode, ['combined', 'image_only'], true)) {
            $imageMode = 'combined';
        }

        $userPrompt = 'Topic: ' . $topic;
        if ($captionHint !== '') {
            $userPrompt .= "\n\nCaption instructions:\n" . $captionHint;
        }
        if ($imageHint !== '' && $imageMode === 'combined') {
            $userPrompt .= "\n\nImage instructions:\n" . $imageHint;
        }

        $content      = $this->openai->generateSingleImageText($apiKey, $settings['text_model'], $userPrompt);
        $caption      = trim((string) ($content['caption'] ?? ''));
        $hashtags     = $this->normalizeHashtags((array) ($content['hashtags'] ?? []));
        $rawImgPrompt = trim((string) ($content['image_prompt'] ?? $topic));

        if ($imageMode === 'image_only' && $imageHint !== '') {
            $imagePrompt = $this->composeImagePrompt($imageHint, '', null);
        } else {
            $combinedImagePrompt = implode("\n\n", array_filter([
                'Instagram single-image post visual for this topic: ' . $topic,
                $caption !== '' ? 'Caption context: ' . $caption : '',
                $imageHint !== '' ? 'User image instructions: ' . $imageHint : '',
                $rawImgPrompt !== '' ? 'AI visual direction: ' . $rawImgPrompt : '',
            ], fn($line) => trim($line) !== ''));
            $imagePrompt = $this->composeImagePrompt($combinedImagePrompt, '', null);
        }
        $imageSettings = $this->resolvedImageSettings($settings, null);

        $imageBase64 = $this->openai->generateImage(
            $apiKey,
            $imageSettings['image_model'],
            $imagePrompt,
            $imageSettings['image_size'],
            $imageSettings['image_quality']
        );

        $imagePath = $this->imageStorage->saveBase64Png($imageBase64, $topic);
        $this->assertStoredImageExists($imagePath);

        $record = [
            'site_id'    => (int) ee()->config->item('site_id'),
            'member_id'  => $this->memberId(),
            'prompt'     => $topic,
            'title'      => $topic,
            'post_text'  => $caption,
            'intro_text' => '',
            'table_of_contents'   => '[]',
            'keywords'            => '[]',
            'category'            => '',
            'hashtags'            => json_encode($hashtags),
            'external_link'       => '',
            'internal_link'       => '',
            'recommended_topics'  => '[]',
            'image_brief'         => $imageHint,
            'image_prompt'        => $imagePrompt,
            'image_path'          => $imagePath,
            'raw_response'        => '{}',
            'created_at'          => ee()->localize->now,
        ];

        if (ee()->db->field_exists('source', $this->table)) {
            $record['source'] = (string) ($options['source'] ?? 'manual');
        }

        foreach (['image_model', 'image_size', 'image_quality'] as $field) {
            if (ee()->db->field_exists($field, $this->table)) {
                $record[$field] = (string) $imageSettings[$field];
            }
        }

        ee()->db->insert($this->table, $record);
        $record['id'] = (int) ee()->db->insert_id();

        return $this->normalizeRecord($record);
    }

    public function assistSingleImagePost(array $articles, array $options = []): array
    {
        $settings = $this->getSettings();
        $apiKey   = trim((string) ($settings['api_key'] ?? ''));
        if ($apiKey === '') {
            throw new \RuntimeException('OpenAI API key is not configured.');
        }

        $articles = array_values(array_filter($articles, 'is_array'));
        if (! $articles) {
            throw new \InvalidArgumentException('No recent articles were found for this source.');
        }

        $sourceName = trim((string) ($options['source_name'] ?? 'Selected source'));
        $tone       = trim((string) ($options['tone'] ?? 'practical, polished, conversational'));
        $goal       = trim((string) ($options['goal'] ?? 'educate and invite engagement'));
        $audience   = trim((string) ($options['audience'] ?? 'developers, founders, and AI-curious business owners'));
        $profile = trim((string) ($options['generation_profile'] ?? ''));
        $templates = ee('socialposter:templates');
        $planningContract = $templates->profilePlanningContract($profile);
        $freshnessContract = $templates->profileFreshnessContract($profile);

        $articleLines = [];
        foreach (array_slice($articles, 0, 8) as $idx => $article) {
            $title   = trim((string) ($article['title'] ?? ''));
            $excerpt = trim((string) ($article['excerpt'] ?? ''));
            $url     = trim((string) ($article['url'] ?? ''));
            if ($title === '') {
                continue;
            }
            $articleLines[] = implode("\n", array_filter([
                'Article ' . ($idx + 1) . ': ' . $title,
                $excerpt !== '' ? 'Excerpt: ' . mb_strimwidth($excerpt, 0, 260, '...') : '',
                $url !== '' ? 'URL: ' . $url : '',
            ]));
        }

        if (! $articleLines) {
            throw new \InvalidArgumentException('No usable article titles were found for this source.');
        }

        $prompt = implode("\n\n", [
            'Source: ' . $sourceName,
            'Audience: ' . $audience,
            'Tone: ' . $tone,
            'Goal: ' . $goal,
            $profile !== '' ? 'Behavior profile: ' . $templates->generationProfileLabel($profile) : '',
            $planningContract,
            $freshnessContract,
            "Recent source context:\n" . implode("\n\n", $articleLines),
            'Create one Instagram single-image post idea that connects the strongest recent article/theme to the audience.',
        ]);

        $assistModel = trim((string) ($settings['text_model'] ?? 'gpt-5.4'));
        if ($assistModel === '' || str_starts_with($assistModel, 'claude-')) {
            $assistModel = 'gpt-5.4';
        }

        $recentGenerations = $this->normalizeRecentGenerationMemory((array) ($options['recent_generations'] ?? []));
        $assist = [];
        $topic = '';

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $attemptPrompt = $prompt . $this->recentTopicGuardPrompt($recentGenerations, $attempt);
            $assist = $this->openai->generateSingleImageAssist($apiKey, $assistModel, $attemptPrompt);
            $topic = trim((string) ($assist['topic'] ?? ''));

            if ($topic === '' || ! $this->duplicateTextMatch($topic, $recentGenerations)) {
                break;
            }

            if ($attempt >= 3) {
                throw new \RuntimeException('AI kept suggesting a topic too close to a recent Instagram post. Try a different source or refresh the feed.');
            }
        }

        return [
            'topic' => $topic,
            'caption_prompt' => trim((string) ($assist['caption_prompt'] ?? '')),
            'image_prompt' => trim((string) ($assist['image_prompt'] ?? '')),
            'source_context' => implode(' | ', array_slice(array_map(
                fn($article) => trim((string) ($article['title'] ?? '')),
                $articles
            ), 0, 3)),
        ];
    }

    public function plan(string $mode, array $input = []): array
    {
        $settings = $this->getSettings();
        if (trim($settings['api_key']) === '') {
            throw new \RuntimeException('OpenAI API key is not configured.');
        }

        $templateId = (int) ($input['template_id'] ?? 0);
        $templates = ee('socialposter:templates');
        $template = $templateId > 0 ? $templates->find($templateId) : null;
        $profile = $templates->generationProfileForTemplate($template);
        $platform = trim((string) ($template['platform'] ?? ''));
        $kind = in_array($mode, ['title', 'brief', 'images', 'creative'], true) ? $mode : 'brief';
        $planningContract = $templates->profilePlanningContract($profile);
        $freshnessContract = $templates->profileFreshnessContract($profile);

        $prompt = implode("\n", array_filter([
            'Planning task: Generate ' . $kind . ' support for a SocialPoster generation form.',
            'Template: ' . trim((string) ($template['title'] ?? 'No template')),
            'Platform: ' . ($platform !== '' ? $platform : 'General'),
            $profile !== '' ? 'Behavior profile: ' . $templates->generationProfileLabel($profile) : '',
            'Current title: ' . trim((string) ($input['title'] ?? '')),
            'Current brief: ' . trim((string) ($input['brief'] ?? '')),
            'Current keywords: ' . trim((string) ($input['keywords'] ?? '')),
            'Current prompt/topic: ' . trim((string) ($input['prompt'] ?? '')),
            'Image plan context: ' . trim((string) ($input['image_plan'] ?? '')),
            $planningContract,
            $freshnessContract,
            $platform === 'Instagram'
                ? 'For Instagram, return a clear topic, a useful slide outline with Slide 1, Slide 2, and so on, a caption direction, a hashtag direction, and one square cover visual direction that fits the whole carousel. Do not return separate image descriptions for every slide.'
                : 'For images, return 1-3 concise image descriptions suitable for the selected content type.',
            $kind === 'creative'
                ? 'Focus on the full creative brief. Improve or fill title, brief, keywords, topic, slide outline, caption direction, hashtag direction, and the cover visual direction.'
                : 'Return a strong title, a concise brief, 5-8 keywords, and image descriptions. If a field already exists, improve it instead of ignoring it.',
        ], fn($line) => trim($line) !== ''));

        $plan = $this->openai->generatePlan($settings['api_key'], $settings['text_model'], $prompt);

        return [
            'title' => trim((string) ($plan['title'] ?? '')),
            'brief' => trim((string) ($plan['brief'] ?? '')),
            'keywords' => array_values(array_filter(array_map('trim', (array) ($plan['keywords'] ?? [])))),
            'topic' => trim((string) ($plan['topic'] ?? '')),
            'slide_outline' => trim((string) ($plan['slide_outline'] ?? '')),
            'caption_direction' => trim((string) ($plan['caption_direction'] ?? '')),
            'hashtag_direction' => trim((string) ($plan['hashtag_direction'] ?? '')),
            'images' => array_values(array_filter((array) ($plan['images'] ?? []), 'is_array')),
        ];
    }

    public function latest(int $limit = 20): array
    {
        if (! ee()->db->table_exists($this->table)) {
            return [];
        }

        $rows = ee()->db
            ->order_by('created_at', 'DESC')
            ->limit($limit)
            ->get($this->table)
            ->result_array();

        return array_map([$this, 'normalizeRecord'], $rows);
    }

    public function tokenUsage(int $limit = 100): array
    {
        $summary = [
            'requests' => 0,
            'input_tokens' => 0,
            'output_tokens' => 0,
            'total_tokens' => 0,
            'cached_tokens' => 0,
            'reasoning_tokens' => 0,
        ];

        if (! ee()->db->table_exists($this->table)) {
            return [
                'summary' => $summary,
                'rows' => [],
                'costs' => $this->actualOpenAiCosts(),
            ];
        }

        $select = ['id', 'title', 'raw_response', 'created_at', 'image_path'];
        foreach (['source', 'template_id', 'image_model', 'image_size', 'image_quality'] as $field) {
            if (ee()->db->field_exists($field, $this->table)) {
                $select[] = $field;
            }
        }

        $rows = ee()->db
            ->select($select)
            ->order_by('created_at', 'DESC')
            ->limit($limit)
            ->get($this->table)
            ->result_array();

        $usageRows = [];
        foreach ($rows as $row) {
            $usage = $this->usageFromRawResponse((string) ($row['raw_response'] ?? ''));
            if ($usage['total_tokens'] > 0) {
                $summary['requests']++;
            }

            foreach (['input_tokens', 'output_tokens', 'total_tokens', 'cached_tokens', 'reasoning_tokens'] as $key) {
                $summary[$key] += $usage[$key];
            }

            $usageRows[] = array_merge($row, $usage);
        }

        return [
            'summary' => $summary,
            'rows' => $usageRows,
            'costs' => $this->actualOpenAiCosts(),
        ];
    }

    public function find(int $id): ?array
    {
        if (! ee()->db->table_exists($this->table)) {
            return null;
        }

        $row = ee()->db->where('id', $id)->get($this->table)->row_array();
        return $row ? $this->normalizeRecord($row) : null;
    }

    public function update(int $id, array $input): bool
    {
        $row = ee()->db->where('id', $id)->get($this->table)->row_array();
        if (! $row) {
            return false;
        }

        $record = [
            'title' => trim((string) ($input['title'] ?? '')),
            'prompt' => trim((string) ($input['prompt'] ?? '')),
            'post_text' => (string) ($input['post_text'] ?? ''),
            'intro_text' => (string) ($input['intro_text'] ?? ''),
            'table_of_contents' => json_encode($this->linesToList((string) ($input['table_of_contents'] ?? ''))),
            'keywords' => json_encode($this->csvToList((string) ($input['keywords'] ?? ''))),
            'category' => trim((string) ($input['category'] ?? '')),
            'hashtags' => json_encode($this->normalizeHashtags($this->csvToList((string) ($input['hashtags'] ?? '')))),
            'external_link' => trim((string) ($input['external_link'] ?? '')),
            'internal_link' => trim((string) ($input['internal_link'] ?? '')),
            'recommended_topics' => json_encode($this->linesToList((string) ($input['recommended_topics'] ?? ''))),
        ];

        if (array_key_exists('image_brief', $input)) {
            $record['image_brief'] = trim((string) $input['image_brief']);
        }

        if (array_key_exists('image_prompt', $input)) {
            $record['image_prompt'] = trim((string) $input['image_prompt']);
        }

        if (ee()->db->field_exists('template_id', $this->table)) {
            $record['template_id'] = (int) ($input['template_id'] ?? 0);
        }

        ee()->db->where('id', $id)->update($this->table, $record);

        return true;
    }

    public function regenerateImage(int $id, array $input): ?array
    {
        $row = ee()->db->where('id', $id)->get($this->table)->row_array();
        if (! $row) {
            return null;
        }

        $settings = $this->getSettings();
        if (trim($settings['api_key']) === '') {
            throw new \RuntimeException('OpenAI API key is not configured.');
        }

        $imagePrompt = trim((string) ($input['image_prompt'] ?? $row['image_prompt'] ?? ''));
        $imageBrief = trim((string) ($input['image_brief'] ?? $row['image_brief'] ?? ''));
        if ($imagePrompt === '') {
            $imagePrompt = trim((string) ($row['prompt'] ?? $row['title'] ?? 'social post image'));
        }

        $templateId = (int) ($row['template_id'] ?? 0);
        $template = $templateId > 0 ? ee('socialposter:templates')->find($templateId) : null;
        $composedPrompt = $this->composeImagePrompt($this->baseImagePrompt($imagePrompt), $imageBrief, $template);
        $imageSettings = $this->resolvedImageSettings($settings, $template);
        $imageBase64 = $this->openai->generateImage(
            $settings['api_key'],
            $imageSettings['image_model'],
            $composedPrompt,
            $imageSettings['image_size'],
            $imageSettings['image_quality']
        );

        $imagePath = $this->imageStorage->saveBase64Png(
            $imageBase64,
            (string) ($row['title'] ?? 'social-post'),
            $this->imageResizeConfig($template)
        );
        $this->assertStoredImageExists($imagePath);

        ee()->db->where('id', $id)->update($this->table, [
            'image_brief' => $imageBrief,
            'image_prompt' => $composedPrompt,
            'image_path' => $imagePath,
        ] + $this->imageSettingsRecord($imageSettings));

        return $this->find($id);
    }

    public function delete(int $id): bool
    {
        $row = ee()->db->where('id', $id)->get($this->table)->row_array();
        if (! $row) {
            return false;
        }

        if (! empty($row['image_path'])) {
            $path = $this->imageStorage->publicPath((string) $row['image_path']);
            if (is_file($path)) {
                @unlink($path);
            }
        }

        ee()->db->where('id', $id)->delete($this->table);
        return true;
    }

    public function actionUrl(): string
    {
        $actionId = ee()->cp->fetch_action_id('Socialposter', 'GeneratePost');
        if (! $actionId) {
            return '';
        }

        return ee()->functions->fetch_site_index(0, 0) . QUERY_MARKER . 'ACT=' . $actionId;
    }

    public function csrfToken(): string
    {
        return ee()->functions->add_form_security_hash('{XID_HASH}');
    }

    public function tableName(): string
    {
        return $this->table;
    }

    public function contentType(): string
    {
        return 'website';
    }

    private function normalizeRecord(array $record): array
    {
        $record['table_of_contents'] = $this->decodeList($record['table_of_contents'] ?? []);
        $record['keywords'] = $this->decodeList($record['keywords'] ?? []);
        $record['hashtags'] = $this->decodeList($record['hashtags'] ?? []);
        $record['recommended_topics'] = $this->decodeList($record['recommended_topics'] ?? []);
        $record['image_url'] = $this->imageUrlForPath((string) ($record['image_path'] ?? ''));
        $record['image_missing'] = trim((string) ($record['image_path'] ?? '')) !== '' && $record['image_url'] === '';
        $record['internal_link_title'] = $this->internalLinkTitle((string) ($record['internal_link'] ?? ''));
        $record['instagram_slides'] = $this->instagramSlidesFromRecord($record);
        $record['instagram_slide_image_briefs'] = array_values(array_filter(array_map(
            fn(array $slide) => trim((string) ($slide['image_brief'] ?? '')),
            $record['instagram_slides']
        )));
        $slidePaths = $this->decodeList($record['slide_images'] ?? []);
        $record['slide_images'] = $slidePaths;
        $record['slide_image_urls'] = array_map(
            fn($path) => $path !== '' ? $this->imageUrlForPath((string) $path) : '',
            $slidePaths
        );
        return $record;
    }

    private function assertStoredImageExists(string $imagePath): void
    {
        $path = $this->imageStorage->publicPath(trim($imagePath));
        if ($imagePath === '' || ! is_file($path) || ! is_readable($path) || filesize($path) < 1) {
            throw new \RuntimeException('Generated image file was not saved correctly.');
        }
    }

    private function imageUrlForPath(string $imagePath): string
    {
        $imagePath = trim($imagePath);
        if ($imagePath === '') {
            return '';
        }

        $path = $this->imageStorage->publicPath($imagePath);
        return is_file($path) ? '/' . ltrim($imagePath, '/') : '';
    }

    private function usageFromRawResponse(string $rawResponse): array
    {
        $usage = [
            'input_tokens' => 0,
            'output_tokens' => 0,
            'total_tokens' => 0,
            'cached_tokens' => 0,
            'reasoning_tokens' => 0,
            'model' => '',
        ];

        $decoded = json_decode($rawResponse, true);
        if (! is_array($decoded)) {
            return $usage;
        }

        $usage['model'] = (string) ($decoded['model'] ?? '');

        $responseUsage = $decoded['usage'] ?? [];
        if (! is_array($responseUsage)) {
            return $usage;
        }

        $usage['input_tokens'] = (int) ($responseUsage['input_tokens'] ?? $responseUsage['prompt_tokens'] ?? 0);
        $usage['output_tokens'] = (int) ($responseUsage['output_tokens'] ?? $responseUsage['completion_tokens'] ?? 0);
        $usage['total_tokens'] = (int) ($responseUsage['total_tokens'] ?? ($usage['input_tokens'] + $usage['output_tokens']));

        $inputDetails = $responseUsage['input_tokens_details'] ?? $responseUsage['prompt_tokens_details'] ?? [];
        if (is_array($inputDetails)) {
            $usage['cached_tokens'] = (int) ($inputDetails['cached_tokens'] ?? 0);
        }

        $outputDetails = $responseUsage['output_tokens_details'] ?? $responseUsage['completion_tokens_details'] ?? [];
        if (is_array($outputDetails)) {
            $usage['reasoning_tokens'] = (int) ($outputDetails['reasoning_tokens'] ?? 0);
        }

        return $usage;
    }

    private function actualOpenAiCosts(): array
    {
        $settings = $this->getSettings();
        if (trim($settings['admin_api_key']) === '') {
            return [
                'available' => false,
                'error' => 'Add an OpenAI Admin API key in Settings to load actual billed costs.',
                'total' => 0.0,
                'currency' => 'usd',
                'line_items' => [],
                'buckets' => [],
            ];
        }

        $end = ee()->localize->now;
        $start = strtotime('today UTC', $end) - (30 * 86400);

        try {
            $response = $this->openai->organizationCosts(
                $settings['admin_api_key'],
                $start,
                $end,
                trim((string) $settings['openai_project_id'])
            );
        } catch (\Throwable $e) {
            return [
                'available' => false,
                'error' => $e->getMessage(),
                'total' => 0.0,
                'currency' => 'usd',
                'line_items' => [],
                'buckets' => [],
            ];
        }

        return $this->normalizeCostsResponse($response);
    }

    private function normalizeCostsResponse(array $response): array
    {
        $total = 0.0;
        $currency = 'usd';
        $lineItems = [];
        $buckets = [];

        foreach (($response['data'] ?? []) as $bucket) {
            $bucketTotal = 0.0;
            foreach (($bucket['results'] ?? []) as $result) {
                $amount = $result['amount'] ?? [];
                $value = (float) ($amount['value'] ?? 0);
                $currency = (string) ($amount['currency'] ?? $currency);
                $lineItem = (string) ($result['line_item'] ?? 'Other');

                $total += $value;
                $bucketTotal += $value;
                $lineItems[$lineItem] = ($lineItems[$lineItem] ?? 0.0) + $value;
            }

            $buckets[] = [
                'start_time' => (int) ($bucket['start_time'] ?? 0),
                'end_time' => (int) ($bucket['end_time'] ?? 0),
                'amount' => $bucketTotal,
            ];
        }

        arsort($lineItems);

        return [
            'available' => true,
            'error' => '',
            'total' => $total,
            'currency' => $currency,
            'line_items' => $lineItems,
            'buckets' => $buckets,
        ];
    }

    private function imageSettingsRecord(array $settings): array
    {
        $record = [];
        foreach (['image_model', 'image_size', 'image_quality'] as $field) {
            if (ee()->db->field_exists($field, $this->table)) {
                $record[$field] = (string) $settings[$field];
            }
        }

        return $record;
    }

    private function resolvedImageSettings(array $settings, ?array $template = null): array
    {
        if ($template && $this->isInstagramTemplate($template)) {
            $settings['image_size'] = '1024x1024';
            $settings['image_quality'] = 'high';
        }

        return $settings;
    }

    private function validateGeneratedContent(array $content, ?array $template = null, string $prompt = ''): void
    {
        $title = trim((string) ($content['title'] ?? ''));
        $postText = trim((string) ($content['post_text'] ?? ''));
        $tocItems = array_values(array_filter(array_map('trim', (array) ($content['table_of_contents'] ?? []))));

        if ($title === '' || $postText === '') {
            throw new \RuntimeException('OpenAI returned incomplete article content.');
        }

        if ($tocItems && $this->requiresArticleHeadings($template)) {
            $headings = $this->markdownHeadings($postText);
            if (count($headings) < count($tocItems)) {
                throw new \RuntimeException('OpenAI returned a table of contents without matching article sections. Try generating again.');
            }

            $missing = [];
            foreach ($tocItems as $item) {
                if (! in_array($this->headingKey($item), $headings, true)) {
                    $missing[] = $item;
                }
            }

            if ($missing) {
                throw new \RuntimeException('OpenAI returned article sections that do not match the table of contents: ' . implode(', ', $missing));
            }
        }

        $internalLink = trim((string) ($content['internal_link'] ?? ''));
        if ($internalLink !== '' && ! $this->isInstagramTemplate($template) && ! $this->validInternalLink($internalLink)) {
            throw new \RuntimeException('OpenAI returned an invalid internal link: ' . $internalLink);
        }

        $requestedSlideCount = $this->requestedInstagramSlideCount($prompt, $template);
        if ($requestedSlideCount !== null) {
            if (count($tocItems) !== $requestedSlideCount) {
                throw new \RuntimeException('OpenAI returned the wrong number of Instagram slides. Requested '
                    . $requestedSlideCount . ', received ' . count($tocItems) . '. Please generate again.');
            }

            $slideBlocks = $this->instagramSlideBlocks($postText);
            if (count($slideBlocks) !== $requestedSlideCount) {
                throw new \RuntimeException('OpenAI returned the wrong number of Instagram slide blocks. Requested '
                    . $requestedSlideCount . ', received ' . count($slideBlocks) . '. Please generate again.');
            }

            $slideImageBriefs = $this->instagramSlideImageBriefsFromContent($content);
            if (count($slideImageBriefs) !== $requestedSlideCount) {
                throw new \RuntimeException('OpenAI returned the wrong number of Instagram slide image briefs. Requested '
                    . $requestedSlideCount . ', received ' . count($slideImageBriefs) . '. Please generate again.');
            }
        }
    }

    private function requestedInstagramSlideCount(string $prompt, ?array $template = null): ?int
    {
        if (! $this->isInstagramTemplate($template)) {
            return null;
        }

        $patterns = [
            '/^\s*Slide count:\s*(\d+)\s*$/im',
            '/\bStructure it as\s*(\d+)\s*slides?\b/i',
            '/\bUse\s*(\d+)(?:\s*-\s*\d+)?\s*slides?\b/i',
            '/\bOutput\s*(\d+)(?:\s*-\s*\d+)?\s*concise slides?\b/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $prompt, $matches)) {
                $count = (int) ($matches[1] ?? 0);
                if ($count > 0) {
                    return $count;
                }
            }
        }

        return null;
    }

    private function generateStructuredContent(string $apiKey, string $model, string $generationPrompt, ?array $template = null, string $prompt = ''): array
    {
        $content = $this->openai->generateContent($apiKey, $model, $generationPrompt);

        if (! $this->isInstagramTemplate($template)) {
            return $content;
        }

        $minimumSlides = max(6, (int) ($this->requestedInstagramSlideCount($prompt, $template) ?? 0));
        if ($this->instagramOutputSlideCount($content) >= $minimumSlides) {
            return $content;
        }

        $retryPrompt = $generationPrompt . "\n\nYou did not generate enough slides. Generate ALL slides (6–8).";
        return $this->openai->generateContent($apiKey, $model, $retryPrompt);
    }

    private function normalizeRecentGenerationMemory(array $rows): array
    {
        return array_values(array_filter(array_map(function ($row) {
            if (! is_array($row)) {
                return null;
            }

            $title = trim((string) ($row['title'] ?? ''));
            $prompt = trim((string) ($row['prompt'] ?? ''));
            if ($title === '' && $prompt === '') {
                return null;
            }

            return [
                'id' => (int) ($row['id'] ?? 0),
                'title' => $title,
                'prompt' => $prompt,
                'created_at' => (int) ($row['created_at'] ?? 0),
            ];
        }, $rows)));
    }

    private function recentTopicGuardPrompt(array $recentGenerations, int $attempt): string
    {
        if (! $recentGenerations) {
            return '';
        }

        $lines = [];
        foreach (array_slice($recentGenerations, 0, 12) as $row) {
            $title = trim((string) ($row['title'] ?? ''));
            $prompt = trim((string) ($row['prompt'] ?? ''));
            $parts = [];
            if ($title !== '') {
                $parts[] = 'Title: ' . $title;
            }
            if ($prompt !== '') {
                $parts[] = 'Prompt/topic: ' . mb_strimwidth($prompt, 0, 180, '...');
            }
            if ($parts) {
                $lines[] = '- ' . implode(' | ', $parts);
            }
        }

        if (! $lines) {
            return '';
        }

        $instruction = $attempt > 1
            ? 'Your previous attempt was too similar to a recent topic. Choose a distinctly different announcement, angle, vendor, framework, or implementation problem.'
            : 'Avoid duplicating or lightly rewording recent topics. Do not pick the same announcement, the same product update, or the same core angle with cosmetic wording changes.';

        return "\n\nRecent topic guard:\n"
            . $instruction . "\n"
            . "Avoid these recent topics:\n"
            . implode("\n", $lines)
            . "\nReturn a meaningfully different topic with a fresh angle.";
    }

    private function duplicateTopicMatch(array $content, array $recentGenerations): ?array
    {
        $candidate = $this->topicComparisonText($content);
        return $this->duplicateTextMatch($candidate, $recentGenerations);
    }

    private function duplicateTextMatch(string $candidate, array $recentGenerations): ?array
    {
        if ($candidate === '') {
            return null;
        }

        $candidateNormalized = $this->normalizeTopicFingerprint($candidate);
        $candidateTokens = $this->topicTokens($candidateNormalized);

        foreach ($recentGenerations as $row) {
            $recentText = trim((string) ($row['title'] ?? ''));
            if ($recentText === '') {
                $recentText = trim((string) ($row['prompt'] ?? ''));
            }
            if ($recentText === '') {
                continue;
            }

            $recentNormalized = $this->normalizeTopicFingerprint($recentText);
            if ($recentNormalized === '') {
                continue;
            }

            if ($candidateNormalized === $recentNormalized) {
                return ['title' => $recentText, 'reason' => 'exact'];
            }

            if (strlen($candidateNormalized) > 18 && strlen($recentNormalized) > 18) {
                if (str_contains($candidateNormalized, $recentNormalized) || str_contains($recentNormalized, $candidateNormalized)) {
                    return ['title' => $recentText, 'reason' => 'contained'];
                }
            }

            $recentTokens = $this->topicTokens($recentNormalized);
            if ($this->topicTokenSimilarity($candidateTokens, $recentTokens) >= 0.74) {
                return ['title' => $recentText, 'reason' => 'token_overlap'];
            }

            similar_text($candidateNormalized, $recentNormalized, $percent);
            if ($percent >= 82.0) {
                return ['title' => $recentText, 'reason' => 'string_similarity'];
            }
        }

        return null;
    }

    private function topicComparisonText(array $content): string
    {
        $parts = [];
        $title = trim((string) ($content['title'] ?? ''));
        if ($title !== '') {
            $parts[] = $title;
        }

        $toc = array_values(array_filter(array_map('trim', (array) ($content['table_of_contents'] ?? []))));
        if ($toc) {
            $parts[] = implode(' ', array_slice($toc, 0, 3));
        }

        return trim(implode(' ', $parts));
    }

    private function normalizeTopicFingerprint(string $value): string
    {
        $value = strtolower(trim($value));
        $value = str_replace(['gpt-', 'gpt ', 'gpt'], ' gpt ', $value);
        $value = preg_replace('/[^a-z0-9\.]+/i', ' ', $value);
        $value = preg_replace('/\b(v|version)\s+(\d+(?:\.\d+)*)\b/i', ' $2 ', $value);
        $value = preg_replace('/\s+/', ' ', (string) $value);
        return trim((string) $value);
    }

    private function topicTokens(string $value): array
    {
        $stopWords = [
            'a', 'an', 'and', 'are', 'as', 'at', 'be', 'blog', 'for', 'from', 'guide', 'how',
            'in', 'into', 'is', 'it', 'of', 'on', 'or', 'post', 'the', 'this', 'to', 'what',
            'why', 'with', 'your'
        ];

        $tokens = array_values(array_filter(array_map('trim', explode(' ', $value)), function ($token) use ($stopWords) {
            return $token !== '' && ! in_array($token, $stopWords, true);
        }));

        return array_values(array_unique($tokens));
    }

    private function topicTokenSimilarity(array $left, array $right): float
    {
        if (! $left || ! $right) {
            return 0.0;
        }

        $intersection = array_intersect($left, $right);
        $union = array_unique(array_merge($left, $right));

        if (! $union) {
            return 0.0;
        }

        return count($intersection) / count($union);
    }

    private function instagramOutputSlideCount(array $content): int
    {
        $structuredSlides = $this->instagramSlideImageBriefsFromContent($content);
        $tocItems = array_values(array_filter(array_map('trim', (array) ($content['table_of_contents'] ?? []))));
        $slideBlocks = $this->instagramSlideBlocks((string) ($content['post_text'] ?? ''));

        return max(count($structuredSlides), count($tocItems), count($slideBlocks));
    }

    private function instagramSlideImageBriefsFromContent(array $content): array
    {
        $slides = $content['slides'] ?? [];
        if (! is_array($slides)) {
            return [];
        }

        $briefs = [];
        foreach ($slides as $slide) {
            if (! is_array($slide)) {
                continue;
            }

            $brief = trim((string) ($slide['image_brief'] ?? ''));
            if ($brief !== '') {
                $briefs[] = $brief;
            }
        }

        return $briefs;
    }

    private function instagramSlidesFromRecord(array $record): array
    {
        $decoded = json_decode((string) ($record['raw_response'] ?? ''), true);
        if (! is_array($decoded)) {
            return [];
        }

        $parsed = $decoded['parsed_content'] ?? [];
        $slides = is_array($parsed) ? ($parsed['slides'] ?? []) : [];
        if (! is_array($slides)) {
            return [];
        }

        return array_values(array_filter($slides, 'is_array'));
    }

    private function instagramSlideBlocks(string $postText): array
    {
        preg_match_all('/^\s*Slide\s+\d+\b.*$/im', $postText, $matches);
        return array_values(array_filter(array_map('trim', $matches[0] ?? [])));
    }

    private function requiresArticleHeadings(?array $template): bool
    {
        if (! $template) {
            return true;
        }

        $type = (string) ($template['content_type'] ?? '');
        $platform = strtolower((string) ($template['platform'] ?? ''));

        return in_array($type, ['seo_article', 'faq', 'technical_audit', 'comparison', 'case_study'], true)
            || $platform === 'website';
    }

    private function markdownHeadings(string $markdown): array
    {
        preg_match_all('/^#{2,4}\s+(.+)$/m', $markdown, $matches);

        return array_values(array_unique(array_map(
            fn($heading) => $this->headingKey((string) $heading),
            $matches[1] ?? []
        )));
    }

    private function headingKey(string $value): string
    {
        $value = strtolower(strip_tags($value));
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value);
        return trim((string) preg_replace('/\s+/', ' ', $value));
    }

    private function validInternalLink(string $url): bool
    {
        if (strpos($url, '/') === 0) {
            return true;
        }

        $host = parse_url($url, PHP_URL_HOST);
        if (! $host) {
            return false;
        }

        $siteHost = parse_url((string) ee()->config->item('base_url'), PHP_URL_HOST);
        return $siteHost !== null && strtolower($host) === strtolower($siteHost);
    }

    private function siteContextInstructions(?array $template = null): string
    {
        if ($this->isInstagramTemplate($template)) {
            return '';
        }

        $parts = [];
        $categories = $this->blogCategoryNames();
        $links = $this->recentBlogLinks();

        if ($categories) {
            $parts[] = "Available blog categories:\n" . implode("\n", array_map(fn($category) => '- ' . $category, $categories));
            $parts[] = 'Set category to the single closest matching available blog category name. Do not invent a new category when an available category fits.';
        }

        if ($links) {
            $parts[] = "Existing blog posts for possible internal links:\n" . implode("\n", array_map(
                fn($link) => '- ' . $link['title'] . ' - ' . $link['url'],
                $links
            ));
            $parts[] = 'Set internal_link to the URL of the most related existing blog post above when one is clearly relevant. If none are related, return an empty internal_link.';
        }

        return implode("\n\n", $parts);
    }

    private function blogCategoryNames(): array
    {
        $channel = ee('Model')->get('Channel')
            ->filter('channel_name', 'blog')
            ->with('CategoryGroups')
            ->first();

        if (! $channel) {
            return [];
        }

        $categories = [];
        foreach ($channel->getCategoryGroups() as $group) {
            foreach ($group->Categories as $category) {
                $name = trim((string) $category->cat_name);
                if ($name !== '') {
                    $categories[] = $name;
                }
            }
        }

        sort($categories);
        return array_values(array_unique($categories));
    }

    private function recentBlogLinks(int $limit = 15): array
    {
        $channel = ee('Model')->get('Channel')
            ->filter('channel_name', 'blog')
            ->first();

        if (! $channel) {
            return [];
        }

        $rows = ee()->db
            ->select('title, url_title')
            ->from('channel_titles')
            ->where('channel_id', (int) $channel->channel_id)
            ->where('status', 'open')
            ->order_by('entry_date', 'DESC')
            ->limit($limit)
            ->get()
            ->result_array();

        $links = [];
        foreach ($rows as $row) {
            $title = trim((string) ($row['title'] ?? ''));
            $urlTitle = trim((string) ($row['url_title'] ?? ''));
            if ($title !== '' && $urlTitle !== '') {
                $links[] = [
                    'title' => $title,
                    'url' => '/blog/article/' . $urlTitle,
                ];
            }
        }

        return $links;
    }

    private function internalLinkTitle(string $url): string
    {
        $urlTitle = $this->blogUrlTitleFromUrl($url);
        $channelId = $this->blogChannelId();
        if ($urlTitle === '' || $channelId < 1) {
            return '';
        }

        $row = ee()->db
            ->select('title')
            ->from('channel_titles')
            ->where('channel_id', $channelId)
            ->where('url_title', $urlTitle)
            ->limit(1)
            ->get()
            ->row_array();

        return trim((string) ($row['title'] ?? ''));
    }

    private function blogChannelId(): int
    {
        $channel = ee('Model')->get('Channel')
            ->filter('channel_name', 'blog')
            ->first();

        return $channel ? (int) $channel->channel_id : 0;
    }

    private function blogUrlTitleFromUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        $path = strpos($url, '/') === 0 ? $url : (string) parse_url($url, PHP_URL_PATH);
        if ($path === '') {
            return '';
        }

        if (! preg_match('~/blog/article/([^/?#]+)~', $path, $match)) {
            return '';
        }

        return trim((string) $match[1]);
    }

    private function decodeList($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        $decoded = json_decode((string) $value, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function linesToList(string $value): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/', $value) ?: [])));
    }

    private function csvToList(string $value): array
    {
        return array_values(array_filter(array_map('trim', explode(',', $value))));
    }

    private function normalizeHashtags(array $items): array
    {
        $hashtags = [];

        foreach ($items as $item) {
            $tag = trim((string) $item);
            $tag = ltrim($tag, '#');
            $tag = preg_replace('/[^A-Za-z0-9_]+/', '', $tag);
            if ($tag !== '') {
                $hashtags[] = '#' . $tag;
            }
        }

        return array_values(array_unique($hashtags));
    }

    private function composeImagePrompt(string $imagePrompt, string $imageBrief, ?array $template = null): string
    {
        if ($template && $this->isInstagramTemplate($template)) {
            $instagramDirection = implode("\n", [
                'Generate a polished Instagram carousel cover image suitable for slide 1 of a professional post.',
                'Keep the composition square 1:1, bold, clean, high-contrast, and visually strong on a mobile feed.',
                'Favor designed editorial cover aesthetics, abstract systems, premium layouts, or stylized productized visuals over blog hero photography.',
                'Do not include logos, watermarks, UI screenshots, or readable text unless the user explicitly requests visible text.',
                'Keep enough safe margin around the focal elements so the image still reads well inside Instagram cropping and UI overlays.',
            ]);
            $imagePrompt = trim($instagramDirection . "\n\nSubject and composition:\n" . $imagePrompt);
        } else {
            $realisticDirection = implode("\n", [
                'Generate a realistic, photorealistic image suitable for a professional social post or blog hero.',
                'Use natural lighting, believable materials, authentic environments, and real-world perspective.',
                'Avoid illustrations, vector art, infographics, diagrams, quote cards, UI mockups, rendered text, logos, watermarks, and decorative typography unless the user explicitly requests visible text.',
            ]);
            $imagePrompt = trim($realisticDirection . "\n\nSubject and composition:\n" . $imagePrompt);
        }

        if ($imageBrief === '') {
            return $imagePrompt;
        }

        return trim($imagePrompt . "\n\nImage brief:\n" . $imageBrief);
    }

    private function imageResizeConfig(?array $template = null): ?array
    {
        if (! $template || ! $this->isInstagramTemplate($template)) {
            return null;
        }

        return [
            'width' => 1080,
            'height' => 1080,
        ];
    }

    private function isInstagramTemplate(?array $template = null): bool
    {
        if (! $template) {
            return false;
        }

        $platform = strtolower(trim((string) ($template['platform'] ?? '')));
        $title = strtolower(trim((string) ($template['title'] ?? '')));

        return $platform === 'instagram' || str_contains($title, 'instagram carousel');
    }

    private function baseImagePrompt(string $imagePrompt): string
    {
        $parts = preg_split('/\R\RImage brief:\R/', $imagePrompt, 2);
        return trim((string) ($parts[0] ?? $imagePrompt));
    }

    private function memberId(): int
    {
        try {
            return (int) ee()->session->userdata('member_id');
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public function generateSingleSlideImage(int $generationId, int $slideIndex, array $slideData): array
    {
        $row = ee()->db->where('id', $generationId)->get($this->table)->row_array();
        if (! $row) {
            throw new \InvalidArgumentException('Generated post not found.');
        }

        $settings     = $this->getSettings();
        $slideNumber  = (int) ($slideData['slide_number'] ?? ($slideIndex + 1));
        $slideTitle   = trim((string) ($slideData['title'] ?? 'slide'));
        $isCover      = $slideIndex === 0 || strtolower((string) ($slideData['type'] ?? '')) === 'cover';
        $providerKey  = $isCover ? 'instagram_cover_provider' : 'instagram_slide_provider';
        $provider     = (string) ($settings[$providerKey] ?? 'openai');

        if ($provider === 'none') {
            return [
                'generation_id' => $generationId,
                'slide_index'   => $slideIndex,
                'slide_number'  => $slideNumber,
                'image_url'     => '',
            ];
        }

        $base64 = $this->generateSlideImageBase64($provider, $slideData, $settings);

        $resize = $provider === 'gd' ? null : ['width' => 1080, 'height' => 1080];
        $path   = $this->imageStorage->saveBase64Png(
            $base64,
            'slide-' . $slideNumber . '-' . $slideTitle,
            $resize
        );

        if (ee()->db->field_exists('slide_images', $this->table)) {
            $existing = $this->decodeList($row['slide_images'] ?? '');
            while (count($existing) <= $slideIndex) {
                $existing[] = '';
            }
            $existing[$slideIndex] = $path;
            ee()->db->where('id', $generationId)->update($this->table, [
                'slide_images' => json_encode(array_values($existing)),
            ]);
        }

        return [
            'generation_id' => $generationId,
            'slide_index'   => $slideIndex,
            'slide_number'  => $slideNumber,
            'image_url'     => $this->imageUrlForPath($path),
        ];
    }

    private function generateSlideImageBase64(string $provider, array $slideData, array $settings): string
    {
        if ($provider === 'gd') {
            return $this->gdRenderer->renderToBase64($slideData);
        }

        if ($provider === 'ideogram') {
            $ideogramKey = trim((string) ($settings['ideogram_api_key'] ?? ''));
            if ($ideogramKey === '') {
                throw new \RuntimeException('Ideogram API key is not configured. Add it in Instagram Settings.');
            }
            $prompt = $this->composeSlideImagePrompt($slideData);
            return $this->ideogramClient->generateImage($ideogramKey, $prompt);
        }

        // openai (default)
        $apiKey = trim((string) ($settings['api_key'] ?? ''));
        if ($apiKey === '') {
            throw new \RuntimeException('OpenAI API key is not configured.');
        }
        $model  = (string) ($settings['image_model'] ?? 'gpt-image-1.5');
        $prompt = $this->composeSlideImagePrompt($slideData);
        return $this->openai->generateImage($apiKey, $model, $prompt, '1024x1024', 'medium');
    }

    private function generateSlideImages(array $slides, string $apiKey, array $imageSettings): array
    {
        @set_time_limit(0);
        $paths = [];

        foreach ($slides as $slide) {
            if (! is_array($slide)) {
                $paths[] = '';
                continue;
            }

            try {
                $prompt = $this->composeSlideImagePrompt($slide);
                $base64 = $this->openai->generateImage(
                    $apiKey,
                    $imageSettings['image_model'],
                    $prompt,
                    '1024x1024',
                    'medium'
                );

                $slideNumber = (int) ($slide['slide_number'] ?? (count($paths) + 1));
                $slideTitle = trim((string) ($slide['title'] ?? 'slide'));
                $path = $this->imageStorage->saveBase64Png(
                    $base64,
                    'slide-' . $slideNumber . '-' . $slideTitle,
                    ['width' => 1080, 'height' => 1080]
                );
                $paths[] = $path;
            } catch (\Throwable $e) {
                $paths[] = '';
            }
        }

        return $paths;
    }

    private function composeSlideImagePrompt(array $slide): string
    {
        $slideNumber = (int) ($slide['slide_number'] ?? 0);
        $type = strtolower(trim((string) ($slide['type'] ?? '')));
        $title = trim((string) ($slide['title'] ?? ''));
        $subtitle = trim((string) ($slide['subtitle'] ?? ''));
        $heading = trim((string) ($slide['heading'] ?? ''));
        $bullets = array_values(array_filter(array_map('trim', (array) ($slide['bullets'] ?? []))));
        $imageBrief = trim((string) ($slide['image_brief'] ?? ''));
        $isCover = $slideNumber === 1 || $type === 'cover';

        $textLines = [];
        if ($title !== '') {
            $textLines[] = $title;
        }
        if ($isCover && $subtitle !== '') {
            $textLines[] = $subtitle;
        }
        if (! $isCover && $heading !== '') {
            $textLines[] = $heading;
        }
        foreach (array_slice($bullets, 0, 4) as $bullet) {
            $textLines[] = '• ' . $bullet;
        }
        if (! $textLines) {
            $textLines[] = 'Slide ' . $slideNumber;
        }

        $lines = [
            'Instagram carousel ' . ($isCover ? 'cover slide' : 'slide ' . $slideNumber) . ', square 1:1 format.',
            'Design as a premium typographic social media slide. Display this text prominently on the slide:',
            implode(' | ', $textLines),
            'Style: dark charcoal background, bold clean sans-serif typography in white or muted sage green, minimal structured layout, high contrast, premium editorial Instagram aesthetic.',
            'Text must be large, legible, centered or left-aligned, with safe margins from all edges so nothing is cropped by Instagram UI.',
            'No logos, no watermarks.',
        ];

        if ($imageBrief !== '') {
            $lines[] = 'Background visual accent: ' . $imageBrief;
        }

        return implode("\n", array_filter($lines));
    }

    private function encrypt(string $value): string
    {
        return $value === '' ? '' : ee('Encrypt')->encode($value, ee()->config->item('encryption_key'));
    }

    private function decrypt(string $value): string
    {
        if ($value === '') {
            return '';
        }

        $decoded = ee('Encrypt')->decode($value, ee()->config->item('encryption_key'));
        return is_string($decoded) ? $decoded : '';
    }
}
