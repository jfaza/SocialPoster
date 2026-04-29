<?php

namespace JavidFazaeli\SocialPoster\Service;

class OpenAiClient
{
    private string $baseUrl = 'https://api.openai.com/v1';

    public function generateContent(string $apiKey, string $model, string $prompt): array
    {
        $payload = [
            'model' => $model,
            'input' => [
                [
                    'role' => 'system',
                    'content' => implode("\n", [
                        'You generate complete, practical content packages for a personal portfolio and consulting website.',
                        'When the requested platform is Website or the template asks for an SEO article, post_text must be a complete Markdown article, not a summary.',
                        'For Website or SEO article templates only, every item in table_of_contents must have a matching Markdown section heading in post_text using ## with the exact same text.',
                        'For social templates, post_text must be native social copy for that platform, not a blog article. table_of_contents may be a slide list, thread outline, or content plan.',
                        'For Instagram carousel templates, post_text must be compact slide-by-slide copy plus caption and hashtags. Each slide should include its own concise visual direction line. Also populate the slides array with one item per slide, including slide_number, type, text fields, and image_brief. In the slides array, always include every slide property and use empty strings or an empty bullets array when a field does not apply to that slide type. Do not write long article-style sections. The system generates one image, so image_brief and image_prompt must describe a single square carousel cover or reusable visual system, not separate images for each slide.',
                        'Use intro_text only as the short opening summary; put the full article body or native social copy in post_text.',
                        'Do not use placeholder internal links or fake domains. Internal links must be selected from supplied existing blog posts when one is clearly related, otherwise empty.',
                        'Set category to one concise blog category name, preferring an available blog category supplied in the prompt. Set hashtags to 3-8 relevant tags including the leading #, and image_brief to a practical creative direction for a realistic image.',
                        'Keep image_prompt production-ready, photorealistic, and consistent with image_brief. Favor realistic scenes, natural light, believable objects, and authentic environments over infographics, diagrams, quote cards, illustrations, or branded graphics.',
                        'Do not ask the image model to render text unless the user explicitly requests it.',
                        'Return only valid structured data.',
                    ]),
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
            'text' => [
                'format' => [
                    'type' => 'json_schema',
                    'name' => 'social_post_package',
                    'strict' => true,
                    'schema' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => [
                            'title',
                            'post_text',
                            'intro_text',
                            'table_of_contents',
                            'seo_keywords',
                            'category',
                            'hashtags',
                            'external_link',
                            'internal_link',
                            'recommended_topics',
                            'image_brief',
                            'image_prompt',
                            'slides',
                        ],
                        'properties' => [
                            'title' => ['type' => 'string'],
                            'post_text' => ['type' => 'string'],
                            'intro_text' => ['type' => 'string'],
                            'table_of_contents' => [
                                'type' => 'array',
                                'items' => ['type' => 'string'],
                            ],
                            'seo_keywords' => [
                                'type' => 'array',
                                'items' => ['type' => 'string'],
                            ],
                            'category' => ['type' => 'string'],
                            'hashtags' => [
                                'type' => 'array',
                                'items' => ['type' => 'string'],
                            ],
                            'external_link' => ['type' => 'string'],
                            'internal_link' => ['type' => 'string'],
                            'recommended_topics' => [
                                'type' => 'array',
                                'items' => ['type' => 'string'],
                            ],
                            'image_brief' => ['type' => 'string'],
                            'image_prompt' => ['type' => 'string'],
                            'slides' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'additionalProperties' => false,
                                    'required' => ['slide_number', 'type', 'title', 'subtitle', 'heading', 'bullets', 'image_brief'],
                                    'properties' => [
                                        'slide_number' => ['type' => 'integer'],
                                        'type' => ['type' => 'string'],
                                        'title' => ['type' => 'string'],
                                        'subtitle' => ['type' => 'string'],
                                        'heading' => ['type' => 'string'],
                                        'bullets' => [
                                            'type' => 'array',
                                            'items' => ['type' => 'string'],
                                        ],
                                        'image_brief' => ['type' => 'string'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/responses', $apiKey, $payload);
        $data = json_decode($this->extractOutputText($response), true);

        if (! is_array($data)) {
            throw new \RuntimeException('OpenAI returned an invalid structured content response.');
        }

        $response['parsed_content'] = $data;
        $data['_raw_response'] = $response;
        return $data;
    }

    public function generateNewsPostText(string $apiKey, string $model, string $prompt): array
    {
        $payload = [
            'model' => $model,
            'input' => [
                [
                    'role'    => 'system',
                    'content' => implode("\n", [
                        'You write Instagram captions and hashtags based on a news article.',
                        'Write an engaging caption that: hooks the reader in the first line, distills the key insight, adds a brief perspective or commentary, and ends with a question or CTA.',
                        'Keep it conversational — 2–4 short paragraphs. Do not fabricate facts not mentioned in the article.',
                        'Distill a concise, punchy title (not the full article headline — make it Instagram-native).',
                        'Return 5–10 relevant hashtags.',
                        'Return only valid structured data.',
                    ]),
                ],
                [
                    'role'    => 'user',
                    'content' => $prompt,
                ],
            ],
            'text' => [
                'format' => [
                    'type'   => 'json_schema',
                    'name'   => 'news_post',
                    'strict' => true,
                    'schema' => [
                        'type'                 => 'object',
                        'additionalProperties' => false,
                        'required'             => ['title', 'caption', 'hashtags'],
                        'properties'           => [
                            'title'    => ['type' => 'string'],
                            'caption'  => ['type' => 'string'],
                            'hashtags' => ['type' => 'array', 'items' => ['type' => 'string']],
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/responses', $apiKey, $payload);
        $data     = json_decode($this->extractOutputText($response), true);

        if (! is_array($data)) {
            throw new \RuntimeException('OpenAI returned an invalid news post response.');
        }

        return $data;
    }

    public function generateSingleImageText(string $apiKey, string $model, string $prompt): array
    {
        $payload = [
            'model' => $model,
            'input' => [
                [
                    'role' => 'system',
                    'content' => implode("\n", [
                        'You write Instagram captions and image prompts for single-image posts.',
                        'Return a punchy, engaging Instagram caption (2-4 short paragraphs), 5–10 relevant hashtags, and a detailed image generation prompt.',
                        'The caption should hook the reader immediately, add value, and end with a call to action or question.',
                        'The image_prompt should be production-ready, photorealistic, and visually striking — suitable for a professional Instagram feed.',
                        'Do not include logos, watermarks, or readable text in the image unless explicitly requested.',
                        'Return only valid structured data.',
                    ]),
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
            'text' => [
                'format' => [
                    'type'   => 'json_schema',
                    'name'   => 'single_image_post',
                    'strict' => true,
                    'schema' => [
                        'type'                 => 'object',
                        'additionalProperties' => false,
                        'required'             => ['caption', 'hashtags', 'image_prompt'],
                        'properties'           => [
                            'caption'      => ['type' => 'string'],
                            'hashtags'     => ['type' => 'array', 'items' => ['type' => 'string']],
                            'image_prompt' => ['type' => 'string'],
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/responses', $apiKey, $payload);
        $data = json_decode($this->extractOutputText($response), true);

        if (! is_array($data)) {
            throw new \RuntimeException('OpenAI returned an invalid single-image response.');
        }

        return $data;
    }

    public function generateCarouselContent(string $apiKey, string $model, string $systemPrompt, string $userPrompt, array $schema): array
    {
        $payload = [
            'model' => $model,
            'input' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user',   'content' => $userPrompt],
            ],
            'text' => [
                'format' => [
                    'type'   => 'json_schema',
                    'name'   => 'carousel_post',
                    'strict' => true,
                    'schema' => $schema,
                ],
            ],
        ];

        $response = $this->postJson('/responses', $apiKey, $payload);
        $data = json_decode($this->extractOutputText($response), true);

        if (! is_array($data)) {
            throw new \RuntimeException('OpenAI returned an invalid carousel content response.');
        }

        return $data;
    }

    public function generateImage(string $apiKey, string $model, string $prompt, string $size, string $quality): string
    {
        $response = $this->postJson('/images/generations', $apiKey, [
            'model' => $model,
            'prompt' => $prompt,
            'size' => $size,
            'quality' => $quality,
            'n' => 1,
        ]);

        $image = $response['data'][0]['b64_json'] ?? null;
        if (is_string($image) && $image !== '') {
            return $image;
        }

        $url = $response['data'][0]['url'] ?? null;
        if (is_string($url) && $url !== '') {
            return base64_encode($this->getBinary($url));
        }

        throw new \RuntimeException('OpenAI did not return image data.');
    }

    public function generatePlan(string $apiKey, string $model, string $prompt): array
    {
        $response = $this->postJson('/responses', $apiKey, [
            'model' => $model,
            'input' => [
                [
                    'role' => 'system',
                    'content' => implode("\n", [
                        'You help plan social and blog content before final generation.',
                        'Return concise planning fields only. Do not write the full post.',
                        'When the platform is Instagram, also return a focused topic, a slide-by-slide outline, a caption direction, and hashtag direction that fit a carousel workflow.',
                        'For Instagram carousel image descriptions, return one strong square cover visual direction that matches the slide outline. Avoid logos, watermarks, and readable text unless explicitly requested.',
                    ]),
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
            'text' => [
                'format' => [
                    'type' => 'json_schema',
                    'name' => 'socialposter_plan',
                    'strict' => true,
                    'schema' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['title', 'brief', 'keywords', 'topic', 'slide_outline', 'caption_direction', 'hashtag_direction', 'images'],
                        'properties' => [
                            'title' => ['type' => 'string'],
                            'brief' => ['type' => 'string'],
                            'keywords' => [
                                'type' => 'array',
                                'items' => ['type' => 'string'],
                            ],
                            'topic' => ['type' => 'string'],
                            'slide_outline' => ['type' => 'string'],
                            'caption_direction' => ['type' => 'string'],
                            'hashtag_direction' => ['type' => 'string'],
                            'images' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'additionalProperties' => false,
                                    'required' => ['label', 'description'],
                                    'properties' => [
                                        'label' => ['type' => 'string'],
                                        'description' => ['type' => 'string'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $data = json_decode($this->extractOutputText($response), true);
        if (! is_array($data)) {
            throw new \RuntimeException('OpenAI returned an invalid planning response.');
        }

        return $data;
    }

    public function organizationCosts(string $adminApiKey, int $startTime, int $endTime, string $projectId = ''): array
    {
        $query = [
            'start_time' => $startTime,
            'end_time' => $endTime,
            'bucket_width' => '1d',
            'group_by' => ['line_item'],
            'limit' => 31,
        ];

        if ($projectId !== '') {
            $query['project_ids'] = [$projectId];
        }

        return $this->getJson('/organization/costs', $adminApiKey, $query);
    }

    private function getJson(string $path, string $apiKey, array $query = []): array
    {
        $url = $this->baseUrl . $path;
        if ($query) {
            $url .= '?' . $this->buildQuery($query);
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT => 120,
        ]);

        $body = curl_exec($ch);
        $error = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false) {
            throw new \RuntimeException('OpenAI request failed: ' . $error);
        }

        $decoded = json_decode((string) $body, true);
        if (! is_array($decoded)) {
            throw new \RuntimeException('OpenAI returned a non-JSON response.');
        }

        if ($status < 200 || $status >= 300) {
            $message = $decoded['error']['message'] ?? ('OpenAI request failed with HTTP ' . $status);
            throw new \RuntimeException($message);
        }

        return $decoded;
    }

    private function buildQuery(array $query): string
    {
        $parts = [];
        foreach ($query as $key => $value) {
            foreach ((array) $value as $item) {
                $parts[] = rawurlencode((string) $key) . '=' . rawurlencode((string) $item);
            }
        }

        return implode('&', $parts);
    }

    private function postJson(string $path, string $apiKey, array $payload): array
    {
        $ch = curl_init($this->baseUrl . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 120,
        ]);

        $body = curl_exec($ch);
        $error = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false) {
            throw new \RuntimeException('OpenAI request failed: ' . $error);
        }

        $decoded = json_decode((string) $body, true);
        if (! is_array($decoded)) {
            throw new \RuntimeException('OpenAI returned a non-JSON response.');
        }

        if ($status < 200 || $status >= 300) {
            $message = $decoded['error']['message'] ?? ('OpenAI request failed with HTTP ' . $status);
            throw new \RuntimeException($message);
        }

        return $decoded;
    }

    private function getBinary(string $url): string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 120,
        ]);

        $body = curl_exec($ch);
        $error = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false || $status < 200 || $status >= 300) {
            throw new \RuntimeException('Could not download generated image: ' . ($error ?: 'HTTP ' . $status));
        }

        return (string) $body;
    }

    private function extractOutputText(array $response): string
    {
        if (! empty($response['output_text']) && is_string($response['output_text'])) {
            return $response['output_text'];
        }

        foreach (($response['output'] ?? []) as $output) {
            foreach (($output['content'] ?? []) as $content) {
                if (($content['type'] ?? '') === 'output_text' && isset($content['text'])) {
                    return (string) $content['text'];
                }
            }
        }

        throw new \RuntimeException('OpenAI response did not include output text.');
    }
}
