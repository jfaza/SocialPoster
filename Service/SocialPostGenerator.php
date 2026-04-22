<?php

namespace JavidFazaeli\SocialPoster\Service;

class SocialPostGenerator
{
    private const DEFAULT_TEXT_MODEL = 'gpt-5.4';

    private OpenAiClient $openai;
    private ImageStorage $imageStorage;

    public function __construct(?OpenAiClient $openai = null, ?ImageStorage $imageStorage = null)
    {
        $this->openai = $openai ?: new OpenAiClient();
        $this->imageStorage = $imageStorage ?: new ImageStorage();
    }

    public function defaultSettings(): array
    {
        return [
            'api_key' => '',
            'admin_api_key' => '',
            'openai_project_id' => '',
            'text_model' => self::DEFAULT_TEXT_MODEL,
            'image_model' => 'gpt-image-1.5',
            'image_size' => '1024x1024',
            'image_quality' => 'medium',
        ];
    }

    public function textModels(): array
    {
        return [
            'gpt-5.4' => 'GPT-5.4',
            'gpt-5.4-mini' => 'GPT-5.4 Mini',
        ];
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
            if (in_array($key, ['api_key', 'admin_api_key'], true)) {
                $settings[$key] = $this->decrypt((string) $row['setting_value']);
            } elseif (array_key_exists($key, $settings)) {
                $settings[$key] = (string) $row['setting_value'];
            }
        }

