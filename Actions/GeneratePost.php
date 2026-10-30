<?php

namespace JavidFazaeli\SocialPoster\Actions;

use ExpressionEngine\Service\Addon\Controllers\Action\AbstractRoute;

class GeneratePost extends AbstractRoute
{
    public function process()
    {
        if (! ee('Request')->isPost()) {
            return $this->json(['success' => false, 'message' => 'POST required.'], 405);
        }

        if ((int) ee()->session->userdata('member_id') < 1 || ! ee('Permission')->has('can_access_cp')) {
            return $this->json(['success' => false, 'message' => 'Control Panel access required.'], 403);
        }

        if (ee()->input->post('carousel_assist_mode', true)) {
            return $this->handleCarouselAssist();
        }

        if (ee()->input->post('carousel_rewrite_mode', true)) {
            return $this->handleCarouselRewrite();
        }

        if (ee()->input->post('carousel_template_mode', true)) {
            return $this->handleCarouselTemplate();
        }

        if (ee()->input->post('carousel_mode', true)) {
            return $this->handleCarouselGenerate();
        }

        if (ee()->input->post('fetch_news', true)) {
            return $this->handleFetchNews();
        }

        if (ee()->input->post('hot_news', true)) {
            return $this->handleHotNews();
        }

        if (ee()->input->post('news_mode', true)) {
            return $this->handleNewsPost();
        }

        if (ee()->input->post('single_assist_mode', true)) {
            return $this->handleSingleAssist();
        }

        if (ee()->input->post('single_image_mode', true)) {
            return $this->handleSingleImage();
        }

        if (ee()->input->post('carousel_regen', true)) {
            return $this->handleCarouselRegenerate();
        }

        if (ee()->input->post('slide_image_mode', true)) {
            return $this->handleSlideImage();
        }

        if (ee()->input->post('plan_mode', true)) {
            return $this->handlePlan();
        }

        return $this->handleGeneralGenerate();
    }

    private function handleCarouselAssist()
    {
        $sourceId = (int) ee()->input->post('source_id');
        $tone     = trim((string) ee()->input->post('tone', true));
        $goal     = trim((string) ee()->input->post('goal', true));
        $audience = trim((string) ee()->input->post('audience', true));
        $template = trim((string) ee()->input->post('carousel_template', true)) ?: 'minimal-list';
        $style    = trim((string) ee()->input->post('style', true)) ?: 'base';
        $slides   = (int) ee()->input->post('slides', true) ?: 6;

        if ($sourceId < 1) {
            return $this->json(['success' => false, 'message' => 'Please select a news source.'], 422);
        }

        try {
            $source   = ee('socialposter:newsFeed')->find($sourceId);
            $articles = ee('socialposter:newsFeed')->fetchArticles($sourceId, 8);
            $result   = ee('socialposter:carousel')->assistCarousel($articles, [
                'source_name' => is_array($source) ? (string) ($source['name'] ?? '') : '',
                'tone'        => $tone,
                'goal'        => $goal,
                'audience'    => $audience,
                'template'    => $template,
                'style'       => $style,
                'slides'      => $slides,
            ]);
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }

        return $this->json(['success' => true, 'result' => $result], 200);
    }

    private function handleCarouselRewrite()
    {
        $carouselId    = (int) ee()->input->post('carousel_id');
        $topic         = trim((string) ee()->input->post('topic', true));
        $template      = trim((string) ee()->input->post('carousel_template', true)) ?: 'minimal-list';
        $style         = trim((string) ee()->input->post('style', true)) ?: 'base';
        $audience      = trim((string) ee()->input->post('audience', true));
        $contentPrompt = trim((string) ee()->input->post('content_prompt', false));
        $designPrompt  = trim((string) ee()->input->post('design_prompt', false));
        $slidesRaw     = ee()->input->post('slides', true);
        $slides        = $slidesRaw !== false && $slidesRaw !== '' ? (int) $slidesRaw : null;

        if ($carouselId < 1) {
            return $this->json(['success' => false, 'message' => 'Carousel not found.'], 422);
        }
        if ($topic === '') {
            return $this->json(['success' => false, 'message' => 'Please enter a topic.'], 422);
        }

        try {
            $result = ee('socialposter:carousel')->rewrite($carouselId, $topic, $template, $style, [
                'audience'       => $audience,
                'content_prompt' => $contentPrompt,
                'design_prompt'  => $designPrompt,
                'slides'         => $slides,
            ]);
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }

        return $this->json(['success' => true, 'result' => $result], 200);
    }

    private function handleCarouselTemplate()
    {
        $name = trim((string) ee()->input->post('template_name', true));
        $instructions = trim((string) ee()->input->post('template_instructions', false));

        if ($name === '') {
            return $this->json(['success' => false, 'message' => 'Please enter a template name.'], 422);
        }

        try {
            $result = ee('socialposter:carousel')->generateVisualTemplate($name, $instructions);
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }

        return $this->json(['success' => true, 'result' => $result], 200);
    }

