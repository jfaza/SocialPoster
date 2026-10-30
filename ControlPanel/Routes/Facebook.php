<?php

namespace JavidFazaeli\SocialPoster\ControlPanel\Routes;

use ExpressionEngine\Service\Addon\Controllers\Mcp\AbstractRoute;

class Facebook extends AbstractRoute
{
    use LoadsStyle;
    use BuildsRouteContext;

    protected $route_path = 'facebook';
    protected $cp_page_title = 'SocialPoster Facebook';

    public function process($id = false)
    {
        $this->addBreadcrumb('index', 'SocialPoster');
        $this->addBreadcrumb('facebook', 'Facebook');
        $this->loadStyle();

        $generator = ee('socialposter:generator');
        $baseUrl   = ee('CP/URL')->make('addons/settings/socialposter/facebook');
        $activeTab = 'credentials';
        $coreUrls = $this->coreRouteContext();
        $facebookUrls = $this->facebookRouteContext();

        if (ee('Request')->post('save_facebook_settings')) {
            try {
                $generator->saveSettings([
                    'facebook_app_id'        => ee()->input->post('facebook_app_id', true),
                    'facebook_app_secret'    => ee()->input->post('facebook_app_secret', false),
                    'facebook_graph_version' => ee()->input->post('facebook_graph_version', true),
                    'facebook_page_id'       => ee()->input->post('facebook_page_id', true),
                    'facebook_access_token'  => ee()->input->post('facebook_access_token', false),
                ]);

                ee('CP/Alert')->makeBanner('socialposter-facebook-settings')
                    ->asSuccess()->withTitle('Facebook settings saved')
                    ->addToBody('Facebook Page publishing settings were updated.')->defer();

                ee()->functions->redirect($baseUrl->compile());
            } catch (\Throwable $e) {
                ee('CP/Alert')->makeBanner('socialposter-facebook-settings')
                    ->asIssue()->withTitle('Facebook settings were not saved')
                    ->addToBody($e->getMessage())->now();
            }
        }

        if (ee('Request')->post('exchange_facebook_token')) {
            try {
                $shortToken = (string) ee()->input->post('exchange_short_token', false);
                $result = ee('socialposter:publisher')->exchangeFacebookToken($shortToken);

                $generator->saveSettings([
                    'facebook_access_token'      => $result['access_token'],
                    'facebook_user_access_token' => $result['access_token'],
                ]);

                $msg = 'Long-lived user token saved.';
                if ($result['expires_in'] > 0) {
                    $days = (int) floor($result['expires_in'] / 86400);
                    $msg .= ' Expires in ' . $days . ' days.';
                }

                ee('CP/Alert')->makeBanner('socialposter-facebook-token')
                    ->asSuccess()->withTitle('Token exchanged')
                    ->addToBody($msg)->defer();

                ee()->functions->redirect($baseUrl->compile() . '?tab=tokens');
            } catch (\Throwable $e) {
                ee('CP/Alert')->makeBanner('socialposter-facebook-token')
                    ->asIssue()->withTitle('Token exchange failed')
                    ->addToBody($e->getMessage())->now();
                $activeTab = 'tokens';
            }
        }

        $pages = null;
        $testResult = null;

        if (ee('Request')->post('list_facebook_pages')) {
            try {
                $pages = ee('socialposter:publisher')->listFacebookPages();
                if (empty($pages)) {
                    ee('CP/Alert')->makeBanner('socialposter-facebook-pages')
                        ->asIssue()->withTitle('No pages found')
                        ->addToBody('Your token did not return any Facebook Pages. Check pages_show_list and Page access in Meta.')
                        ->now();
                }
            } catch (\Throwable $e) {
                ee('CP/Alert')->makeBanner('socialposter-facebook-pages')
                    ->asIssue()->withTitle('Could not list pages')
                    ->addToBody($e->getMessage())->now();
            }
            $activeTab = 'tokens';
        }

        if (ee('Request')->post('use_facebook_page_token')) {
            try {
                $pageId = (string) ee()->input->post('page_id', true);
                $page = ee('socialposter:publisher')->selectFacebookPage($pageId);

                $generator->saveSettings([
                    'facebook_page_id'      => $page['id'],
                    'facebook_access_token' => $page['access_token'],
                ]);

                ee('CP/Alert')->makeBanner('socialposter-facebook-page-token')
                    ->asSuccess()->withTitle('Page token saved')
                    ->addToBody('Saved the Page access token for "' . $page['name'] . '".')
                    ->defer();

                ee()->functions->redirect($baseUrl->compile() . '?tab=tokens');
            } catch (\Throwable $e) {
                ee('CP/Alert')->makeBanner('socialposter-facebook-page-token')
                    ->asIssue()->withTitle('Could not save Page token')
                    ->addToBody($e->getMessage())->now();
                $activeTab = 'tokens';
            }
        }

        if (ee('Request')->post('test_facebook_connection')) {
            $testResult = ee('socialposter:publisher')->testFacebookConnection();
            $activeTab = 'tokens';
        }

        $tabFromGet = trim((string) ee()->input->get('tab', true));
        if ($tabFromGet !== '' && ! ee('Request')->isPost()) {
            $activeTab = $tabFromGet;
        }

        $settings = $generator->getSettings();
        $effective = [
            'app_id'        => trim((string) ($settings['facebook_app_id'] ?? '')) ?: trim((string) ($settings['instagram_app_id'] ?? '')),
            'app_secret'    => trim((string) ($settings['facebook_app_secret'] ?? '')) ?: trim((string) ($settings['instagram_app_secret'] ?? '')),
            'graph_version' => trim((string) ($settings['facebook_graph_version'] ?? '')) ?: trim((string) ($settings['instagram_graph_version'] ?? 'v24.0')),
            'page_id'       => trim((string) ($settings['facebook_page_id'] ?? '')) ?: trim((string) ($settings['instagram_page_id'] ?? '')),
            'access_token'  => trim((string) ($settings['facebook_access_token'] ?? '')) ?: trim((string) ($settings['instagram_access_token'] ?? '')),
        ];

        $this->setBody('Facebook', [
            'save_url'                    => $baseUrl->compile(),
            'general_url'                 => $coreUrls['index_url'],
            'history_url'                 => $coreUrls['history_url'],
            'publish_url'                 => $facebookUrls['publish_facebook_url'],
            'settings'                    => $settings,
            'effective_settings'          => $effective,
            'token_status'                => ee('socialposter:publisher')->inspectFacebookToken(),
            'pages'                       => $pages,
            'test_result'                 => $testResult,
            'active_tab'                  => $activeTab,
            'facebook_app_secret_saved'   => $settings['facebook_app_secret'] !== '',
            'facebook_access_token_saved' => $settings['facebook_access_token'] !== '',
        ]);

        return $this;
    }
}
