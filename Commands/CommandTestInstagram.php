<?php

namespace JavidFazaeli\SocialPoster\Commands;

use ExpressionEngine\Cli\Cli;

class CommandTestInstagram extends Cli
{
    public $name = 'TestInstagram';
    public $signature = 'socialposter:test-instagram';
    public $description = 'Test saved SocialPoster Instagram Graph API credentials.';
    public $summary = 'Calls safe Meta Graph API read endpoints and redacts access tokens.';
    public $usage = 'php eecli.php socialposter:test-instagram';

    public function handle()
    {
        $settings = ee('socialposter:generator')->getSettings();

        $token = trim((string) ($settings['instagram_access_token'] ?? ''));
        $pageId = trim((string) ($settings['instagram_page_id'] ?? ''));
        $igId = trim((string) ($settings['instagram_business_account_id'] ?? ''));
        $version = trim((string) ($settings['instagram_graph_version'] ?? 'v24.0')) ?: 'v24.0';

        $this->info('Saved settings present:');
        $this->info('- page_id: ' . ($pageId !== '' ? 'yes' : 'no'));
        $this->info('- instagram_business_account_id: ' . ($igId !== '' ? 'yes' : 'no'));
        $this->info('- instagram_access_token: ' . ($token !== '' ? 'yes' : 'no'));
        $this->info('- graph_version: ' . $version);

        if ($token === '') {
            $this->error('No saved Instagram access token to test.');
            return;
        }

        $tests = [
            'me' => '/me?fields=id,name',
            'me_instagram' => '/me?fields=id,name,instagram_business_account',
        ];

        if ($pageId !== '') {
            $tests['saved_page'] = '/' . rawurlencode($pageId) . '?fields=id,name,instagram_business_account';
        }

        $tests['me_accounts'] = '/me/accounts?fields=id,name,access_token,instagram_business_account';

        foreach ($tests as $name => $path) {
            $this->info('');
            $this->info('== ' . $name . ' ==');
            $result = $this->graphCall($version, $path, $token);
            $this->info(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }
    }

    private function graphCall(string $version, string $path, string $token): array
    {
        $url = 'https://graph.facebook.com/' . rawurlencode($version) . '/' . ltrim($path, '/');
        $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query(['access_token' => $token]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
        ]);

        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        $decoded = json_decode((string) $body, true);

        return [
            'status' => $status,
            'error' => $error ?: null,
            'body' => $this->redactTokens(is_array($decoded) ? $decoded : ['raw' => $body]),
        ];
    }

    private function redactTokens($value)
    {
        if (! is_array($value)) {
            return $value;
        }

        $redacted = [];
        foreach ($value as $key => $item) {
            $redacted[$key] = strtolower((string) $key) === 'access_token'
                ? '[redacted]'
                : $this->redactTokens($item);
        }

        return $redacted;
    }
}