    private function handleCarouselGenerate()
    {
        $topic         = trim((string) ee()->input->post('topic', true));
        $template      = trim((string) ee()->input->post('carousel_template', true)) ?: 'minimal-list';
        $style         = trim((string) ee()->input->post('style', true)) ?: 'base';
        $audience      = trim((string) ee()->input->post('audience', true));
        $contentPrompt = trim((string) ee()->input->post('content_prompt', false));
        $designPrompt  = trim((string) ee()->input->post('design_prompt', false));
        $slidesRaw     = ee()->input->post('slides', true);
        $slides        = $slidesRaw !== false && $slidesRaw !== '' ? (int) $slidesRaw : null;

        if ($topic === '') {
            return $this->json(['success' => false, 'message' => 'Please enter a topic.'], 422);
        }

        try {
            $result = ee('socialposter:carousel')->generate($topic, $template, $style, [
                'audience'       => $audience,
                'content_prompt' => $contentPrompt,
                'design_prompt'  => $designPrompt,
                'slides'         => $slides,
            ]);
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }

        return $this->json(['success' => true, 'result' => $result], 200);
    }

    private function handleFetchNews()
    {
        $sourceId = (int) ee()->input->post('source_id');
        if ($sourceId < 1) {
            return $this->json(['success' => false, 'message' => 'Please select a source.'], 422);
        }

        try {
            $articles = ee('socialposter:newsFeed')->fetchArticles($sourceId);
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }

        return $this->json(['success' => true, 'articles' => $articles], 200);
    }

