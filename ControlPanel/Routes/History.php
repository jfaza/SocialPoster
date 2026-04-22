<?php

namespace JavidFazaeli\SocialPoster\ControlPanel\Routes;

use ExpressionEngine\Service\Addon\Controllers\Mcp\AbstractRoute;

class History extends AbstractRoute
{
    use LoadsStyle;

    /**
     * @var string
     */
    protected $route_path = 'history';

    /**
     * @var string
     */
    protected $cp_page_title = 'SocialPoster History';

    /**
     * @param false $id
     * @return AbstractRoute
     */
    public function process($id = false)
    {
        $this->addBreadcrumb('index', 'SocialPoster');
        $this->addBreadcrumb('history', 'History');
        $this->loadStyle();

        $generator = ee('socialposter:generator');

        $id = (int) $id;

        if ($id > 0 && ee('Request')->post('regenerate_image')) {
            try {
                $post = $generator->regenerateImage($id, [
                    'image_brief' => ee()->input->post('image_brief', false),
                    'image_prompt' => ee()->input->post('image_prompt', false),
                ]);

                if (! $post) {
                    ee('CP/Alert')->makeBanner('socialposter-history')
                        ->asIssue()
                        ->withTitle('Not found')
                        ->addToBody('That generated post does not exist.')
                        ->defer();
                } else {
                    ee('CP/Alert')->makeBanner('socialposter-history')
                        ->asSuccess()
                        ->withTitle('Image regenerated')
                        ->addToBody('The generated image was updated without saving the full post.')
                        ->defer();
                }
            } catch (\Throwable $e) {
                ee('CP/Alert')->makeBanner('socialposter-history')
                    ->asIssue()
                    ->withTitle('Image was not regenerated')
                    ->addToBody($e->getMessage())
                    ->defer();
            }

            ee()->functions->redirect(ee('CP/URL')->make('addons/settings/socialposter/history/' . $id));
        }

        if (ee('Request')->post('save_post')) {
            $generator->update($id, [
                'title' => ee()->input->post('title', true),
                'prompt' => ee()->input->post('prompt', true),
                'post_text' => ee()->input->post('post_text', false),
                'intro_text' => ee()->input->post('intro_text', false),
                'table_of_contents' => ee()->input->post('table_of_contents', false),
                'keywords' => ee()->input->post('keywords', true),
                'category' => ee()->input->post('category', true),
                'hashtags' => ee()->input->post('hashtags', true),
                'external_link' => ee()->input->post('external_link', true),
                'internal_link' => ee()->input->post('internal_link', true),
                'recommended_topics' => ee()->input->post('recommended_topics', false),
                'template_id' => ee()->input->post('template_id', true),
            ]);

            ee('CP/Alert')->makeBanner('socialposter-history')
                ->asSuccess()
                ->withTitle('Saved')
                ->addToBody('The generated post was updated.')
                ->defer();

            ee()->functions->redirect(ee('CP/URL')->make('addons/settings/socialposter/history/' . $id));
        }

        if (ee('Request')->post('delete_id')) {
            $generator->delete((int) ee()->input->post('delete_id'));

            ee('CP/Alert')->makeBanner('socialposter-history')
                ->asSuccess()
                ->withTitle('Deleted')
                ->addToBody('The generated post was deleted.')
                ->defer();

            ee()->functions->redirect(ee('CP/URL')->make('addons/settings/socialposter/history'));
        }

        if ($id > 0) {
            $post = $generator->find($id);
            if (! $post) {
                ee('CP/Alert')->makeBanner('socialposter-history')
                    ->asIssue()
                    ->withTitle('Not found')
                    ->addToBody('That generated post does not exist.')
                    ->defer();
                ee()->functions->redirect(ee('CP/URL')->make('addons/settings/socialposter/history'));
            }

            $this->setBody('History', [
                'mode' => 'edit',
                'post' => $post,
                'seo_score' => ee('socialposter:seoScorer')->score($post),
                'rows' => [],
                'index_url' => ee('CP/URL')->make('addons/settings/socialposter')->compile(),
                'settings_url' => ee('CP/URL')->make('addons/settings/socialposter/settings')->compile(),
                'history_url' => ee('CP/URL')->make('addons/settings/socialposter/history')->compile(),
                'save_url' => ee('CP/URL')->make('addons/settings/socialposter/history/' . $id)->compile(),
                'template_options' => ee('socialposter:templates')->options(),
            ]);

            return $this;
        }

        $this->setBody('History', [
            'mode' => 'list',
            'rows' => ee('socialposter:seoScorer')->scoreMany($generator->latest(50)),
            'index_url' => ee('CP/URL')->make('addons/settings/socialposter')->compile(),
            'settings_url' => ee('CP/URL')->make('addons/settings/socialposter/settings')->compile(),
            'history_url' => ee('CP/URL')->make('addons/settings/socialposter/history')->compile(),
            'template_options' => ee('socialposter:templates')->options(),
        ]);

        return $this;
    }
}
