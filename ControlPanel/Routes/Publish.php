<?php

namespace JavidFazaeli\SocialPoster\ControlPanel\Routes;

use ExpressionEngine\Service\Addon\Controllers\Mcp\AbstractRoute;

class Publish extends AbstractRoute
{
    use LoadsStyle;

    protected $route_path = 'publish';
    protected $cp_page_title = 'SocialPoster Publish';

    public function process($id = false)
    {
        $this->addBreadcrumb('index', 'SocialPoster');
        $this->addBreadcrumb('publish', 'Publish');
        $this->loadStyle();

        $publisher = ee('socialposter:publisher');
        $result = null;

        if (ee('Request')->post('publish_blog')) {
            try {
                $result = $publisher->publishToBlog((int) ee()->input->post('generation_id'), [
                    'title' => ee()->input->post('title', true),
                    'status' => ee()->input->post('status', true),
                    'category_id' => ee()->input->post('category_id', true),
                    'include_image' => ee()->input->post('include_image', true),
                ]);

                ee('CP/Alert')->makeBanner('socialposter-publish')
                    ->asSuccess()
                    ->withTitle('Published to blog')
                    ->addToBody('Entry #' . $result['entry_id'] . ' was created.')
                    ->now();
            } catch (\Throwable $e) {
                ee('CP/Alert')->makeBanner('socialposter-publish')
                    ->asIssue()
                    ->withTitle('Blog publish failed')
                    ->addToBody($e->getMessage())
                    ->now();
            }
        }

        if (ee('Request')->post('delete_blog')) {
            try {
                $publisher->deletePublishedBlog((int) ee()->input->post('published_blog_id'));

                ee('CP/Alert')->makeBanner('socialposter-publish')
                    ->asSuccess()
                    ->withTitle('Deleted blog entry')
                    ->addToBody('The generated blog entry was deleted.')
                    ->now();
            } catch (\Throwable $e) {
                ee('CP/Alert')->makeBanner('socialposter-publish')
                    ->asIssue()
                    ->withTitle('Blog delete failed')
                    ->addToBody($e->getMessage())
                    ->now();
            }
        }

        $this->setBody('Publish', [
            'publish_url' => ee('CP/URL')->make('addons/settings/socialposter/publish')->compile(),
            'history_url' => ee('CP/URL')->make('addons/settings/socialposter/history')->compile(),
            'index_url' => ee('CP/URL')->make('addons/settings/socialposter')->compile(),
            'targets' => $publisher->targets(),
            'status_options' => $publisher->statusOptions(),
            'category_options' => $publisher->categoryOptions(),
            'rows' => ee('socialposter:generator')->latest(50),
            'published_blogs' => $publisher->publishedBlogs(50),
            'result' => $result,
        ]);

        return $this;
    }
}