        foreach (['api_key', 'admin_api_key'] as $key) {
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
        $this->validateSecretInput($apiKey, 'OpenAI API Key');
        $this->validateSecretInput($adminApiKey, 'OpenAI Admin API Key');

        $settings = [
            'api_key' => $apiKey !== ''
                ? $apiKey
                : $current['api_key'],
            'admin_api_key' => $adminApiKey !== ''
                ? $adminApiKey
                : $current['admin_api_key'],
            'openai_project_id' => trim((string) ($input['openai_project_id'] ?? $current['openai_project_id'])),
            'text_model' => trim((string) ($input['text_model'] ?? self::DEFAULT_TEXT_MODEL)) ?: self::DEFAULT_TEXT_MODEL,
            'image_model' => array_key_exists(($input['image_model'] ?? ''), $this->imageModels())
                ? (string) $input['image_model']
                : 'gpt-image-1.5',
            'image_size' => in_array(($input['image_size'] ?? ''), ['1024x1024', '1024x1536', '1536x1024'], true)
                ? (string) $input['image_size']
                : '1024x1024',
            'image_quality' => in_array(($input['image_quality'] ?? ''), ['low', 'medium', 'high'], true)
                ? (string) $input['image_quality']
                : 'medium',
        ];

        if ($settings['admin_api_key'] !== '' && str_starts_with($settings['admin_api_key'], 'proj_')) {
            throw new \InvalidArgumentException('OpenAI Admin API Key contains a project ID. Paste the admin key secret into that field and put the proj_ value in OpenAI Project ID.');
        }

        if ($settings['admin_api_key'] !== '' && $settings['openai_project_id'] !== '' && hash_equals($settings['admin_api_key'], $settings['openai_project_id'])) {
            throw new \InvalidArgumentException('OpenAI Admin API Key and OpenAI Project ID cannot be the same value.');
        }

        foreach ($settings as $key => $value) {
            $stored = in_array($key, ['api_key', 'admin_api_key'], true) ? $this->encrypt($value) : $value;
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
        $templateInstructions = $templateId > 0 ? ee('socialposter:templates')->instructionsFor($templateId) : '';
        $generationPrompt = trim($templateInstructions . "\n\nUser prompt:\n" . $prompt);
        $siteContext = $this->siteContextInstructions();
        if ($siteContext !== '') {
            $generationPrompt .= "\n\n" . $siteContext;
        }
        $requestedImageBrief = trim((string) ($meta['image_brief'] ?? ''));
        if ($requestedImageBrief !== '') {
            $generationPrompt .= "\n\nImage brief to follow:\n" . $requestedImageBrief;
        }

        $content = $this->openai->generateContent($settings['api_key'], $settings['text_model'], $generationPrompt);
        $this->validateGeneratedContent($content);

        $imageBrief = $requestedImageBrief !== '' ? $requestedImageBrief : trim((string) ($content['image_brief'] ?? ''));
        $imagePrompt = $this->composeImagePrompt(trim((string) ($content['image_prompt'] ?? $prompt)), $imageBrief);
        $imageBase64 = $this->openai->generateImage(
            $settings['api_key'],
            $settings['image_model'],
            $imagePrompt,
            $settings['image_size'],
            $settings['image_quality']
        );

        $imagePath = $this->imageStorage->saveBase64Png($imageBase64, (string) ($content['title'] ?? 'social-post'));

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

        if (ee()->db->field_exists('schedule_id', 'socialposter_generations')) {
            $record['schedule_id'] = (int) ($meta['schedule_id'] ?? 0);
        }

        if (ee()->db->field_exists('scheduled_for', 'socialposter_generations')) {
            $record['scheduled_for'] = (int) ($meta['scheduled_for'] ?? 0);
        }

        if (ee()->db->field_exists('source', 'socialposter_generations')) {
            $record['source'] = (string) ($meta['source'] ?? 'manual');
        }

        if (ee()->db->field_exists('template_id', 'socialposter_generations')) {
            $record['template_id'] = $templateId;
        }

        foreach (['image_model', 'image_size', 'image_quality'] as $field) {
            if (ee()->db->field_exists($field, 'socialposter_generations')) {
                $record[$field] = (string) $settings[$field];
            }
        }

        ee()->db->insert('socialposter_generations', $record);
        $record['id'] = (int) ee()->db->insert_id();

        return $this->normalizeRecord($record);
    }

    public function latest(int $limit = 20): array
    {
        if (! ee()->db->table_exists('socialposter_generations')) {
            return [];
        }

        $rows = ee()->db
            ->order_by('created_at', 'DESC')
            ->limit($limit)
            ->get('socialposter_generations')
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

        if (! ee()->db->table_exists('socialposter_generations')) {
            return [
                'summary' => $summary,
                'rows' => [],
                'costs' => $this->actualOpenAiCosts(),
            ];
        }

        $select = ['id', 'title', 'raw_response', 'created_at', 'image_path'];
        foreach (['source', 'template_id', 'image_model', 'image_size', 'image_quality'] as $field) {
            if (ee()->db->field_exists($field, 'socialposter_generations')) {
                $select[] = $field;
            }
        }

        $rows = ee()->db
            ->select($select)
            ->order_by('created_at', 'DESC')
            ->limit($limit)
            ->get('socialposter_generations')
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
        if (! ee()->db->table_exists('socialposter_generations')) {
            return null;
        }

        $row = ee()->db->where('id', $id)->get('socialposter_generations')->row_array();
        return $row ? $this->normalizeRecord($row) : null;
    }

    public function update(int $id, array $input): bool
    {
        $row = ee()->db->where('id', $id)->get('socialposter_generations')->row_array();
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

        if (ee()->db->field_exists('template_id', 'socialposter_generations')) {
            $record['template_id'] = (int) ($input['template_id'] ?? 0);
        }

        ee()->db->where('id', $id)->update('socialposter_generations', $record);

        return true;
    }

    public function regenerateImage(int $id, array $input): ?array
    {
        $row = ee()->db->where('id', $id)->get('socialposter_generations')->row_array();
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

        $composedPrompt = $this->composeImagePrompt($this->baseImagePrompt($imagePrompt), $imageBrief);
        $imageBase64 = $this->openai->generateImage(
            $settings['api_key'],
            $settings['image_model'],
            $composedPrompt,
            $settings['image_size'],
            $settings['image_quality']
        );

        $imagePath = $this->imageStorage->saveBase64Png($imageBase64, (string) ($row['title'] ?? 'social-post'));

        ee()->db->where('id', $id)->update('socialposter_generations', [
            'image_brief' => $imageBrief,
            'image_prompt' => $composedPrompt,
            'image_path' => $imagePath,
        ] + $this->imageSettingsRecord($settings));

        return $this->find($id);
    }

    public function delete(int $id): bool
    {
        $row = ee()->db->where('id', $id)->get('socialposter_generations')->row_array();
        if (! $row) {
            return false;
        }

        if (! empty($row['image_path'])) {
            $path = FCPATH . ltrim((string) $row['image_path'], '/');
            if (is_file($path)) {
                @unlink($path);
            }
        }

        ee()->db->where('id', $id)->delete('socialposter_generations');
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

    private function normalizeRecord(array $record): array
    {
        $record['table_of_contents'] = $this->decodeList($record['table_of_contents'] ?? []);
        $record['keywords'] = $this->decodeList($record['keywords'] ?? []);
        $record['hashtags'] = $this->decodeList($record['hashtags'] ?? []);
        $record['recommended_topics'] = $this->decodeList($record['recommended_topics'] ?? []);
        $record['image_url'] = ! empty($record['image_path']) ? '/' . ltrim((string) $record['image_path'], '/') : '';
        $record['internal_link_title'] = $this->internalLinkTitle((string) ($record['internal_link'] ?? ''));
        return $record;
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
            if (ee()->db->field_exists($field, 'socialposter_generations')) {
                $record[$field] = (string) $settings[$field];
            }
        }

        return $record;
    }

    private function validateGeneratedContent(array $content): void
    {
        $title = trim((string) ($content['title'] ?? ''));
        $postText = trim((string) ($content['post_text'] ?? ''));
        $tocItems = array_values(array_filter(array_map('trim', (array) ($content['table_of_contents'] ?? []))));

        if ($title === '' || $postText === '') {
            throw new \RuntimeException('OpenAI returned incomplete article content.');
        }

        if ($tocItems) {
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
        if ($internalLink !== '' && ! $this->validInternalLink($internalLink)) {
            throw new \RuntimeException('OpenAI returned an invalid internal link: ' . $internalLink);
        }
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

    private function siteContextInstructions(): string
    {
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

    private function composeImagePrompt(string $imagePrompt, string $imageBrief): string
    {
        $realisticDirection = implode("\n", [
            'Generate a realistic, photorealistic image suitable for a professional social post or blog hero.',
            'Use natural lighting, believable materials, authentic environments, and real-world perspective.',
            'Avoid illustrations, vector art, infographics, diagrams, quote cards, UI mockups, rendered text, logos, watermarks, and decorative typography unless the user explicitly requests visible text.',
        ]);
        $imagePrompt = trim($realisticDirection . "\n\nSubject and composition:\n" . $imagePrompt);

        if ($imageBrief === '') {
            return $imagePrompt;
        }

        return trim($imagePrompt . "\n\nImage brief:\n" . $imageBrief);
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
