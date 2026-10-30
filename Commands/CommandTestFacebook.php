<?php

namespace JavidFazaeli\SocialPoster\Commands;

use ExpressionEngine\Cli\Cli;

class CommandTestFacebook extends Cli
{
    public $name = 'TestFacebook';
    public $signature = 'socialposter:test-facebook';
    public $description = 'Test saved SocialPoster Facebook Graph API credentials.';
    public $summary = 'Calls safe Meta Graph API read endpoints and redacts access tokens.';
    public $usage = 'php eecli.php socialposter:test-facebook';

    public function handle()
    {
        $settings = ee('socialposter:generator')->getSettings();

        $explicitPageId = trim((string) ($settings['facebook_page_id'] ?? ''));
        $fallbackPageId = trim((string) ($settings['instagram_page_id'] ?? ''));
        $pageId = $explicitPageId ?: $fallbackPageId;

        $explicitToken = trim((string) ($settings['facebook_access_token'] ?? ''));
        $fallbackToken = trim((string) ($settings['instagram_access_token'] ?? ''));
        $pageToken = $explicitToken ?: $fallbackToken;

        $userToken = trim((string) ($settings['facebook_user_access_token'] ?? ''))
            ?: trim((string) ($settings['facebook_access_token'] ?? ''))
            ?: trim((string) ($settings['instagram_user_access_token'] ?? ''))
            ?: trim((string) ($settings['instagram_access_token'] ?? ''));

        $appId = trim((string) ($settings['facebook_app_id'] ?? ''))
            ?: trim((string) ($settings['instagram_app_id'] ?? ''));
        $appSecret = trim((string) ($settings['facebook_app_secret'] ?? ''))
            ?: trim((string) ($settings['instagram_app_secret'] ?? ''));
        $version = trim((string) ($settings['facebook_graph_version'] ?? ''))
            ?: trim((string) ($settings['instagram_graph_version'] ?? 'v24.0'))
            ?: 'v24.0';

        $this->info('Saved/effective settings present:');
        $this->info('- facebook_page_id: ' . ($explicitPageId !== '' ? 'yes' : 'no'));
        $this->info('- fallback instagram_page_id: ' . ($fallbackPageId !== '' ? 'yes' : 'no'));
        $this->info('- effective page_id: ' . ($pageId !== '' ? $pageId : 'no'));
        $this->info('- facebook_access_token: ' . ($explicitToken !== '' ? 'yes' : 'no'));
        $this->info('- fallback instagram_access_token: ' . ($fallbackToken !== '' ? 'yes' : 'no'));
        $this->info('- effective publish token: ' . ($pageToken !== '' ? 'yes' : 'no'));
        $this->info('- effective user token for page listing: ' . ($userToken !== '' ? 'yes' : 'no'));
        $this->info('- effective app_id: ' . ($appId !== '' ? 'yes' : 'no'));
        $this->info('- effective app_secret: ' . ($appSecret !== '' ? 'yes' : 'no'));
        $this->info('- graph_version: ' . $version);

        if ($pageToken === '' && $userToken === '') {
            $this->error('No saved Facebook or fallback Instagram access token to test.');
            return;
        }

        if ($appId !== '' && $appSecret !== '' && $pageToken !== '') {
            $this->runTest('debug_publish_token', $version, '/debug_token', $appId . '|' . $appSecret, [
                'input_token' => $pageToken,
            ]);
        }

        if ($pageToken !== '') {
            $this->runTest('me_with_publish_token', $version, '/me', $pageToken, [
                'fields' => 'id,name',
            ]);
        }

        if ($pageId !== '' && $pageToken !== '') {
            $this->runTest('saved_page_with_publish_token', $version, '/' . rawurlencode($pageId), $pageToken, [
                'fields' => 'id,name',
            ]);
        }

        if ($userToken !== '') {
            $this->runTest('me_accounts_with_user_token', $version, '/me/accounts', $userToken, [
                'fields' => 'id,name,access_token,tasks',
                'limit' => 100,
            ]);
        }
    }

    private function runTest(string $name, string $version, string $path, string $token, array $params = []): void
    {
        $this->info('');
        $this->info('== ' . $name . ' ==');
        $result = $this->graphCall($version, $path, $token, $params);
        $this->info(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    private function graphCall(string $version, string $path, string $token, array $params = []): array
    {
        $params['access_token'] = $token;
        $url = 'https://graph.facebook.com/' . rawurlencode($version) . '/' . ltrim($path, '/');
        $url .= '?' . http_build_query($params);

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
