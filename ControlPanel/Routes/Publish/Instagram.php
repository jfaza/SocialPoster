<?php

namespace JavidFazaeli\SocialPoster\ControlPanel\Routes\Publish;

use ExpressionEngine\Service\Addon\Controllers\Mcp\AbstractRoute;
use JavidFazaeli\SocialPoster\ControlPanel\Routes\BuildsRouteContext;
use JavidFazaeli\SocialPoster\ControlPanel\Routes\LoadsStyle;

class Instagram extends AbstractRoute
{
    use LoadsStyle;
    use BuildsRouteContext;

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
        $coreUrls = $this->coreRouteContext();
        $instagramUrls = $this->instagramRouteContext();
        $publishUrl = $instagramUrls['publish_instagram_url'];

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
            'publish_blog_url'      => $coreUrls['publish_url'],
            'instagram_history_url' => $instagramUrls['instagram_history_url'],
            'instagram_create_url'  => $instagramUrls['instagram_create_url'],
            'instagram_settings_url'=> $instagramUrls['instagram_settings_url'],
            'instagram_carousel_url'=> $instagramUrls['instagram_carousel_url'],
            'general_url'           => $instagramUrls['general_url'],
            'instagram_posts'       => $instagramPosts,
        ]);

        return $this;
    }
}
