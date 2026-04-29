<?php

namespace JavidFazaeli\SocialPoster\Service;

class IdeogramClient
{
    private string $apiBase = 'https://api.ideogram.ai';

    /**
     * Generate a 1:1 image and return the raw PNG binary (ready to base64-encode).
     *
     * @throws \RuntimeException on API or download failure
     */
    public function generateImage(string $apiKey, string $prompt): string
    {
        $payload = [
            'image_request' => [
                'prompt'              => $prompt,
                'aspect_ratio'        => 'ASPECT_1_1',
                'model'               => 'V_2',
                'magic_prompt_option' => 'AUTO',
            ],
        ];

        $response = $this->postJson($apiKey, '/generate', $payload);

        $url = (string) ($response['data'][0]['url'] ?? '');
        if ($url === '') {
            throw new \RuntimeException('Ideogram API returned no image URL.');
        }

        $binary = $this->download($url);
        if ($binary === '') {
            throw new \RuntimeException('Failed to download image from Ideogram.');
        }

        return base64_encode($binary);
    }

    private function postJson(string $apiKey, string $path, array $payload): array
    {
        $ch = curl_init($this->apiBase . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Api-Key: ' . $apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT    => 120,
        ]);

        $body  = curl_exec($ch);
        $error = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false || $body === '') {
            throw new \RuntimeException('Ideogram request failed: ' . $error);
        }

        $decoded = json_decode((string) $body, true);
        if (! is_array($decoded)) {
            throw new \RuntimeException('Ideogram returned a non-JSON response.');
        }

        if ($status < 200 || $status >= 300) {
            $message = (string) ($decoded['message'] ?? $decoded['detail'] ?? ('Ideogram request failed with HTTP ' . $status));
            throw new \RuntimeException($message);
        }

        return $decoded;
    }

    private function download(string $url): string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 60,
        ]);

        $body  = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            throw new \RuntimeException('Failed to download Ideogram image: ' . $error);
        }

        return (string) $body;
    }
}
