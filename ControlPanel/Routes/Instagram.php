<?php

namespace JavidFazaeli\SocialPoster\ControlPanel\Routes;

use ExpressionEngine\Service\Addon\Controllers\Mcp\AbstractRoute;

class Instagram extends AbstractRoute
{
    use LoadsStyle;
    use BuildsRouteContext;

    protected $route_path = 'instagram';
    protected $cp_page_title = 'SocialPoster Instagram';

    public function process($id = false)
    {
        $this->addBreadcrumb('index', 'SocialPoster');
        $this->addBreadcrumb('instagram', 'Instagram');
        $this->loadStyle();

        $generator  = ee('socialposter:generator');
$baseUrl    = ee('CP/URL')->make('addons/settings/socialposter/instagram');
        $activeTab  = 'credentials';
        $coreUrls = $this->coreRouteContext();
        $instagramUrls = $this->instagramRouteContext();

        if (ee('Request')->post('save_instagram_settings')) {
            try {
                $generator->saveSettings([
                    'instagram_app_id'              => ee()->input->post('instagram_app_id', true),
                    'instagram_app_secret'          => ee()->input->post('instagram_app_secret', false),
                    'instagram_ig_app_id'           => ee()->input->post('instagram_ig_app_id', true),
                    'instagram_ig_app_secret'       => ee()->input->post('instagram_ig_app_secret', false),
                    'instagram_graph_version'       => ee()->input->post('instagram_graph_version', true),
                    'instagram_page_id'             => ee()->input->post('instagram_page_id', true),
                    'instagram_business_account_id' => ee()->input->post('instagram_business_account_id', true),
                    'instagram_access_token'        => ee()->input->post('instagram_access_token', false),
                ]);

                ee('CP/Alert')->makeBanner('socialposter-instagram-settings')
                    ->asSuccess()->withTitle('Instagram settings saved')
                    ->addToBody('Instagram connection settings were updated.')->defer();

                ee()->functions->redirect($baseUrl->compile());
            } catch (\Throwable $e) {
                ee('CP/Alert')->makeBanner('socialposter-instagram-settings')
                    ->asIssue()->withTitle('Instagram settings were not saved')
                    ->addToBody($e->getMessage())->now();
            }
        }

        if (ee('Request')->post('exchange_instagram_token')) {
            try {
                $shortToken = (string) ee()->input->post('exchange_short_token', false);
                $result     = ee('socialposter:publisher')->exchangeInstagramToken($shortToken);

                $generator->saveSettings([
                    'instagram_access_token'      => $result['access_token'],
                    'instagram_user_access_token' => $result['access_token'],
                ]);

                $msg = 'Long-lived token saved.';
                if ($result['expires_in'] > 0) {
                    $expiry = ee()->localize->now + (int) $result['expires_in'];
                    $days   = (int) floor($result['expires_in'] / 86400);
                    $msg   .= ' Expires in ' . $days . ' days (' . date('M j, Y', $expiry) . ').';
                } else {
                    $msg .= ' Token does not expire.';
                }

                ee('CP/Alert')->makeBanner('socialposter-token-exchange')
                    ->asSuccess()->withTitle('Token exchanged')
                    ->addToBody($msg)->defer();

                ee()->functions->redirect($baseUrl->compile() . '?tab=tokens');
            } catch (\Throwable $e) {
                ee('CP/Alert')->makeBanner('socialposter-token-exchange')
                    ->asIssue()->withTitle('Token exchange failed')
                    ->addToBody($e->getMessage())->now();
                $activeTab = 'tokens';
            }
        }

        $pages      = null;
        $testResult = null;

        if (ee('Request')->post('test_instagram_connection')) {
            $testResult = ee('socialposter:publisher')->testInstagramConnection();
            $activeTab  = 'tokens';
        }

        if (ee('Request')->post('list_instagram_pages')) {
            try {
                $pages = ee('socialposter:publisher')->listInstagramPages();
                if (empty($pages)) {
                    ee('CP/Alert')->makeBanner('socialposter-list-pages')
                        ->asIssue()->withTitle('No pages found')
                        ->addToBody('Your account does not admin any Facebook Pages, or the saved token is missing the pages_show_list scope. Re-exchange a token with that scope first.')
                        ->now();
                }
            } catch (\Throwable $e) {
                ee('CP/Alert')->makeBanner('socialposter-list-pages')
                    ->asIssue()->withTitle('Could not list pages')
                    ->addToBody($e->getMessage())->now();
            }
            $activeTab = 'tokens';
        }

        if (ee('Request')->post('use_page_token')) {
            try {
                $pageId = (string) ee()->input->post('page_id', true);
                $page   = ee('socialposter:publisher')->selectInstagramPage($pageId);

                $updates = ['instagram_access_token' => $page['access_token']];
                if ($page['id'] !== '') {
                    $updates['instagram_page_id'] = $page['id'];
                }
                if ($page['instagram_business_account_id'] !== '') {
                    $updates['instagram_business_account_id'] = $page['instagram_business_account_id'];
                }

                $generator->saveSettings($updates);

                $body = 'Saved the access token for "' . $page['name'] . '". Page tokens issued from a long-lived user token do not expire.';
                if (! empty($page['instagram_username'])) {
                    $body .= ' IG account @' . $page['instagram_username'] . ' linked to this page is auto-saved as the Business Account ID.';
                }

                ee('CP/Alert')->makeBanner('socialposter-page-token')
                    ->asSuccess()->withTitle('Page token saved')
                    ->addToBody($body)->defer();

                ee()->functions->redirect($baseUrl->compile() . '?tab=tokens');
            } catch (\Throwable $e) {
                ee('CP/Alert')->makeBanner('socialposter-page-token')
                    ->asIssue()->withTitle('Could not save page token')
                    ->addToBody($e->getMessage())->now();
                $activeTab = 'tokens';
            }
        }

        if (ee('Request')->post('save_instagram_image_settings')) {
            try {
                $generator->saveSettings([
                    'instagram_cover_provider' => ee()->input->post('instagram_cover_provider', true),
                    'instagram_slide_provider' => ee()->input->post('instagram_slide_provider', true),
                    'ideogram_api_key'         => ee()->input->post('ideogram_api_key', false),
                    'carousel_slides'          => ee()->input->post('carousel_slides', true),
                ]);

                ee('CP/Alert')->makeBanner('socialposter-instagram-image-settings')
                    ->asSuccess()->withTitle('Image settings saved')
                    ->addToBody('Instagram image generation settings were updated.')->defer();

                ee()->functions->redirect($baseUrl->compile() . '?tab=images');
            } catch (\Throwable $e) {
                ee('CP/Alert')->makeBanner('socialposter-instagram-image-settings')
                    ->asIssue()->withTitle('Image settings were not saved')
                    ->addToBody($e->getMessage())->now();
                $activeTab = 'images';
            }
        }

        // Honour ?tab= from redirect or manual navigation
        $tabFromGet = trim((string) ee()->input->get('tab', true));
        if ($tabFromGet !== '' && ! ee('Request')->isPost()) {
            $activeTab = $tabFromGet;
        }

        $settings    = $generator->getSettings();
        $tokenStatus = ee('socialposter:publisher')->inspectInstagramToken();

        $this->setBody('Instagram', [
            'save_url'                    => $baseUrl->compile(),
            'save_image_settings_url'     => $baseUrl->compile(),
            'general_url'                 => $instagramUrls['general_url'],
            'generate_url'                => $instagramUrls['instagram_generate_url'],
            'instagram_history_url'       => $instagramUrls['instagram_history_url'],
            'instagram_carousel_url'      => $instagramUrls['instagram_carousel_url'],
            'settings_url'                => $coreUrls['settings_url'],
            'publish_url'                 => $instagramUrls['publish_instagram_url'],
            'settings'                    => $settings,
            'token_status'                => $tokenStatus,
            'pages'                       => $pages,
            'test_result'                 => $testResult,
            'active_tab'                  => $activeTab,
            'instagram_app_secret_saved'  => $settings['instagram_app_secret'] !== '',
            'instagram_access_token_saved'=> $settings['instagram_access_token'] !== '',
            'ideogram_api_key_saved'      => $settings['ideogram_api_key'] !== '',
        ]);

        return $this;
    }
}