    private function handleHotNews()
    {
        $refresh = (bool) ee()->input->post('refresh', true);

        try {
            if ($refresh) {
                $result = ee('socialposter:newsFeed')->refreshHotArticles();
                return $this->json(['success' => true] + $result, 200);
            }

            return $this->json([
                'success' => true,
                'articles' => ee('socialposter:newsFeed')->hotArticles(20),
                'errors' => [],
                'meta' => ee('socialposter:newsFeed')->hotCacheMeta(),
            ], 200);
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function handleNewsPost()
    {
        $articleTitle    = trim((string) ee()->input->post('article_title', true));
        $articleUrl      = trim((string) ee()->input->post('article_url', true));
        $articleExcerpt  = trim((string) ee()->input->post('article_excerpt', true));
        $articleImageUrl = trim((string) ee()->input->post('article_image_url', true));
        $captionHint     = trim((string) ee()->input->post('caption_hint', false));
        $imageChoice     = trim((string) ee()->input->post('image_choice', true)) ?: 'none';
        $imageHint       = trim((string) ee()->input->post('image_hint', false));

        if ($articleTitle === '') {
            return $this->json(['success' => false, 'message' => 'Please select a news article.'], 422);
        }

        try {
            $result = ee('socialposter:instagramGenerator')->generateNewsPost([
                'title'     => $articleTitle,
                'url'       => $articleUrl,
                'excerpt'   => $articleExcerpt,
                'image_url' => $articleImageUrl,
            ], [
                'caption_hint' => $captionHint,
                'image_choice' => $imageChoice,
                'image_hint'   => $imageHint,
            ]);
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }

        return $this->json(['success' => true, 'result' => $result], 200);
    }

    private function handleSingleAssist()
    {
        $sourceId = (int) ee()->input->post('source_id');
        $tone     = trim((string) ee()->input->post('tone', true));
        $goal     = trim((string) ee()->input->post('goal', true));
        $audience = trim((string) ee()->input->post('audience', true));

        if ($sourceId < 1) {
            return $this->json(['success' => false, 'message' => 'Please select a news source.'], 422);
        }

        try {
            $source   = ee('socialposter:newsFeed')->find($sourceId);
            $articles = ee('socialposter:newsFeed')->fetchArticles($sourceId, 8);
            $result   = ee('socialposter:instagramGenerator')->assistSingleImagePost($articles, [
                'source_name' => is_array($source) ? (string) ($source['name'] ?? '') : '',
                'tone'        => $tone,
                'goal'        => $goal,
                'audience'    => $audience,
            ] + $this->duplicateGuardMeta(ee('socialposter:instagramGenerator'), [
                'source' => 'instagram',
                'limit' => 18,
                'max_topic_attempts' => 3,
            ]));
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }

        return $this->json(['success' => true, 'result' => $result], 200);
    }

    private function handleSingleImage()
    {
        $topic        = trim((string) ee()->input->post('topic', true));
        $captionHint  = trim((string) ee()->input->post('caption_prompt', false));
        $imageHint    = trim((string) ee()->input->post('image_prompt_hint', false));
        $imageMode    = trim((string) ee()->input->post('image_prompt_mode', true)) ?: 'combined';

        if ($topic === '') {
            return $this->json(['success' => false, 'message' => 'Please enter a topic.'], 422);
        }

        try {
            $useInstagram = (bool) ee()->input->post('instagram_source', true);
            $gen    = $useInstagram ? ee('socialposter:instagramGenerator') : ee('socialposter:generator');
            $source = $useInstagram ? 'instagram' : 'manual';
            $result = $gen->generateSingleImagePost($topic, [
                'caption_prompt' => $captionHint,
                'image_prompt'   => $imageHint,
                'image_prompt_mode' => $imageMode,
                'source'         => $source,
            ] + $this->duplicateGuardMeta($gen, [
                'source' => $source,
                'limit' => 18,
                'max_topic_attempts' => 1,
            ]));
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }

        return $this->json(['success' => true, 'result' => $result], 200);
    }

    private function handleCarouselRegenerate()
    {
        $carouselId = (int) ee()->input->post('carousel_id');
        $slideIndex = (int) ee()->input->post('slide_index');
        $topic      = trim((string) ee()->input->post('topic', true));
        $template   = trim((string) ee()->input->post('carousel_template', true)) ?: 'minimal-list';
        $style      = trim((string) ee()->input->post('style', true)) ?: 'base';
        $slideType  = trim((string) ee()->input->post('slide_type', true)) ?: 'content';

        try {
            $result = ee('socialposter:carousel')->regenerateSlide(
                $carouselId,
                $slideIndex,
                $slideType,
                $topic,
                $template,
                $style
            );
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }

        return $this->json(['success' => true, 'result' => $result], 200);
    }

    private function handleSlideImage()
    {
        $generationId = (int) ee()->input->post('generation_id');
        $slideIndex   = (int) ee()->input->post('slide_index');
        $slideData    = json_decode((string) ee()->input->post('slide_data', false), true);

        if (! is_array($slideData)) {
            return $this->json(['success' => false, 'message' => 'Invalid slide data.'], 422);
        }

        try {
            $result = ee('socialposter:generator')->generateSingleSlideImage($generationId, $slideIndex, $slideData);
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }

        return $this->json(['success' => true, 'result' => $result], 200);
    }

    private function handlePlan()
    {
        try {
            $result = ee('socialposter:generator')->plan((string) ee()->input->post('plan_mode', true), [
                'template_id' => (int) ee()->input->post('template_id'),
                'title' => ee()->input->post('title', true),
                'brief' => ee()->input->post('brief', false),
                'keywords' => ee()->input->post('keywords', true),
                'prompt' => ee()->input->post('prompt', false),
                'image_plan' => ee()->input->post('image_plan', false),
            ]);
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }

        return $this->json(['success' => true, 'result' => $result], 200);
    }

    private function handleGeneralGenerate()
    {
        $prompt = trim((string) ee()->input->post('prompt', true));
        if ($prompt === '') {
            return $this->json(['success' => false, 'message' => 'Please enter a prompt.'], 422);
        }

        try {
            $templateId = (int) ee()->input->post('template_id');
            $gen = $this->generatorForTemplate($templateId);
            $result = $gen->generate($prompt, [
                'template_id'       => $templateId,
                'image_brief'       => ee()->input->post('image_brief', false),
                'skip_slide_images' => true,
            ] + $this->duplicateGuardMeta($gen, [
                'template_id' => $templateId,
                'limit' => 18,
                'max_topic_attempts' => 3,
            ]));
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }

        return $this->json(['success' => true, 'result' => $result], 200);
    }

    private function generatorForTemplate(int $templateId)
    {
        if ($templateId < 1) {
            return ee('socialposter:generator');
        }
        $template = ee('socialposter:templates')->find($templateId);
        if (! $template) {
            return ee('socialposter:generator');
        }
        $platform = strtolower(trim((string) ($template['platform'] ?? '')));
        return $platform === 'instagram' ? ee('socialposter:instagramGenerator') : ee('socialposter:generator');
    }

    private function duplicateGuardMeta($generator, array $options = []): array
    {
        if (! is_object($generator) || ! method_exists($generator, 'recentGenerationMemory')) {
            return [];
        }

        $recentGenerations = $generator->recentGenerationMemory([
            'template_id' => (int) ($options['template_id'] ?? 0),
            'source' => (string) ($options['source'] ?? ''),
            'limit' => (int) ($options['limit'] ?? 18),
        ]);

        if (! $recentGenerations) {
            return [];
        }

        return [
            'recent_generations' => $recentGenerations,
            'max_topic_attempts' => max(1, (int) ($options['max_topic_attempts'] ?? 1)),
        ];
    }

    private function json(array $payload, int $status)
    {
        ee()->output->set_status_header($status);
        ee()->output->set_header('Content-Type: application/json; charset=UTF-8');
        ee()->output->set_output(json_encode($payload));
        return;
    }
}
