<?php

namespace JavidFazaeli\SocialPoster\ControlPanel\Routes;

use ExpressionEngine\Service\Addon\Controllers\Mcp\AbstractRoute;

class Settings extends AbstractRoute
{
    use LoadsStyle;

    /**
     * @var string
     */
    protected $route_path = 'settings';

    /**
     * @var string
     */
    protected $cp_page_title = 'SocialPoster Settings';

    /**
     * @param false $id
     * @return AbstractRoute
     */
    public function process($id = false)
    {
        $this->addBreadcrumb('index', 'SocialPoster');
        $this->addBreadcrumb('settings', 'Settings');
        $this->loadStyle();

        $generator = ee('socialposter:generator');

        if (ee('Request')->post('save_settings')) {
            try {
                $generator->saveSettings([
                    'api_key' => ee()->input->post('api_key', false),
                    'admin_api_key' => ee()->input->post('admin_api_key', false),
                    'openai_project_id' => ee()->input->post('openai_project_id', true),
                    'text_model' => ee()->input->post('text_model', true),
                    'image_model' => ee()->input->post('image_model', true),
                    'image_size' => ee()->input->post('image_size', true),
                    'image_quality' => ee()->input->post('image_quality', true),
                ]);

                ee('CP/Alert')->makeBanner('socialposter-settings')
                    ->asSuccess()
                    ->withTitle('Settings saved')
                    ->addToBody('SocialPoster settings were updated.')
                    ->defer();

                ee()->functions->redirect(ee('CP/URL')->make('addons/settings/socialposter/settings'));
            } catch (\Throwable $e) {
                ee('CP/Alert')->makeBanner('socialposter-settings')
                    ->asIssue()
                    ->withTitle('Settings were not saved')
                    ->addToBody($e->getMessage())
                    ->now();
            }
        }

        $settings = $generator->getSettings();

        $this->setBody('Settings', [
            'save_url' => ee('CP/URL')->make('addons/settings/socialposter/settings')->compile(),
            'index_url' => ee('CP/URL')->make('addons/settings/socialposter')->compile(),
            'history_url' => ee('CP/URL')->make('addons/settings/socialposter/history')->compile(),
            'settings' => $settings,
            'image_models' => $generator->imageModels(),
            'api_key_saved' => $settings['api_key'] !== '',
            'admin_api_key_saved' => $settings['admin_api_key'] !== '',
        ]);

        return $this;
    }
}
