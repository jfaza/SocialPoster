<?php

namespace JavidFazaeli\SocialPoster\Service;

class CarouselService
{
    private string $addonDir;
    private string $themesDir;

    public function __construct()
    {
        $this->addonDir  = __DIR__ . '/../carousel';
        $this->themesDir = $this->resolveThemesDir();
    }

    // -------------------------------------------------------------------------
    // Templates
    // -------------------------------------------------------------------------

    public function listTemplates(): array
    {
        $dir       = $this->addonDir . '/templates';
        $templates = [];

        if (! is_dir($dir)) {
            return $templates;
        }

        foreach (new \DirectoryIterator($dir) as $item) {
            if (! $item->isDir() || $item->isDot()) {
                continue;
            }

            $name     = $item->getFilename();
            $this->syncTemplateToTheme($name);

            $metaFile = $item->getPathname() . '/meta.json';
            $meta     = [];

            if (is_file($metaFile)) {
                $decoded = json_decode(file_get_contents($metaFile), true);
                $meta    = is_array($decoded) ? $decoded : [];
            }

            $meta['folder']      = $name;
            $meta['name']        = $meta['name'] ?? $name;
            $meta['description'] = $meta['description'] ?? '';
            $meta['style']       = $meta['style'] ?? [];
            $meta['best_for']    = $meta['best_for'] ?? [];
            $meta['density']     = $meta['density'] ?? '';
            $meta['tone']        = $meta['tone'] ?? '';
            $meta['tags']        = $meta['tags'] ?? [];

            $preview = [];
            $previewDirName = is_dir($item->getPathname() . '/preview') ? 'preview' : 'exports';
            $prevDir = $item->getPathname() . '/' . $previewDirName;
            if (is_dir($prevDir)) {
                for ($i = 1; $i <= 2; $i++) {
                    $f = sprintf('%s/slide_%02d.png', $prevDir, $i);
                    if (is_file($f)) {
                        $preview[] = '/system/user/addons/socialposter/carousel/templates/' . $name . '/' . $previewDirName . '/slide_0' . $i . '.png';
                    }
                }
            }
            $meta['previews'] = $preview;

            $templates[] = $meta;
        }

        usort($templates, fn($a, $b) => strcmp((string) $a['folder'], (string) $b['folder']));

        return $templates;
    }

    public function loadSchema(string $template): array
    {
        $path = $this->addonDir . '/templates/' . $template . '/schema.json';
        if (! is_file($path)) {
            throw new \RuntimeException("Carousel template schema not found: $template");
        }

        $schema = json_decode(file_get_contents($path), true);
        if (! is_array($schema)) {
            throw new \RuntimeException("Invalid schema.json for carousel template: $template");
        }

        return $schema;
    }

    public function templateExists(string $template): bool
    {
        $template = preg_replace('/[^a-zA-Z0-9\-]/', '', $template);
        return $template !== ''
            && is_dir($this->addonDir . '/templates/' . $template)
            && is_file($this->addonDir . '/templates/' . $template . '/schema.json');
    }

