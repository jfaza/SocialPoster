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
            $prevDir = $item->getPathname() . '/preview';
            if (is_dir($prevDir)) {
                for ($i = 1; $i <= 2; $i++) {
                    $f = sprintf('%s/slide_%02d.png', $prevDir, $i);
                    if (is_file($f)) {
                        $preview[] = '/system/user/addons/socialposter/carousel/templates/' . $name . '/preview/slide_0' . $i . '.png';
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

    // -------------------------------------------------------------------------
    // Generation
    // -------------------------------------------------------------------------

    public function generate(string $topic, string $template = 'minimal-list', string $style = 'base', array $options = []): array
    {
        $template = preg_replace('/[^a-zA-Z0-9\-]/', '', $template);
        $style    = in_array($style, ['base', 'viral', 'technical'], true) ? $style : 'base';

        if (! $this->templateExists($template)) {
            throw new \RuntimeException("Unknown carousel template: $template");
        }

        $audience      = (string) ($options['audience'] ?? '');
        $contentPrompt = (string) ($options['content_prompt'] ?? '');
        $designPrompt  = (string) ($options['design_prompt'] ?? '');

        $settings    = ee('socialposter:generator')->getSettings();
        $model       = trim((string) ($settings['text_model'] ?? 'gpt-5.4'));
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
        $model         = trim((string) ($settings['text_model'] ?? 'gpt-5.4'));
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

        $parts[] = 'Create a ' . $style . ' Instagram carousel with exactly ' . $slideCount . ' slides.';

        if ($contentPrompt !== '') {
            $parts[] = "\nContent instructions:\n" . $contentPrompt;
        }

        if ($designPrompt !== '') {
            $parts[] = "\nDesign instructions:\n" . $designPrompt;
        }

        return implode("\n", $parts);
    }

    private function adjustSlidesCount(array $schema, int $count): array
    {
        if (isset($schema['properties']['slides'])) {
            $schema['properties']['slides']['minItems'] = $count;
            $schema['properties']['slides']['maxItems'] = $count;
        }
        return $schema;
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
