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

        if (ee()->input->post('carousel_mode', true)) {
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

        if (ee()->input->post('fetch_news', true)) {
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

        if (ee()->input->post('news_mode', true)) {
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

        if (ee()->input->post('single_image_mode', true)) {
            $topic        = trim((string) ee()->input->post('topic', true));
            $captionHint  = trim((string) ee()->input->post('caption_prompt', false));
            $imageHint    = trim((string) ee()->input->post('image_prompt_hint', false));

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
                    'source'         => $source,
                ]);
            } catch (\Throwable $e) {
                return $this->json(['success' => false, 'message' => $e->getMessage()], 500);
            }

            return $this->json(['success' => true, 'result' => $result], 200);
        }

        if (ee()->input->post('carousel_regen', true)) {
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

        if (ee()->input->post('slide_image_mode', true)) {
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

        if (ee()->input->post('plan_mode', true)) {
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
            ]);
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

    private function json(array $payload, int $status)
    {
        ee()->output->set_status_header($status);
        ee()->output->set_header('Content-Type: application/json; charset=UTF-8');
        ee()->output->set_output(json_encode($payload));
        return;
    }
}
