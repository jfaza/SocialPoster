<?php

namespace JavidFazaeli\SocialPoster\ControlPanel\Routes\Publish;

use ExpressionEngine\Service\Addon\Controllers\Mcp\AbstractRoute;
use JavidFazaeli\SocialPoster\ControlPanel\Routes\BuildsRouteContext;
use JavidFazaeli\SocialPoster\ControlPanel\Routes\LoadsStyle;

class Facebook extends AbstractRoute
{
    use LoadsStyle;
    use BuildsRouteContext;

    protected $route_path = 'publish/facebook';
    protected $cp_page_title = 'Publish to Facebook';

    public function process($id = false)
    {
        $this->addBreadcrumb('index', 'SocialPoster');
        $this->addBreadcrumb('facebook', 'Facebook');
        $this->addBreadcrumb('publish/facebook', 'Publish');
        $this->loadStyle();

        $publisher      = ee('socialposter:publisher');
        $generator      = ee('socialposter:generator');
        $coreUrls = $this->coreRouteContext();
        $facebookUrls = $this->facebookRouteContext();
        $publishUrl = $facebookUrls['publish_facebook_url'];

        if (ee('Request')->isPost() && ee()->input->post('publish_to_facebook')) {
            $generationId = (int) ee()->input->post('generation_id');
            if ($generationId < 1) {
                ee('CP/Alert')->makeBanner('socialposter-publish-facebook')
                    ->asIssue()
                    ->withTitle('No post selected')
                    ->addToBody('Please select a generated post to publish.')
                    ->defer();
            } else {
                try {
                    $result = $publisher->publishToFacebook($generationId);
                    ee('CP/Alert')->makeBanner('socialposter-publish-facebook')
                        ->asSuccess()
                        ->withTitle('Published to Facebook')
                        ->addToBody('Post ID: ' . $result['post_id'])
                        ->defer();
                } catch (\Throwable $e) {
                    ee('CP/Alert')->makeBanner('socialposter-publish-facebook')
                        ->asIssue()
                        ->withTitle('Facebook publish failed')
                        ->addToBody($e->getMessage())
                        ->defer();
                }
            }

            ee()->functions->redirect($publishUrl);
        }

        $templateMeta = ee('socialposter:templates')->metaById();
        $posts = array_values(array_filter(
            $generator->latest(50),
            function (array $row) use ($templateMeta) {
                if ((string) ($row['source'] ?? '') === 'news') {
                    return false;
                }

                $template = $templateMeta[(int) ($row['template_id'] ?? 0)] ?? null;
                if (! $template) {
                    return false;
                }

                $platform = strtolower(trim((string) ($template['platform'] ?? '')));
                return $platform === 'facebook';
            }
        ));

        if (! empty($posts) && ee()->db->table_exists('socialposter_published_facebook')) {
            $genIds = array_column($posts, 'id');
            $publishedGenIds = array_column(
                ee()->db->select('generation_id')->where_in('generation_id', $genIds)->get('socialposter_published_facebook')->result_array(),
                'generation_id'
            );
            $publishedGenIds = array_map('intval', $publishedGenIds);
            $posts = array_values(array_filter($posts, fn($p) => ! in_array((int) $p['id'], $publishedGenIds, true)));
        }

        $this->setBody('PublishFacebook', [
            'publish_url'      => $publishUrl,
            'history_url'      => $coreUrls['history_url'],
            'generate_url'     => $coreUrls['index_url'],
            'settings_url'     => $this->cpUrl('facebook'),
            'publish_blog_url' => $coreUrls['publish_url'],
            'published_url'    => $coreUrls['published_url'],
            'facebook_posts'   => $posts,
        ]);

        return $this;
    }
}
