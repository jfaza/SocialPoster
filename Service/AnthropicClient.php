<?php

namespace JavidFazaeli\SocialPoster\Service;

class AnthropicClient
{
    private const BASE_URL = 'https://api.anthropic.com/v1';
    private const API_VERSION = '2023-06-01';

    public function generateCarouselContent(
        string $apiKey,
        string $model,
        string $systemPrompt,
        string $userPrompt,
        array $schema
    ): array {
        $payload = [
            'model'      => $model,
            'max_tokens' => 8192,
            'system'     => $systemPrompt,
            'tools'      => [[
                'name'         => 'generate_carousel',
                'description'  => 'Generate structured carousel slide content.',
                'input_schema' => $schema,
            ]],
            'tool_choice' => ['type' => 'tool', 'name' => 'generate_carousel'],
            'messages'    => [
                ['role' => 'user', 'content' => $userPrompt],
            ],
        ];

        $response = $this->postJson('/messages', $apiKey, $payload);

        foreach ((array) ($response['content'] ?? []) as $block) {
            if (($block['type'] ?? '') === 'tool_use' && isset($block['input']) && is_array($block['input'])) {
                return $block['input'];
            }
        }

        throw new \RuntimeException('Anthropic did not return structured carousel content.');
    }

    private function postJson(string $path, string $apiKey, array $payload): array
    {
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $ch   = curl_init(self::BASE_URL . $path);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-api-key: ' . $apiKey,
                'anthropic-version: ' . self::API_VERSION,
            ],
        ]);

        $raw  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($err !== '') {
            throw new \RuntimeException('Anthropic request failed: ' . $err);
        }

        $data = json_decode((string) $raw, true);

        if ($code >= 400) {
            $msg = $data['error']['message'] ?? $raw;
            throw new \RuntimeException("Anthropic API error ($code): $msg");
        }

        return is_array($data) ? $data : [];
    }
}
