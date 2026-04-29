<?php

namespace JavidFazaeli\SocialPoster\ControlPanel\Routes;

use ExpressionEngine\Service\Addon\Controllers\Mcp\AbstractRoute;

class Published extends AbstractRoute
{
    use LoadsStyle;

    protected $route_path = 'published';
    protected $cp_page_title = 'SocialPoster Published';

    public function process($id = false)
    {
        $this->addBreadcrumb('index', 'SocialPoster');
        $this->addBreadcrumb('published', 'Published');
        $this->loadStyle();

        $publisher = ee('socialposter:publisher');

        if (ee('Request')->post('delete_blog')) {
            try {
                $publisher->deletePublishedBlog((int) ee()->input->post('published_blog_id'));

                ee('CP/Alert')->makeBanner('socialposter-published')
                    ->asSuccess()
                    ->withTitle('Deleted blog entry')
                    ->addToBody('The generated blog entry was deleted.')
                    ->now();
            } catch (\Throwable $e) {
                ee('CP/Alert')->makeBanner('socialposter-published')
                    ->asIssue()
                    ->withTitle('Blog delete failed')
                    ->addToBody($e->getMessage())
                    ->now();
            }
        }

        $this->setBody('Published', [
            'published_url'        => ee('CP/URL')->make('addons/settings/socialposter/published')->compile(),
            'publish_url'          => ee('CP/URL')->make('addons/settings/socialposter/publish')->compile(),
            'publish_instagram_url'=> ee('CP/URL')->make('addons/settings/socialposter/publish/instagram')->compile(),
            'history_url'          => ee('CP/URL')->make('addons/settings/socialposter/history')->compile(),
            'instagram_history_url'=> ee('CP/URL')->make('addons/settings/socialposter/instagram/history')->compile(),
            'index_url'            => ee('CP/URL')->make('addons/settings/socialposter')->compile(),
            'published_blogs'      => $publisher->publishedBlogs(50),
            'published_instagrams' => $publisher->publishedInstagrams(50),
        ]);

        return $this;
    }
}
