<?php

namespace JavidFazaeli\SocialPoster\ControlPanel\Routes;

use ExpressionEngine\Service\Addon\Controllers\Mcp\AbstractRoute;

class Published extends AbstractRoute
{
    use LoadsStyle;
    use BuildsRouteContext;

    protected $route_path = 'published';
    protected $cp_page_title = 'SocialPoster Published';

    public function process($id = false)
    {
        $this->addBreadcrumb('index', 'SocialPoster');
        $this->addBreadcrumb('published', 'Published');
        $this->loadStyle();

        $publisher = ee('socialposter:publisher');
        $generator = ee('socialposter:generator');
        $templates = ee('socialposter:templates');
        $coreUrls = $this->coreRouteContext();
        $publishUrls = $this->publishRouteContext();
        $instagramUrls = $this->instagramRouteContext();

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

        $publishedBlogs = $publisher->publishedBlogs(50);
        $publishedInstagrams = $publisher->publishedInstagrams(50);
        $publishedFacebooks = $publisher->publishedFacebooks(50);
        $generationIds = [];

        foreach ([$publishedBlogs, $publishedInstagrams, $publishedFacebooks] as $rows) {
            foreach ($rows as $row) {
                $generationId = (int) ($row['generation_id'] ?? 0);
                if ($generationId > 0) {
                    $generationIds[$generationId] = $generationId;
                }
            }
        }

        $generationMap = $this->generationMap(array_values($generationIds), $generator);
        $templateMeta = $templates->metaById();
        $publishedStats = $this->publishedStats($publishedBlogs, $publishedInstagrams, $publishedFacebooks);

        $this->setBody('Published', [
            'published_url'        => $coreUrls['published_url'],
            'publish_url'          => $coreUrls['publish_url'],
            'publish_instagram_url'=> $publishUrls['publish_instagram_url'],
            'publish_facebook_url' => $publishUrls['publish_facebook_url'],
            'history_url'          => $coreUrls['history_url'],
            'instagram_history_url'=> $instagramUrls['instagram_history_url'],
            'index_url'            => $coreUrls['index_url'],
            'published_blogs'      => $publishedBlogs,
            'published_instagrams' => $publishedInstagrams,
            'published_facebooks'  => $publishedFacebooks,
            'generation_map'       => $generationMap,
            'template_meta'        => $templateMeta,
            'published_stats'      => $publishedStats,
        ]);

        return $this;
    }

    private function generationMap(array $generationIds, $generator): array
    {
        $generationIds = array_values(array_filter(array_map('intval', $generationIds)));
        if (! $generationIds) {
            return [];
        }

        $records = [];
        foreach ($generationIds as $generationId) {
            $record = $generator->find($generationId);
            if ($record) {
                $records[$generationId] = $record;
            }
        }

        return $records;
    }

    private function publishedStats(array $blogs, array $instagrams, array $facebooks): array
    {
        return [
            'blogs' => count($blogs),
            'instagrams' => count($instagrams),
            'facebooks' => count($facebooks),
            'total' => count($blogs) + count($instagrams) + count($facebooks),
        ];
    }
}