    public function generateVisualTemplate(string $name, string $instructions = ''): array
    {
        $name = $this->sanitizeTemplateName($name);
        if ($name === '') {
            throw new \InvalidArgumentException('Template name is required.');
        }

        $settings = ee('socialposter:generator')->getSettings();
        $apiKey = trim((string) ($settings['api_key'] ?? ''));
        if ($apiKey === '') {
            throw new \RuntimeException('OpenAI API key is not configured. Add it in SocialPoster Settings.');
        }

        $model = trim((string) ($settings['text_model'] ?? 'gpt-6.1-sol'));
        if ($model === '' || str_starts_with($model, 'claude-')) {
            $model = 'gpt-6.1-sol';
        }

        $prompt = $this->buildTemplatePrompt($name, $instructions);
        $package = ee('socialposter:openai')->generateCarouselTemplatePackage($apiKey, $model, $prompt);

        $html = (string) ($package['html'] ?? '');
        $example = $package['example'] ?? null;
        $meta = $package['meta'] ?? [];

        if (trim($html) === '') {
            throw new \RuntimeException('Template output was missing slides.html content.');
        }
        if (! is_array($example)) {
            throw new \RuntimeException('Template output was missing example data.');
        }
        if (! is_array($meta)) {
            $meta = [];
        }

        $meta = $this->normalizeTemplateMeta($meta, $name);
        $example = $this->normalizeTemplateExample($example, $name);
        $schema = $this->schemaFromValue($example);

        $templateDir = $this->addonDir . '/templates/' . $name;
        if (! is_dir($templateDir) && ! mkdir($templateDir, 0755, true) && ! is_dir($templateDir)) {
            throw new \RuntimeException('Could not create carousel template directory.');
        }

        file_put_contents($templateDir . '/slides.html', $html);
        file_put_contents($templateDir . '/example.json', json_encode($example, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        file_put_contents($templateDir . '/schema.json', json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        file_put_contents($templateDir . '/meta.json', json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        $this->syncTemplateToTheme($name);

        return [
            'template' => $name,
            'meta' => $meta,
            'preview_url' => $this->themeBaseUrl() . 'carousel/templates/' . $name . '/slides.html',
        ];
    }

    // -------------------------------------------------------------------------
    // Generation
    // -------------------------------------------------------------------------

    public function generate(string $topic, string $template = 'minimal-list', string $style = 'base', array $options = []): array
    {
        $topic = trim($topic);
        $template = preg_replace('/[^a-zA-Z0-9\-]/', '', $template);
        $style    = in_array($style, ['base', 'viral', 'technical'], true) ? $style : 'base';

        if (! $this->templateExists($template)) {
            throw new \RuntimeException("Unknown carousel template: $template");
        }
        if ($topic === '') {
            throw new \InvalidArgumentException('Please enter a topic.');
        }

        $audience      = (string) ($options['audience'] ?? '');
        $contentPrompt = (string) ($options['content_prompt'] ?? '');
        $designPrompt  = (string) ($options['design_prompt'] ?? '');
        $recentGenerations = $this->normalizeRecentGenerationMemory((array) ($options['recent_generations'] ?? []));
        if (! $recentGenerations) {
            $recentGenerations = $this->recentGenerationMemory(['limit' => 18]);
        }
        $duplicateTopic = ee('socialposter:generator')->duplicateTopicMatchText($topic, $recentGenerations);
        if ($duplicateTopic) {
            throw new \RuntimeException(
                'That carousel topic is too close to a recent carousel (' . $duplicateTopic['title'] . '). '
                . 'Please choose a more distinct angle or topic.'
            );
        }

        $settings    = ee('socialposter:generator')->getSettings();
        $model       = trim((string) ($settings['text_model'] ?? 'gpt-6.1-sol'));
        $isAnthropic = str_starts_with($model, 'claude-');

        // Per-generation slide count overrides the global setting
        $globalSlideCount = max(3, min(12, (int) ($settings['carousel_slides'] ?? 6)));
        $slideCount = isset($options['slides']) && $options['slides'] !== null
            ? max(3, min(12, (int) $options['slides']))
            : $globalSlideCount;

        if ($isAnthropic) {
            $apiKey = trim((string) ($settings['anthropic_api_key'] ?? ''));
            if ($apiKey === '') {
                throw new \RuntimeException('Anthropic API key is not configured. Add it in SocialPoster Settings.');
            }
        } else {
            $apiKey = trim((string) ($settings['api_key'] ?? ''));
            if ($apiKey === '') {
                throw new \RuntimeException('OpenAI API key is not configured. Add it in SocialPoster Settings.');
            }
        }

        $schema       = $this->adjustSlidesCount($this->loadSchema($template), $slideCount);
        $systemPrompt = $this->loadSystemPrompt($style);
        $userPrompt   = $this->buildUserPrompt($topic, $template, $style, $slideCount, $audience, $contentPrompt, $designPrompt);

        if ($isAnthropic) {
            $data = ee('socialposter:anthropic')->generateCarouselContent(
                $apiKey, $model, $systemPrompt, $userPrompt, $schema
            );
        } else {
            $data = ee('socialposter:openai')->generateCarouselContent(
                $apiKey, $model, $systemPrompt, $userPrompt, $schema
            );
        }

        $data['template'] = $template;
        $data['style']    = $style;
        $data             = $this->cleanText($data);

        if (empty($data['brand'])) {
            $data['brand'] = 'fazaeli.dev';
        }
        if (empty($data['accent'])) {
            $data['accent'] = '#C65A2E';
        }

        $record = $this->insertRecord([
            'topic'             => $topic,
            'style'             => $style,
            'carousel_template' => $template,
            'caption'           => (string) ($data['caption'] ?? ''),
            'hashtags'          => json_encode($data['hashtags'] ?? [], JSON_UNESCAPED_UNICODE),
            'data_json'         => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        ]);

        $this->writeDataFile((int) $record['id'], $data);

        return $this->normalizeRecord($record);
    }

    public function assistCarousel(array $articles, array $options = []): array
    {
        $settings = ee('socialposter:generator')->getSettings();
        $apiKey   = trim((string) ($settings['api_key'] ?? ''));
        if ($apiKey === '') {
            throw new \RuntimeException('OpenAI API key is not configured. Add it in SocialPoster Settings.');
        }

        $sourceName = trim((string) ($options['source_name'] ?? 'Selected news source'));
        $tone       = trim((string) ($options['tone'] ?? 'practical, polished, conversational'));
        $goal       = trim((string) ($options['goal'] ?? 'educate and invite engagement'));
        $audience   = trim((string) ($options['audience'] ?? 'developers, founders, and AI-curious business owners'));
        $style      = trim((string) ($options['style'] ?? 'base')) ?: 'base';
        $template   = trim((string) ($options['template'] ?? 'minimal-list')) ?: 'minimal-list';
        $slides     = max(3, min(12, (int) ($options['slides'] ?? 6)));
        $profile = trim((string) ($options['generation_profile'] ?? 'instagram_carousel'));
        $templates = ee('socialposter:templates');
        $recentGenerations = $this->normalizeRecentGenerationMemory((array) ($options['recent_generations'] ?? []));
        if (! $recentGenerations) {
            $recentGenerations = $this->recentGenerationMemory(['limit' => 18]);
        }

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
                $excerpt !== '' ? 'Excerpt: ' . mb_strimwidth($excerpt, 0, 280, '...') : '',
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
            $templates->profilePlanningContract($profile),
            $templates->profileFreshnessContract($profile),
            'Carousel style: ' . $style,
            'Visual template: ' . $template,
            'Slide count: ' . $slides,
            "Recent source context:\n" . implode("\n\n", $articleLines),
            'Create one Instagram carousel plan that connects the strongest recent article/theme to the audience.',
        ]);

        $assistModel = trim((string) ($settings['text_model'] ?? 'gpt-6.1-sol'));
        if ($assistModel === '' || str_starts_with($assistModel, 'claude-')) {
            $assistModel = 'gpt-6.1-sol';
        }

        $assist = [];
        $topic = '';
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $attemptPrompt = $prompt . ee('socialposter:generator')->recentTopicGuardText($recentGenerations, $attempt);
            $assist = ee('socialposter:openai')->generateCarouselAssist($apiKey, $assistModel, $attemptPrompt);
            $topic = trim((string) ($assist['topic'] ?? ''));

            if ($topic === '' || ! ee('socialposter:generator')->duplicateTopicMatchText($topic, $recentGenerations)) {
                break;
            }

            if ($attempt >= 3) {
                throw new \RuntimeException('AI kept suggesting a carousel topic too close to a recent carousel. Try a different source or refresh the feed.');
            }
        }

        return [
            'topic'          => $topic,
            'audience'       => trim((string) ($assist['audience'] ?? $audience)),
            'content_prompt' => trim((string) ($assist['content_prompt'] ?? '')),
            'design_prompt'  => trim((string) ($assist['design_prompt'] ?? '')),
            'source_context' => implode(' | ', array_slice(array_map(
                fn($article) => trim((string) ($article['title'] ?? '')),
                $articles
            ), 0, 3)),
        ];
    }

    public function rewrite(int $id, string $topic, string $template = 'minimal-list', string $style = 'base', array $options = []): array
    {
        $record = $this->find($id);
        if (! $record) {
            throw new \InvalidArgumentException('Carousel not found.');
        }

        $template = preg_replace('/[^a-zA-Z0-9\-]/', '', $template);
        $style    = in_array($style, ['base', 'viral', 'technical'], true) ? $style : 'base';

        if (! $this->templateExists($template)) {
            throw new \RuntimeException("Unknown carousel template: $template");
        }

        $audience      = (string) ($options['audience'] ?? '');
        $contentPrompt = (string) ($options['content_prompt'] ?? '');
        $designPrompt  = (string) ($options['design_prompt'] ?? '');

        $settings = ee('socialposter:generator')->getSettings();
        $model    = trim((string) ($settings['text_model'] ?? 'gpt-6.1-sol'));
        $isAnthropic = str_starts_with($model, 'claude-');

        $globalSlideCount = max(3, min(12, (int) ($settings['carousel_slides'] ?? 6)));
        $slideCount = isset($options['slides']) && $options['slides'] !== null
            ? max(3, min(12, (int) $options['slides']))
            : max(3, min(12, count($record['slides'] ?? []) ?: $globalSlideCount));

        if ($isAnthropic) {
            $apiKey = trim((string) ($settings['anthropic_api_key'] ?? ''));
            if ($apiKey === '') {
                throw new \RuntimeException('Anthropic API key is not configured. Add it in SocialPoster Settings.');
            }
        } else {
            $apiKey = trim((string) ($settings['api_key'] ?? ''));
            if ($apiKey === '') {
                throw new \RuntimeException('OpenAI API key is not configured. Add it in SocialPoster Settings.');
            }
        }

        $schema       = $this->adjustSlidesCount($this->loadSchema($template), $slideCount);
        $systemPrompt = $this->loadSystemPrompt($style);
        $userPrompt   = $this->buildUserPrompt($topic, $template, $style, $slideCount, $audience, $contentPrompt, $designPrompt);

        if ($isAnthropic) {
            $data = ee('socialposter:anthropic')->generateCarouselContent(
                $apiKey, $model, $systemPrompt, $userPrompt, $schema
            );
        } else {
            $data = ee('socialposter:openai')->generateCarouselContent(
                $apiKey, $model, $systemPrompt, $userPrompt, $schema
            );
        }

        $data['template'] = $template;
        $data['style']    = $style;
        $data             = $this->cleanText($data);

        if (empty($data['brand'])) {
            $data['brand'] = 'fazaeli.dev';
        }
        if (empty($data['accent'])) {
            $data['accent'] = '#C65A2E';
        }

        ee()->db->where('id', $id)->update('socialposter_carousel_generations', [
            'topic'             => $topic,
            'style'             => $style,
            'carousel_template' => $template,
            'caption'           => (string) ($data['caption'] ?? ''),
            'hashtags'          => json_encode($data['hashtags'] ?? [], JSON_UNESCAPED_UNICODE),
            'data_json'         => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        ]);

        $this->writeDataFile($id, $data);

        return $this->find($id) ?: [];
    }

    public function regenerateSlide(int $carouselId, int $slideIndex, string $slideType, string $topic, string $template, string $style): array
    {
        $record = $this->find($carouselId);
        if (! $record) {
            throw new \InvalidArgumentException('Carousel not found.');
        }

        $allSlides = $record['slides'] ?? [];
        if (! isset($allSlides[$slideIndex])) {
            throw new \InvalidArgumentException('Slide index out of range.');
        }

        $existingSlide = $allSlides[$slideIndex];
        $schema        = $this->buildSingleSlideSchema($existingSlide);
        $settings      = ee('socialposter:generator')->getSettings();
        $model         = trim((string) ($settings['text_model'] ?? 'gpt-6.1-sol'));
        $isAnthropic   = str_starts_with($model, 'claude-');

        if ($isAnthropic) {
            $apiKey = trim((string) ($settings['anthropic_api_key'] ?? ''));
            if ($apiKey === '') {
                throw new \RuntimeException('Anthropic API key is not configured. Add it in SocialPoster Settings.');
            }
        } else {
            $apiKey = trim((string) ($settings['api_key'] ?? ''));
            if ($apiKey === '') {
                throw new \RuntimeException('OpenAI API key is not configured. Add it in SocialPoster Settings.');
            }
        }

        $systemPrompt = $this->loadSystemPrompt($style);
        $userPrompt   = implode("\n", [
            'Regenerate slide ' . ($slideIndex + 1) . ' of a carousel.',
            'Slide type: ' . $slideType,
            'Topic: ' . $topic,
            'Template: ' . $template,
            'Style: ' . $style,
            'Return improved content for this single slide only. Keep the same structure as the existing slide.',
            'Existing slide for reference: ' . json_encode($existingSlide),
        ]);

        if ($isAnthropic) {
            $data = ee('socialposter:anthropic')->generateCarouselContent(
                $apiKey, $model, $systemPrompt, $userPrompt, $schema
            );
        } else {
            $data = ee('socialposter:openai')->generateCarouselContent(
                $apiKey, $model, $systemPrompt, $userPrompt, $schema
            );
        }

        $data = $this->cleanText($data);

        // Preserve structural keys from the original
        foreach (['type', 'slide_num', 'number'] as $key) {
            if (isset($existingSlide[$key])) {
                $data[$key] = $existingSlide[$key];
            }
        }

        // Patch the carousel data
        $fullData = $record['data'] ?? [];
        if (! isset($fullData['slides'])) {
            $fullData['slides'] = $allSlides;
        }
        $fullData['slides'][$slideIndex] = $data;

        $this->update($carouselId, ['data_json' => json_encode($fullData, JSON_UNESCAPED_UNICODE)]);

        return $data;
    }

    private function buildSingleSlideSchema(array $existingSlide): array
    {
        $properties = [];
        $required   = [];

        foreach ($existingSlide as $key => $value) {
            if (in_array($key, ['slide_num', 'number'], true)) {
                continue;
            }

            $required[] = $key;

            if (is_array($value)) {
                if (empty($value)) {
                    $properties[$key] = ['type' => 'array', 'items' => ['type' => 'string']];
                } else {
                    $first = reset($value);
                    if (is_array($first)) {
                        $itemProps     = [];
                        $itemRequired  = [];
                        foreach ($first as $ik => $iv) {
                            $itemProps[$ik] = ['type' => 'string'];
                            $itemRequired[] = $ik;
                        }
                        $properties[$key] = [
                            'type'  => 'array',
                            'items' => [
                                'type'                 => 'object',
                                'additionalProperties' => false,
                                'required'             => $itemRequired,
                                'properties'           => $itemProps,
                            ],
                        ];
                    } else {
                        $properties[$key] = ['type' => 'array', 'items' => ['type' => 'string']];
                    }
                }
            } else {
                $properties[$key] = ['type' => 'string'];
            }
        }

        return [
            'type'                 => 'object',
            'additionalProperties' => false,
            'required'             => $required,
            'properties'           => $properties,
        ];
    }

    // -------------------------------------------------------------------------
    // CRUD
    // -------------------------------------------------------------------------

    public function find(int $id): ?array
    {
        if (! ee()->db->table_exists('socialposter_carousel_generations')) {
            return null;
        }

        $row = ee()->db->where('id', $id)->get('socialposter_carousel_generations')->row_array();
        return $row ? $this->normalizeRecord($row) : null;
    }

    public function latest(int $limit = 20): array
    {
        if (! ee()->db->table_exists('socialposter_carousel_generations')) {
            return [];
        }

        $rows = ee()->db
            ->order_by('created_at', 'DESC')
            ->limit($limit)
            ->get('socialposter_carousel_generations')
            ->result_array();

        return array_map([$this, 'normalizeRecord'], $rows);
    }

    public function recentGenerationMemory(array $options = []): array
    {
        if (! ee()->db->table_exists('socialposter_carousel_generations')) {
            return [];
        }

        $limit = max(1, min(50, (int) ($options['limit'] ?? 18)));
        $rows = ee()->db
            ->select('id, topic, created_at')
            ->order_by('created_at', 'DESC')
            ->limit($limit)
            ->get('socialposter_carousel_generations')
            ->result_array();

        return $this->normalizeRecentGenerationMemory($rows);
    }

    public function update(int $id, array $input): bool
    {
        $row = ee()->db->where('id', $id)->get('socialposter_carousel_generations')->row_array();
        if (! $row) {
            return false;
        }

        $current = json_decode((string) ($row['data_json'] ?? '{}'), true);
        if (! is_array($current)) {
            $current = [];
        }

        if (isset($input['data_json'])) {
            $incoming = json_decode((string) $input['data_json'], true);
            if (is_array($incoming)) {
                $current = $incoming;
            }
        } else {
            if (array_key_exists('caption', $input)) {
                $current['caption'] = trim((string) $input['caption']);
            }
            if (array_key_exists('hashtags', $input)) {
                $current['hashtags'] = $this->normalizeHashtags((string) $input['hashtags']);
            }
            if (array_key_exists('slides', $input) && is_array($input['slides'])) {
                $current['slides'] = $input['slides'];
            }
        }

        $current = $this->cleanText($current);
        $dataJson = json_encode($current, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        ee()->db->where('id', $id)->update('socialposter_carousel_generations', [
            'topic'     => array_key_exists('topic', $input) ? trim((string) $input['topic']) : (string) ($row['topic'] ?? ''),
            'caption'   => (string) ($current['caption'] ?? $row['caption']),
            'hashtags'  => json_encode($current['hashtags'] ?? [], JSON_UNESCAPED_UNICODE),
            'data_json' => $dataJson,
        ]);

        $this->writeDataFile($id, $current);

        return true;
    }

    public function delete(int $id): bool
    {
        $row = ee()->db->where('id', $id)->get('socialposter_carousel_generations')->row_array();
        if (! $row) {
            return false;
        }

        $dataFile = $this->dataFilePath($id);
        if (is_file($dataFile)) {
            @unlink($dataFile);
        }

        ee()->db->where('id', $id)->delete('socialposter_carousel_generations');

        return true;
    }

    // -------------------------------------------------------------------------
    // URLs / paths for iframe preview
    // -------------------------------------------------------------------------

    public function previewUrl(int $id, string $template): string
    {
        $template = preg_replace('/[^a-zA-Z0-9\-]/', '', $template);
        $this->syncTemplateToTheme($template);

        $tplUrl   = $this->themeBaseUrl() . 'carousel/templates/' . $template . '/slides.html';
        $dataUrl  = $this->dataFileUrl($id);

        return $tplUrl . '?data=' . rawurlencode($dataUrl);
    }

    public function dataFileUrl(int $id): string
    {
        return $this->themeBaseUrl() . 'carousel/data/' . $id . '.json';
    }

    public function dataFilePath(int $id): string
    {
        return $this->themesDir . '/carousel/data/' . $id . '.json';
    }

    // -------------------------------------------------------------------------
    // Internal: prompt loading (resolves @include directives)
    // -------------------------------------------------------------------------

    private function loadSystemPrompt(string $style): string
    {
        $path = $this->addonDir . '/prompts/content/' . $style . '.txt';
        if (! is_file($path)) {
            $path = $this->addonDir . '/prompts/content/base.txt';
        }

        return $this->resolveIncludes($path);
    }

    private function resolveIncludes(string $filePath, array &$seen = []): string
    {
        $filePath = realpath($filePath);
        if ($filePath === false || in_array($filePath, $seen, true)) {
            return '';
        }

        $seen[] = $filePath;
        $lines  = [];

        foreach (explode("\n", file_get_contents($filePath)) as $line) {
            $stripped = ltrim($line);
            if (str_starts_with($stripped, '@include ')) {
                $target      = trim(substr($stripped, 9));
                $includePath = realpath(dirname($filePath) . '/' . $target);
                if ($includePath !== false && is_file($includePath)) {
                    $lines[] = $this->resolveIncludes($includePath, $seen);
                }
            } else {
                $lines[] = $line;
            }
        }

        return trim(implode("\n", $lines));
    }

    private function buildUserPrompt(string $topic, string $template, string $style, int $slideCount = 6, string $audience = '', string $contentPrompt = '', string $designPrompt = ''): string
    {
        $meta     = [];
        $metaFile = $this->addonDir . '/templates/' . $template . '/meta.json';
        if (is_file($metaFile)) {
            $decoded = json_decode(file_get_contents($metaFile), true);
            $meta    = is_array($decoded) ? $decoded : [];
        }

        $resolvedAudience = $audience !== '' ? $audience : 'developers, builders, and AI-curious professionals';

        $parts = [
            'Topic: ' . $topic,
            'Audience: ' . $resolvedAudience,
            'Template name: ' . $template,
            'Style: ' . $style,
            'Brand: fazaeli.dev',
            'Accent color: #C65A2E',
        ];

        if (! empty($meta['description'])) {
            $parts[] = 'Template description: ' . $meta['description'];
        }
        if (! empty($meta['tone'])) {
            $parts[] = 'Tone: ' . $meta['tone'];
        }

        $exampleFile = $this->addonDir . '/templates/' . $template . '/example.json';
        if (is_file($exampleFile)) {
            $example = json_decode(file_get_contents($exampleFile), true);
            if (is_array($example)) {
                $exampleSlides = array_slice($example['slides'] ?? [], 0, 2);
                $parts[] = "\nTemplate data contract:\nUse the template-specific schema exactly. Preserve fields shown in this example structure, even when a field is not useful for one slide; use empty strings or empty arrays rather than omitting required keys.";
                $parts[] = 'Example structure excerpt: ' . json_encode([
                    'top_level_keys' => array_keys($example),
                    'slides' => $exampleSlides,
                ], JSON_UNESCAPED_UNICODE);
            }
        }

        $parts[] = 'Create a ' . $style . ' Instagram carousel with exactly ' . $slideCount . ' slides.';

        if ($contentPrompt !== '') {
            $parts[] = "\nContent instructions:\n" . $contentPrompt;
        }

        if ($designPrompt !== '') {
            $parts[] = "\nDesign instructions:\n" . $designPrompt;
        }

        return implode("\n", $parts);
    }

    private function normalizeRecentGenerationMemory(array $rows): array
    {
        return array_values(array_filter(array_map(function ($row) {
            if (! is_array($row)) {
                return null;
            }

            $topic = trim((string) ($row['topic'] ?? $row['title'] ?? ''));
            if ($topic === '') {
                return null;
            }

            return [
                'id' => (int) ($row['id'] ?? 0),
                'title' => $topic,
                'prompt' => $topic,
                'created_at' => (int) ($row['created_at'] ?? 0),
            ];
        }, $rows)));
    }

    private function adjustSlidesCount(array $schema, int $count): array
    {
        if (isset($schema['properties']['slides'])) {
            $schema['properties']['slides']['minItems'] = $count;
            $schema['properties']['slides']['maxItems'] = $count;
        }
        return $schema;
    }

    private function buildTemplatePrompt(string $name, string $instructions): string
    {
        $promptFile = $this->addonDir . '/prompts/template/template_generator.txt';
        $basePrompt = is_file($promptFile)
            ? file_get_contents($promptFile)
            : 'Generate a reusable Instagram carousel template package.';

        return trim(implode("\n\n", array_filter([
            $basePrompt,
            'Template folder/name: ' . $name,
            $instructions !== '' ? "USER DESIGN INSTRUCTIONS:\n" . $instructions : '',
            'Important: use only the standard data contract fields: type, title, subtitle, category, slide_num, items, cta, item.title, item.text, and top-level brand/accent/series/template/style/caption/hashtags. Do not require custom fields in the HTML renderer.',
        ])));
    }

    private function normalizeTemplateMeta(array $meta, string $name): array
    {
        return [
            'name' => trim((string) ($meta['name'] ?? $name)) ?: $name,
            'description' => trim((string) ($meta['description'] ?? ($name . ' carousel template'))),
            'style' => $this->stringList($meta['style'] ?? ['base']),
            'best_for' => $this->stringList($meta['best_for'] ?? ['tools', 'breakdowns', 'developer content']),
            'density' => trim((string) ($meta['density'] ?? 'medium')) ?: 'medium',
            'tone' => trim((string) ($meta['tone'] ?? 'practical')) ?: 'practical',
            'tags' => $this->stringList($meta['tags'] ?? [$name]),
        ];
    }

    private function normalizeTemplateExample(array $example, string $name): array
    {
        $example['brand'] = trim((string) ($example['brand'] ?? 'ai.tools.daily.lab')) ?: 'ai.tools.daily.lab';
        $example['accent'] = trim((string) ($example['accent'] ?? '#C65A2E')) ?: '#C65A2E';
        $example['series'] = trim((string) ($example['series'] ?? 'Developer Edition')) ?: 'Developer Edition';
        $example['template'] = $name;
        $example['style'] = trim((string) ($example['style'] ?? 'base')) ?: 'base';
        $example['caption'] = trim((string) ($example['caption'] ?? ('A carousel generated with the ' . $name . ' template.')));
        $example['hashtags'] = $this->stringList($example['hashtags'] ?? ['#aitools', '#developers', '#tech']);

        $slides = $example['slides'] ?? [];
        if (! is_array($slides) || ! $slides) {
            throw new \RuntimeException('Template example must include slides.');
        }

        $slides = array_slice(array_values($slides), 0, 6);
        while (count($slides) < 6) {
            $idx = count($slides) + 1;
            $slides[] = [
                'type' => $idx === 1 ? 'cover' : ($idx === 6 ? 'outro' : 'content'),
                'title' => $idx === 1 ? 'AI Tools for Developers' : ($idx === 6 ? 'Build Better Workflows' : 'Practical AI Workflow'),
                'subtitle' => $idx === 1 ? 'A practical guide for modern builders' : '',
                'category' => 'AI Tools',
                'slide_num' => (string) $idx,
                'items' => [],
                'cta' => $idx === 6 ? 'Save this carousel for your next build.' : '',
            ];
        }

        foreach ($slides as $idx => &$slide) {
            if (! is_array($slide)) {
                $slide = [];
            }
            $slide['type'] = in_array(($slide['type'] ?? ''), ['cover', 'content', 'outro'], true)
                ? (string) $slide['type']
                : ($idx === 0 ? 'cover' : ($idx === 5 ? 'outro' : 'content'));
            $slide['title'] = (string) ($slide['title'] ?? '');
            $slide['subtitle'] = (string) ($slide['subtitle'] ?? '');
            $slide['category'] = (string) ($slide['category'] ?? '');
            $slide['slide_num'] = (string) ($slide['slide_num'] ?? ($idx + 1));
            $slide['cta'] = (string) ($slide['cta'] ?? '');

            $items = $slide['items'] ?? [];
            $normalizedItems = [];
            if (is_array($items)) {
                foreach ($items as $item) {
                    if (! is_array($item)) {
                        continue;
                    }
                    $normalizedItems[] = [
                        'title' => (string) ($item['title'] ?? ''),
                        'text' => (string) ($item['text'] ?? ''),
                    ];
                }
            }
            $slide['items'] = $normalizedItems;
        }
        unset($slide);

        $slides[0]['type'] = 'cover';
        $slides[5]['type'] = 'outro';
        $example['slides'] = $slides;

        return $example;
    }

    private function schemaFromValue(mixed $value): array
    {
        if (is_array($value) && $this->isList($value)) {
            $items = [];
            foreach ($value as $entry) {
                if ($entry !== null) {
                    $items[] = $entry;
                }
            }
            if (! $items) {
                return ['type' => 'array', 'items' => ['type' => 'string']];
            }
            if (array_reduce($items, fn($carry, $entry) => $carry && is_array($entry) && $this->isList($entry), true)) {
                $flattened = [];
                foreach ($items as $entry) {
                    foreach ($entry as $child) {
                        if ($child !== null) {
                            $flattened[] = $child;
                        }
                    }
                }

                return [
                    'type' => 'array',
                    'items' => $flattened ? $this->schemaFromValue($flattened) : ['type' => 'string'],
                    'minItems' => 0,
                    'maxItems' => max(3, ...array_map('count', $items)),
                ];
            }
            if (array_reduce($items, fn($carry, $entry) => $carry && is_array($entry) && ! $this->isList($entry), true)) {
                $isSlides = isset($items[0]['type'], $items[0]['slide_num']);
                return [
                    'type' => 'array',
                    'items' => $this->schemaFromObjects($items),
                    'minItems' => $isSlides ? count($items) : 0,
                    'maxItems' => $isSlides ? count($items) : max(count($items), 3),
                ];
            }
            return ['type' => 'array', 'items' => $this->schemaFromValue($items[0])];
        }

        if (is_array($value)) {
            return $this->schemaFromObjects([$value]);
        }

        if (is_bool($value)) {
            return ['type' => 'boolean'];
        }
        if (is_int($value)) {
            return ['type' => 'integer'];
        }
        if (is_float($value)) {
            return ['type' => 'number'];
        }
        return ['type' => 'string'];
    }

    private function schemaFromObjects(array $objects): array
    {
        $keys = [];
        foreach ($objects as $object) {
            foreach (array_keys($object) as $key) {
                if (! in_array($key, $keys, true)) {
                    $keys[] = $key;
                }
            }
        }

        $properties = [];
        foreach ($keys as $key) {
            $values = [];
            foreach ($objects as $object) {
                if (array_key_exists($key, $object)) {
                    $values[] = $object[$key];
                }
            }
            $properties[$key] = $this->schemaFromValue($values && count($values) > 1 ? $values : ($values[0] ?? ''));
        }

        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => $keys,
            'properties' => $properties,
        ];
    }

    private function stringList(mixed $value): array
    {
        $items = is_array($value) ? $value : [$value];
        $strings = [];
        foreach ($items as $item) {
            $text = trim((string) $item);
            if ($text !== '') {
                $strings[] = $text;
            }
        }
        return array_values(array_unique($strings));
    }

    private function sanitizeTemplateName(string $name): string
    {
        $name = strtolower(trim($name));
        $name = preg_replace('/[^a-z0-9]+/', '-', $name);
        return trim((string) $name, '-');
    }

    private function isList(array $array): bool
    {
        return array_keys($array) === range(0, count($array) - 1);
    }

    // -------------------------------------------------------------------------
    // Internal: data file
    // -------------------------------------------------------------------------

    private function writeDataFile(int $id, array $data): void
    {
        $dataDir = $this->themesDir . '/carousel/data';
        if (! is_dir($dataDir) && ! mkdir($dataDir, 0755, true) && ! is_dir($dataDir)) {
            throw new \RuntimeException('Could not create carousel data directory.');
        }

        file_put_contents(
            $dataDir . '/' . $id . '.json',
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );
    }

    private function syncTemplateToTheme(string $template): void
    {
        $template = preg_replace('/[^a-zA-Z0-9\-]/', '', $template);
        if ($template === '') {
            return;
        }

        $source = $this->addonDir . '/templates/' . $template;
        if (! is_dir($source)) {
            return;
        }

        $target = $this->themesDir . '/carousel/templates/' . $template;
        if (! is_dir($target) && ! mkdir($target, 0755, true) && ! is_dir($target)) {
            return;
        }

        $this->copyDirectory($source, $target);
    }

    private function copyDirectory(string $source, string $target): void
    {
        foreach (new \DirectoryIterator($source) as $item) {
            if ($item->isDot()) {
                continue;
            }

            $sourcePath = $item->getPathname();
            $targetPath = $target . '/' . $item->getFilename();

            if ($item->isDir()) {
                if (! is_dir($targetPath) && ! mkdir($targetPath, 0755, true) && ! is_dir($targetPath)) {
                    continue;
                }
                $this->copyDirectory($sourcePath, $targetPath);
                continue;
            }

            if (! $item->isFile()) {
                continue;
            }

            if (is_file($targetPath) && filemtime($targetPath) >= filemtime($sourcePath)) {
                continue;
            }

            @copy($sourcePath, $targetPath);
        }
    }

    // -------------------------------------------------------------------------
    // Internal: DB
    // -------------------------------------------------------------------------

    private function insertRecord(array $fields): array
    {
        $record = array_merge([
            'site_id'           => (int) ee()->config->item('site_id'),
            'member_id'         => $this->memberId(),
            'topic'             => '',
            'style'             => 'base',
            'carousel_template' => '',
            'caption'           => '',
            'hashtags'          => '[]',
            'data_json'         => '{}',
            'created_at'        => ee()->localize->now,
        ], $fields);

        ee()->db->insert('socialposter_carousel_generations', $record);
        $record['id'] = (int) ee()->db->insert_id();

        return $record;
    }

    private function normalizeRecord(array $record): array
    {
        $id   = (int) ($record['id'] ?? 0);
        $tpl  = (string) ($record['carousel_template'] ?? '');
        $data = json_decode((string) ($record['data_json'] ?? '{}'), true);
        $data = is_array($data) ? $data : [];

        $record['hashtags_list'] = json_decode((string) ($record['hashtags'] ?? '[]'), true) ?: [];
        $record['data']          = $data;
        $record['slides']        = $data['slides'] ?? [];
        $record['preview_url']   = $id > 0 && $tpl !== '' ? $this->previewUrl($id, $tpl) : '';
        $record['data_url']      = $id > 0 ? $this->dataFileUrl($id) : '';

        return $record;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function normalizeHashtags(string $raw): array
    {
        $items = preg_split('/[\s,]+/', $raw) ?: [];
        $tags  = [];
        foreach ($items as $item) {
            $tag = ltrim(trim((string) $item), '#');
            $tag = preg_replace('/[^A-Za-z0-9_]+/', '', $tag);
            if ($tag !== '') {
                $tags[] = '#' . $tag;
            }
        }
        return array_values(array_unique($tags));
    }

    private function cleanText(mixed $value): mixed
    {
        if (is_string($value)) {
            return str_replace('\\n', "\n", $value);
        }
        if (is_array($value)) {
            return array_map([$this, 'cleanText'], $value);
        }
        return $value;
    }

    private function memberId(): int
    {
        try {
            return (int) ee()->session->userdata('member_id');
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function resolveThemesDir(): string
    {
        $candidates = [];

        if (defined('SYSPATH')) {
            $candidates[] = dirname(rtrim(str_replace('\\', '/', SYSPATH), '/')) . '/themes/user/addons/socialposter';
        }

        if (defined('FCPATH')) {
            $candidates[] = rtrim(str_replace('\\', '/', FCPATH), '/') . '/themes/user/addons/socialposter';
        }

        foreach ($candidates as $path) {
            $path = rtrim($path, '/');
            if (is_dir(dirname($path))) {
                return $path;
            }
        }

        return '/var/www/html/themes/user/addons/socialposter';
    }

    private function themeBaseUrl(): string
    {
        $base = rtrim((string) ee()->config->item('base_url'), '/');
        return $base . '/themes/user/addons/socialposter/';
    }
}
