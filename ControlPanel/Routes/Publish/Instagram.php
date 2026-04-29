<?php

namespace JavidFazaeli\SocialPoster\ControlPanel\Routes\Publish;

use ExpressionEngine\Service\Addon\Controllers\Mcp\AbstractRoute;
use JavidFazaeli\SocialPoster\ControlPanel\Routes\LoadsStyle;

class Instagram extends AbstractRoute
{
    use LoadsStyle;

    protected $route_path = 'publish/instagram';
    protected $cp_page_title = 'Publish to Instagram';

    public function process($id = false)
    {
        $this->addBreadcrumb('index', 'SocialPoster');
        $this->addBreadcrumb('instagram', 'Instagram');
        $this->addBreadcrumb('publish/instagram', 'Publish');
        $this->loadStyle();

        $publisher          = ee('socialposter:publisher');
        $instagramGen       = ee('socialposter:instagramGenerator');
        $publishUrl         = ee('CP/URL')->make('addons/settings/socialposter/publish/instagram')->compile();
        $publishBlogUrl     = ee('CP/URL')->make('addons/settings/socialposter/publish')->compile();
        $instagramHistUrl   = ee('CP/URL')->make('addons/settings/socialposter/instagram/history')->compile();
        $instagramCreateUrl = ee('CP/URL')->make('addons/settings/socialposter/instagram/create')->compile();

        if (ee('Request')->isPost() && ee()->input->post('delete_instagram_post')) {
            $generationId = (int) ee()->input->post('generation_id');
            if ($generationId < 1) {
                ee('CP/Alert')->makeBanner('socialposter-delete-instagram')
                    ->asIssue()
                    ->withTitle('No post selected')
                    ->addToBody('Please select a post to delete.')
                    ->defer();
            } else {
                try {
                    $instagramGen->delete($generationId);
                    ee('CP/Alert')->makeBanner('socialposter-delete-instagram')
                        ->asSuccess()
                        ->withTitle('Post deleted')
                        ->addToBody('The Instagram post and its generated image were removed.')
                        ->defer();
                } catch (\Throwable $e) {
                    ee('CP/Alert')->makeBanner('socialposter-delete-instagram')
                        ->asIssue()
                        ->withTitle('Could not delete post')
                        ->addToBody($e->getMessage())
                        ->defer();
                }
            }

            ee()->functions->redirect($publishUrl);
        }

        if (ee('Request')->isPost() && ee()->input->post('publish_to_instagram')) {
            $generationId = (int) ee()->input->post('generation_id');
            if ($generationId < 1) {
                ee('CP/Alert')->makeBanner('socialposter-publish-instagram')
                    ->asIssue()
                    ->withTitle('No post selected')
                    ->addToBody('Please select an Instagram post to publish.')
                    ->defer();
            } else {
                try {
                    $publisher->publishToInstagram($generationId);
                    ee('CP/Alert')->makeBanner('socialposter-publish-instagram')
                        ->asSuccess()
                        ->withTitle('Published to Instagram')
                        ->addToBody('The post was published to your Instagram account.')
                        ->defer();
                } catch (\Throwable $e) {
                    ee('CP/Alert')->makeBanner('socialposter-publish-instagram')
                        ->asIssue()
                        ->withTitle('Instagram publish failed')
                        ->addToBody($e->getMessage())
                        ->defer();
                }
            }

            ee()->functions->redirect($publishUrl);
        }

        $instagramPosts = $instagramGen->latest(50);

        if (! empty($instagramPosts) && ee()->db->table_exists('socialposter_published_instagram')) {
            $genIds = array_column($instagramPosts, 'id');
            $publishedGenIds = array_column(
                ee()->db->select('generation_id')->where_in('generation_id', $genIds)->get('socialposter_published_instagram')->result_array(),
                'generation_id'
            );
            $publishedGenIds = array_map('intval', $publishedGenIds);
            $instagramPosts = array_values(array_filter($instagramPosts, fn($p) => ! in_array((int) $p['id'], $publishedGenIds, true)));
        }

        $this->setBody('PublishInstagram', [
            'publish_url'           => $publishUrl,
            'publish_blog_url'      => $publishBlogUrl,
            'instagram_history_url' => $instagramHistUrl,
            'instagram_create_url'  => $instagramCreateUrl,
            'instagram_posts'       => $instagramPosts,
        ]);

        return $this;
    }
}
