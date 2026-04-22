<?php

namespace JavidFazaeli\SocialPoster\ControlPanel\Routes;

use ExpressionEngine\Service\Addon\Controllers\Mcp\AbstractRoute;

class Templates extends AbstractRoute
{
    use LoadsStyle;

    protected $route_path = 'templates';
    protected $cp_page_title = 'SocialPoster Templates';

    public function process($id = false)
    {
        $this->addBreadcrumb('index', 'SocialPoster');
        $this->addBreadcrumb('templates', 'Templates');
        $this->loadStyle();

        $templates = ee('socialposter:templates');
        $id = (int) $id;

        if (ee('Request')->post('save_template')) {
            try {
                $savedId = $templates->save([
                    'title' => ee()->input->post('title', true),
                    'content_type' => ee()->input->post('content_type', true),
                    'platform' => ee()->input->post('platform', true),
                    'length_preset' => ee()->input->post('length_preset', true),
                    'word_count' => ee()->input->post('word_count', true),
                    'tone' => ee()->input->post('tone', true),
                    'audience' => ee()->input->post('audience', true),
                    'goal' => ee()->input->post('goal', true),
                    'research_mode' => ee()->input->post('research_mode', true),
                    'citation_count' => ee()->input->post('citation_count', true),
                    'internal_link_count' => ee()->input->post('internal_link_count', true),
                    'external_link_count' => ee()->input->post('external_link_count', true),
                    'schema_type' => ee()->input->post('schema_type', true),
                    'image_style' => ee()->input->post('image_style', true),
                    'cta_style' => ee()->input->post('cta_style', true),
                    'prompt_instructions' => ee()->input->post('prompt_instructions', false),
                    'is_default' => ee()->input->post('is_default', true),
                ], $id);

                ee('CP/Alert')->makeBanner('socialposter-templates')
                    ->asSuccess()
                    ->withTitle('Template saved')
                    ->addToBody('Generation settings are ready to use.')
                    ->defer();

                ee()->functions->redirect(ee('CP/URL')->make('addons/settings/socialposter/templates/' . $savedId));
            } catch (\Throwable $e) {
                ee('CP/Alert')->makeBanner('socialposter-templates')
                    ->asIssue()
                    ->withTitle('Template was not saved')
                    ->addToBody($e->getMessage())
                    ->now();
            }
        }

        if (ee('Request')->post('delete_template')) {
            $templates->delete((int) ee()->input->post('delete_template'));
            ee()->functions->redirect(ee('CP/URL')->make('addons/settings/socialposter/templates'));
        }

        $editing = $id > 0 ? $templates->find($id) : null;
        $editorActive = $id > 0 || ee()->input->get('tab', true) === 'editor';
        if ($id > 0 && ! $editing) {
            ee('CP/Alert')->makeBanner('socialposter-templates')
                ->asIssue()
                ->withTitle('Template not found')
                ->addToBody('That generation template does not exist.')
                ->defer();
            ee()->functions->redirect(ee('CP/URL')->make('addons/settings/socialposter/templates'));
        }

        $this->setBody('Templates', [
            'templates_url' => ee('CP/URL')->make('addons/settings/socialposter/templates')->compile(),
            'new_template_url' => ee('CP/URL')->make('addons/settings/socialposter/templates', ['tab' => 'editor'])->compile(),
            'save_url' => ee('CP/URL')->make('addons/settings/socialposter/templates' . ($id > 0 ? '/' . $id : ''))->compile(),
            'calendar_url' => ee('CP/URL')->make('addons/settings/socialposter/calendar')->compile(),
            'index_url' => ee('CP/URL')->make('addons/settings/socialposter')->compile(),
            'rows' => $templates->all(),
            'editing' => $editing,
            'editor_active' => $editorActive,
            'field_options' => $templates->fieldOptions(),
        ]);

        return $this;
    }
}
