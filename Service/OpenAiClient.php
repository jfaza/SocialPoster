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
                        'For every item in table_of_contents, include a matching Markdown section heading in post_text using ## with the exact same text.',
                        'Use intro_text only as the short opening summary; put the full article body, examples, lists, proof points, and CTA in post_text.',
                        'Do not use placeholder internal links or fake domains. Internal links must be selected from supplied existing blog posts when one is clearly related, otherwise empty.',
                        'Set category to one concise blog category name, preferring an available blog category supplied in the prompt. Set hashtags to 3-8 relevant tags including the leading #, and image_brief to a practical creative direction for the image.',
                        'Keep image_prompt production-ready and consistent with image_brief. Do not ask the image model to render text unless the user explicitly requests it.',
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

        $data['_raw_response'] = $response;
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
