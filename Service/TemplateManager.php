<?php

namespace JavidFazaeli\SocialPoster\Service;

class TemplateManager
{
    private const TEMPLATE_SCAFFOLD_CACHE_VERSION = 'v1';
    private const INSTAGRAM_CAROUSEL_INSTRUCTIONS = 'You are an expert Instagram carousel creator and visual content strategist. Generate a complete Instagram carousel package with 6 to 8 slides minimum. Do not stop at slide 1. Every slide must include both text content and a unique image brief. post_text must be formatted slide by slide using labels such as "Slide 1 - Cover", "Slide 2 - ...", and so on. For each slide, include a heading plus concise bullets or short support lines, then add a short "Visual Direction:" line for that slide. Slide 1 must be a cover with a strong hook of 10 words or fewer and a subtitle if needed. Slides 2 through the next-to-last slide must teach one idea only, use 3 to 5 bullets maximum, and keep each bullet under 10 words. The final slide must reinforce value and include a CTA such as follow, save, comment, or DM. Tone must be clear, sharp, slightly bold, and free of fluff. Design direction should feel premium, modern, minimal, editorial, and AI-tech oriented with clean composition, no people, no logos, no readable text inside images, and strong 1:1 carousel aesthetics. Include one Instagram Caption section at the end with a publish-ready caption and hashtags. table_of_contents should contain slide titles only for internal structure. The system currently generates one image only, so image_brief and image_prompt must describe the main cover / visual system for the carousel, while the per-slide visual directions live inside post_text. Do not write a blog article, long paragraphs, FAQ sections, citations, internal links, external links, recommended topics, or generic advice.';
    private const HOT_TECH_OLD_INSTRUCTIONS = 'Create a timely Website blog article from a supplied or hot technology/developer topic. Cover what changed, why it matters, practical implications for developers and technical buyers, implementation takeaways, risks and tradeoffs, citation/source ideas, semantic keywords, internal link suggestions, external reference ideas, recommended follow-up topics, and a realistic technical workspace image prompt.';
    private const HOT_TECH_NEW_INSTRUCTIONS = 'Write a technically credible blog article for a developer and technical-buyer audience. If the topic is a news-feed item published in the last 3 days, lead with what changed, explain why it matters now, and separate signal from hype. Otherwise, write an evergreen how-to or explainer with no news framing (no "what changed", "now", "new", or "this quarter"). Make the piece useful to someone deciding whether to pay attention, evaluate, or implement: cover the context, who it affects, practical implementation takeaways, engineering and business tradeoffs, risks or limitations, and clear next steps. Prefer concrete examples, version names, product names, and source-backed claims over vague commentary. Include semantic SEO keywords, real and specific sources, and at most one inline link to a related post on this site. Stay within about 10% of the target word count. The image prompt should describe an editorial concept photo: one concrete object, material or physical metaphor from the topic, with no text in the image. Avoid filler, generic futurism, and outdated trend framing.';

    public function fieldOptions(): array
    {
        return [
            'content_type' => [
                'seo_article' => 'SEO Article',
                'social_post' => 'Social Post',
                'cluster' => 'Content Cluster',
                'reddit_answer' => 'Reddit Answer',
                'outreach' => 'Outreach',
                'technical_audit' => 'Technical SEO Audit',
                'newsletter' => 'Newsletter',
                'comparison' => 'Comparison Page',
                'case_study' => 'Case Study',
                'faq' => 'FAQ Page',
            ],
            'platform' => [
                'Website' => 'Website',
                'LinkedIn' => 'LinkedIn',
                'X / Twitter' => 'X / Twitter',
                'Instagram' => 'Instagram',
                'Facebook' => 'Facebook',
                'Reddit' => 'Reddit',
                'Newsletter' => 'Newsletter',
                'Multi-platform' => 'Multi-platform',
            ],
            'generation_profile' => [
                '' => 'Generic / custom behavior',
                'website_seo_article' => 'Website SEO article',
                'hot_tech_dev_article' => 'Hot tech / dev article',
                'howto_tutorial' => 'How-to tutorial',
                'instagram_carousel' => 'Instagram carousel',
                'facebook_short_post' => 'Facebook short post',
            ],
            'length_preset' => [
                'short' => 'Short',
                'medium' => 'Medium',
                'long' => 'Long',
                'outline' => 'Outline',
                'pillar' => 'Pillar',
            ],
            'word_count' => [
                300 => 'Short social/blog brief - 300 words',
                600 => 'Standard post - 600 words',
                900 => 'Detailed article - 900 words',
                1200 => 'SEO article - 1,200 words',
                1800 => 'Long SEO article - 1,800 words',
                2500 => 'Pillar article - 2,500 words',
            ],
            'tone' => [
                'expert, clear, practical' => 'Expert, clear, practical',
                'confident, human, useful' => 'Confident, human, useful',
                'strategic, organized, practical' => 'Strategic, organized, practical',
                'friendly, educational, concise' => 'Friendly, educational, concise',
                'direct, technical, evidence-based' => 'Direct, technical, evidence-based',
                'thoughtful, founder-led, conversational' => 'Founder-led, conversational',
            ],
            'audience' => [
                'business owners, founders, and technical decision makers' => 'Owners, founders, decision makers',
                'founders, marketers, and web teams' => 'Founders, marketers, web teams',
                'site owners planning topical authority' => 'Site owners building topical authority',
                'developers and technical buyers' => 'Developers and technical buyers',
                'local businesses and service providers' => 'Local service businesses',
                'agency clients and marketing managers' => 'Agency clients and marketing managers',
            ],
            'goal' => [
                'rank in Google and LLM answers while converting readers into qualified leads' => 'Rank and convert leads',
                'build authority and start conversations' => 'Build authority and conversations',
                'plan a topical cluster with internal links' => 'Plan topical cluster',
                'answer buyer questions and reduce objections' => 'Answer buyer questions',
                'earn citations, shares, and backlinks' => 'Earn citations and backlinks',
                'drive newsletter or social engagement' => 'Drive engagement',
            ],
            'research_mode' => [
                'cite credible sources and include proof points' => 'Credible citations and proof points',
                'light research with one credible external reference' => 'Light research',
                'SERP-style clustering from supplied topic and common search intent' => 'SERP-style clustering',
                'use only supplied brand/site context' => 'Brand/site context only',
                'technical checklist with issue, impact, and fix framing' => 'Technical audit framing',
                'compare options, pros, cons, and decision criteria' => 'Comparison research',
            ],
            'citation_count' => [
                0 => 'No citations',
                1 => '1 citation',
                2 => '2 citations',
                3 => '3 citations',
                4 => '4 citations',
                6 => '6 citations',
            ],
            'internal_link_count' => [
                0 => 'No internal links',
                1 => '1 internal link',
                2 => '2 internal links',
                3 => '3 internal links',
                5 => '5 internal links',
                8 => '8 internal links',
            ],
            'external_link_count' => [
                0 => 'No external links',
                1 => '1 external link',
                2 => '2 external links',
                3 => '3 external links',
                5 => '5 external links',
            ],
            'schema_type' => [
                'None' => 'None',
                'Article' => 'Article',
                'BlogPosting' => 'BlogPosting',
                'FAQPage' => 'FAQPage',
                'HowTo' => 'HowTo',
                'ItemList' => 'ItemList',
                'Product' => 'Product',
                'Service' => 'Service',
                'Review' => 'Review',
                'QAPage' => 'QAPage',
            ],
            'image_style' => [
                'editorial concept photo' => 'Editorial concept photo (recommended)',
                'realistic editorial photo' => 'Realistic editorial photo',
                'realistic workplace scene' => 'Realistic workplace scene',
                'realistic topic scene' => 'Realistic topic scene',
                'realistic technical workspace' => 'Realistic technical workspace',
                'realistic case-study scene' => 'Realistic case-study scene',
                'realistic portrait-style scene' => 'Realistic portrait-style scene',
                'consistent branded carousel cover' => 'Consistent branded carousel cover',
            ],
            'cta_style' => [
                'soft consultation CTA' => 'Soft consultation CTA',
                'conversation starter' => 'Conversation starter',
                'next-step content planning CTA' => 'Next-step planning CTA',
                'book a call' => 'Book a call',
                'read related article' => 'Read related article',
                'newsletter signup' => 'Newsletter signup',
                'no CTA' => 'No CTA',
            ],
        ];
    }

    public function instagramCarouselInstructions(): string
    {
        return self::INSTAGRAM_CAROUSEL_INSTRUCTIONS;
    }

    public function defaults(): array
    {
        return [
            [
                'title' => 'SEO Article - Long',
                'content_type' => 'seo_article',
                'platform' => 'Website',
                'generation_profile' => 'website_seo_article',
                'length_preset' => 'long',
                'word_count' => 1800,
                'tone' => 'expert, clear, practical',
                'audience' => 'business owners, founders, and technical decision makers',
                'goal' => 'rank in Google and LLM answers while converting readers into qualified leads',
                'research_mode' => 'cite credible sources and include proof points',
                'citation_count' => 4,
                'internal_link_count' => 3,
                'external_link_count' => 3,
                'schema_type' => 'Article',
                'image_style' => 'realistic editorial photo',
                'cta_style' => 'soft consultation CTA',
                'prompt_instructions' => 'Create an SEO/LLM optimized article package with a strong title, search-intent driven intro, table of contents, semantic keyword set, source/citation ideas, internal link suggestions, recommended topics, and a realistic editorial image prompt.',
                'is_default' => 1,
            ],
            [
                'title' => 'Hot Tech & Dev Blog Article',
                'content_type' => 'seo_article',
                'platform' => 'Website',
                'generation_profile' => 'hot_tech_dev_article',
                'length_preset' => 'medium',
                'word_count' => 1200,
                'tone' => 'direct, technical, evidence-based',
                'audience' => 'developers and technical buyers',
                'goal' => 'answer buyer questions and reduce objections',
                'research_mode' => 'cite credible sources and include proof points',
                'citation_count' => 3,
                'internal_link_count' => 2,
                'external_link_count' => 2,
                'schema_type' => 'BlogPosting',
                'image_style' => 'editorial concept photo',
                'cta_style' => 'read related article',
                'prompt_instructions' => self::HOT_TECH_NEW_INSTRUCTIONS,
                'is_default' => 0,
            ],
            [
                'title' => 'How-To Tutorial',
                'content_type' => 'seo_article',
                'platform' => 'Website',
                'generation_profile' => 'howto_tutorial',
                'length_preset' => 'long',
                'word_count' => 1800,
                'tone' => 'direct, technical, evidence-based',
                'audience' => 'developers and technical buyers',
                'goal' => 'answer buyer questions and reduce objections',
                'research_mode' => 'technical checklist with issue, impact, and fix framing',
                'citation_count' => 3,
                'internal_link_count' => 3,
                'external_link_count' => 2,
                'schema_type' => 'HowTo',
                'image_style' => 'editorial concept photo',
                'cta_style' => 'book a call',
                'prompt_instructions' => 'Create a step-by-step how-to content package with prerequisites, process outline, warnings, validation checklist, SEO keywords, HowTo schema ideas, and a realistic technical workspace image prompt.',
                'is_default' => 0,
            ],
            [
                'title' => 'Instagram Carousel',
                'content_type' => 'social_post',
                'platform' => 'Instagram',
                'generation_profile' => 'instagram_carousel',
                'length_preset' => 'medium',
                'word_count' => 600,
                'tone' => 'friendly, educational, concise',
                'audience' => 'local businesses and service providers',
                'goal' => 'drive newsletter or social engagement',
                'research_mode' => 'use only supplied brand/site context',
                'citation_count' => 0,
                'internal_link_count' => 1,
                'external_link_count' => 0,
                'schema_type' => 'None',
                'image_style' => 'consistent branded carousel cover',
                'cta_style' => 'conversation starter',
                'prompt_instructions' => self::INSTAGRAM_CAROUSEL_INSTRUCTIONS,
                'is_default' => 0,
            ],
            [
                'title' => 'Facebook Short Post',
                'content_type' => 'social_post',
                'platform' => 'Facebook',
                'generation_profile' => 'facebook_short_post',
                'length_preset' => 'short',
                'word_count' => 300,
                'tone' => 'confident, human, useful',
                'audience' => 'founders, marketers, and web teams',
                'goal' => 'drive newsletter or social engagement',
                'research_mode' => 'use only supplied brand/site context',
                'citation_count' => 0,
                'internal_link_count' => 0,
                'external_link_count' => 0,
                'schema_type' => 'None',
                'image_style' => 'editorial concept photo',
                'cta_style' => 'conversation starter',
                'prompt_instructions' => 'Create a short Facebook post that feels natural in-feed, easy to skim, and worth reacting to. Lead with a clear hook in the first line, keep the body concise, avoid long article-style explanations, and focus on one practical point, lesson, update, or opinion only. Use short paragraphs or compact line breaks instead of dense blocks. The post should feel human, conversational, and useful, not corporate or overly polished. Include a simple engagement CTA such as a question, opinion prompt, or invitation to share experience. If a link is useful, suggest it lightly instead of making the post feel like a blog summary. Also provide a realistic image prompt that fits a professional Facebook post.',
                'is_default' => 0,
            ],
        ];
    }

    public function all(): array
    {
        if (! ee()->db->table_exists('socialposter_templates')) {
            return $this->defaultsWithIds();
        }

        $this->seedDefaults();

        $this->resetQueryBuilder();
        $templates = ee()->db
            ->order_by('is_default', 'DESC')
            ->order_by('title', 'ASC')
            ->get('socialposter_templates')
            ->result_array();

        return $this->visibleTemplates($templates);
    }

    public function forPage(string $page = 'general'): array
    {
        $page = strtolower(trim($page));

        return array_values(array_filter($this->all(), function (array $template) use ($page): bool {
            $isInstagram = $this->isInstagramCarousel($template);

            if ($page === 'instagram') {
                return $isInstagram;
            }

            return ! $isInstagram;
        }));
    }

    public function options(bool $includeEmpty = true): array
    {
        $options = $includeEmpty ? [0 => 'No template'] : [];

        foreach ($this->all() as $template) {
            $options[(int) $template['id']] = (string) $template['title'];
        }

        return $options;
    }

    public function optionsForPage(string $page = 'general', bool $includeEmpty = true): array
    {
        $options = $includeEmpty ? [0 => 'No template'] : [];

        foreach ($this->forPage($page) as $template) {
            $options[(int) $template['id']] = (string) $template['title'];
        }

        return $options;
    }

    public function metaById(): array
    {
        $meta = [];
        foreach ($this->all() as $template) {
            $meta[(int) $template['id']] = [
                'title' => (string) ($template['title'] ?? ''),
                'platform' => (string) ($template['platform'] ?? ''),
                'content_type' => (string) ($template['content_type'] ?? ''),
            ];
        }

        return $meta;
    }

    public function metaByPage(string $page = 'general'): array
    {
        $meta = [];
        foreach ($this->forPage($page) as $template) {
            $meta[(int) $template['id']] = [
                'title' => (string) ($template['title'] ?? ''),
                'platform' => (string) ($template['platform'] ?? ''),
                'content_type' => (string) ($template['content_type'] ?? ''),
            ];
        }

        return $meta;
    }

    public function starterPresetsById(): array
    {
        $presets = [];
        foreach ($this->all() as $template) {
            $presets[(int) $template['id']] = $this->starterPresetsFor($template);
        }

        return $presets;
    }

    public function starterPresetsByPage(string $page = 'general'): array
    {
        $presets = [];
        foreach ($this->forPage($page) as $template) {
            $presets[(int) $template['id']] = $this->starterPresetsFor($template);
        }

        return $presets;
    }

    public function findByTitle(string $title): ?array
    {
        $title = trim($title);
        if ($title === '') {
            return null;
        }

        foreach ($this->all() as $template) {
            if (trim((string) ($template['title'] ?? '')) === $title) {
                return $template;
            }
        }

        return null;
    }

    public function instagramCarouselTemplate(): ?array
    {
        return $this->findByTitle('Instagram Carousel');
    }

    private function starterPresetsFor(array $template): array
    {
        $title = strtolower((string) ($template['title'] ?? ''));
        $platform = strtolower((string) ($template['platform'] ?? ''));
        $type = (string) ($template['content_type'] ?? '');

        if ($platform === 'instagram' || str_contains($title, 'instagram carousel')) {
            return [
                [
                    'label' => 'AI visibility carousel',
                    'prompt' => "Create an Instagram carousel for business owners, founders, and marketing teams.\n\nTopic: Why traditional SEO is not enough anymore, and how businesses can prepare their website/content to show up in Google, AI search, and answer engines.\n\nStructure it as 7 slides:\n1. Hook/problem\n2. What changed in search\n3. Why AI visibility matters\n4. What businesses usually miss\n5. A simple framework\n6. Quick action checklist\n7. Soft CTA to work with Javid Fazaeli\n\nTone: confident, helpful, concise, not hypey. Include short slide titles, punchy slide copy, a caption, hashtags, and a soft CTA.",
                    'image_brief' => 'Premium dark editorial Instagram carousel cover with charcoal background, muted sage green accents, subtle grid lines, abstract search/AI signal visuals, small glowing nodes connected like a knowledge graph, modern professional tech aesthetic, no people, no logos, no clutter, square 1:1 format.',
                ],
                [
                    'label' => 'Website mistakes carousel',
                    'prompt' => "Create an Instagram carousel for local businesses and service providers.\n\nTopic: 6 website mistakes that make a business harder to find, trust, and contact.\n\nStructure it as 7 slides: hook, mistake 1, mistake 2, mistake 3, mistake 4, mistake 5/6, CTA. Keep each slide short and practical. Include a caption, hashtags, and a question CTA.",
                    'image_brief' => 'Clean premium website audit visual, dark charcoal workspace, subtle sage highlights, realistic laptop/tablet with abstract blurred website interface shapes, checklist mood without readable text, no people, no logos, square 1:1.',
                ],
                [
                    'label' => 'Before/after clarity carousel',
                    'prompt' => "Create an Instagram carousel for founders and marketing teams.\n\nTopic: Before and after: turning vague website copy into clear content that helps buyers and search engines understand your business.\n\nUse 6-7 slides with very concise teaching points, one practical example, caption, hashtags, and a soft CTA for a website/content review.",
                    'image_brief' => 'Minimal premium split-mood composition showing messy abstract content blocks becoming organized structured signals, charcoal background, sage accent lines, realistic glass screen reflections, no readable text, no logos, square 1:1.',
                ],
            ];
        }

        if (str_contains($title, 'linkedin') || $platform === 'linkedin') {
            return [
                [
                    'label' => 'Founder insight',
                    'prompt' => 'Create a LinkedIn authority post about [topic] for founders and marketing teams. Use a practical hook, one clear lesson, one concrete example, and a conversation-starting CTA.',
                    'image_brief' => 'Realistic founder/workspace editorial image, calm professional mood, laptop and notes, natural light, no visible text, no logos.',
                ],
                [
                    'label' => 'Practical lesson',
                    'prompt' => 'Create a concise LinkedIn post about a common mistake businesses make with [topic]. Explain the mistake, why it matters, a better approach, and a soft question CTA.',
                    'image_brief' => 'Clean realistic consulting workspace scene, subtle tech details, neutral professional lighting, no text, no logos.',
                ],
            ];
        }

        if (str_contains($title, 'x thread') || $platform === 'x / twitter') {
            return [
                [
                    'label' => 'Tactical thread',
                    'prompt' => 'Create an X thread about [topic] for founders and marketing teams. Include a strong first post, 6-8 short posts, practical examples, and a final CTA.',
                    'image_brief' => 'Realistic dark tech workspace image with subtle interface reflections, concise social media mood, no text, no logos.',
                ],
            ];
        }

        if (str_contains($title, 'facebook short post') || $platform === 'facebook') {
            return [
                [
                    'label' => 'Quick insight post',
                    'prompt' => 'Create a short Facebook post about [topic] for founders, marketers, and web teams. Lead with a simple hook, make one practical point only, keep it brief and conversational, and end with a question CTA that encourages comments.',
                    'image_brief' => 'Realistic professional workspace image with laptop, notes, and subtle social-media-friendly energy, natural lighting, no readable text, no logos.',
                ],
                [
                    'label' => 'Myth vs reality',
                    'prompt' => 'Create a short Facebook post about a common misconception around [topic]. Open with the myth, correct it in plain language, give one useful takeaway, and end with a comment-starting question.',
                    'image_brief' => 'Clean realistic consulting or tech workspace scene, modern but casual, no text, no logos, friendly professional mood.',
                ],
                [
                    'label' => 'Small update post',
                    'prompt' => 'Create a short Facebook update post about [topic]. Make it feel like a useful observation or update someone would actually stop to read in-feed. Keep it under a few short paragraphs and end with a low-pressure engagement CTA.',
                    'image_brief' => 'Realistic desk or studio scene with subtle professional tools, warm natural light, uncluttered composition, no readable text, no logos.',
                ],
            ];
        }

        if (str_contains($title, 'hot tech') || str_contains($title, 'dev blog')) {
            return [
                [
                    'label' => 'AI/dev tooling trend',
                    'prompt' => 'Create a timely blog article about a hot AI or developer tooling topic. Explain what changed, why developers and technical buyers should care, practical implementation takeaways, risks/tradeoffs, citations, keywords, internal links, and follow-up topics.',
                    'image_brief' => 'Realistic technical workspace with laptop, code editor glow, documentation notes, subtle AI/dev tooling atmosphere, modern professional lighting, no visible text, no logos.',
                ],
                [
                    'label' => 'Web framework change',
                    'prompt' => 'Create a blog article about a recent or emerging web framework change. Cover the context, what changed, migration or implementation guidance, performance/maintainability implications, risks, citations, and a practical CTA.',
                    'image_brief' => 'Realistic developer desk with multiple browser/code windows abstracted on screens, clean modern workspace, subtle technical mood, no readable text, no logos.',
                ],
                [
                    'label' => 'ExpressionEngine dev angle',
                    'prompt' => 'Create a blog article connecting a hot CMS, PHP, or developer workflow topic to ExpressionEngine development. Include practical examples, implementation guidance, risks, source ideas, internal links, and follow-up article topics.',
                    'image_brief' => 'Realistic CMS developer workspace with laptop, notebook, terminal-like screen reflections, calm technical editorial composition, no readable text, no logos.',
                ],
                [
                    'label' => 'AI visibility topic',
                    'prompt' => 'Create a blog article about a hot technical SEO or AI visibility topic for developers and technical buyers. Explain what changed in search/AI answers, implementation takeaways, risks, citations, internal links, and next-step topics.',
                    'image_brief' => 'Realistic technical SEO workspace with laptop, analytics-style screen glow, structured notes, subtle search and AI signal mood, no readable text, no logos.',
                ],
            ];
        }

        if ($type === 'seo_article' || $platform === 'website') {
            return [
                [
                    'label' => 'SEO article',
                    'prompt' => 'Create an SEO article about [topic] for [audience]. Include search intent, practical examples, related internal blog links when relevant, a matching blog category, and a soft consultation CTA.',
                    'image_brief' => 'Realistic editorial blog hero image, professional workspace, natural light, clean composition, no visible text, no logos.',
                ],
                [
                    'label' => 'Buyer guide',
                    'prompt' => 'Create a buyer-focused guide about [topic]. Explain the problem, decision criteria, common mistakes, practical steps, FAQs, and a clear next step for working with Javid Fazaeli.',
                    'image_brief' => 'Premium realistic consulting scene, desk with laptop and planning notes, calm confident mood, no text, no logos.',
                ],
            ];
        }

        return [
            [
                'label' => 'Default starter',
                'prompt' => 'Create a content package about [topic] for [audience]. Keep it practical, clear, and aligned with the selected template. Include a soft CTA and relevant image direction.',
                'image_brief' => 'Realistic professional editorial image, clean composition, modern tech/business mood, no visible text, no logos.',
            ],
        ];
    }

    public function find(int $id): ?array
    {
        if ($id < 1 || ! ee()->db->table_exists('socialposter_templates')) {
            return null;
        }

        $this->resetQueryBuilder();
        $row = ee()->db->where('id', $id)->get('socialposter_templates')->row_array();
        return $row ?: null;
    }

    public function save(array $input, int $id = 0): int
    {
        if (! ee()->db->table_exists('socialposter_templates')) {
            throw new \RuntimeException('SocialPoster template table is missing. Run add-on migrations.');
        }

        $now = ee()->localize->now;
        $row = [
            'site_id' => (int) ee()->config->item('site_id'),
            'title' => trim((string) ($input['title'] ?? '')),
            'content_type' => trim((string) ($input['content_type'] ?? 'social_post')),
            'platform' => trim((string) ($input['platform'] ?? 'Website')),
            'generation_profile' => trim((string) ($input['generation_profile'] ?? '')),
            'length_preset' => trim((string) ($input['length_preset'] ?? 'medium')),
            'word_count' => max(0, (int) ($input['word_count'] ?? 0)),
            'tone' => trim((string) ($input['tone'] ?? '')),
            'audience' => trim((string) ($input['audience'] ?? '')),
            'goal' => trim((string) ($input['goal'] ?? '')),
            'research_mode' => trim((string) ($input['research_mode'] ?? '')),
            'citation_count' => max(0, (int) ($input['citation_count'] ?? 0)),
            'internal_link_count' => max(0, (int) ($input['internal_link_count'] ?? 0)),
            'external_link_count' => max(0, (int) ($input['external_link_count'] ?? 0)),
            'schema_type' => trim((string) ($input['schema_type'] ?? '')),
            'image_style' => trim((string) ($input['image_style'] ?? '')),
            'cta_style' => trim((string) ($input['cta_style'] ?? '')),
            'prompt_instructions' => trim((string) ($input['prompt_instructions'] ?? '')),
            'is_default' => ! empty($input['is_default']) ? 1 : 0,
            'updated_at' => $now,
        ];

        if ($row['title'] === '') {
            throw new \InvalidArgumentException('Template title is required.');
        }

        if ($row['is_default']) {
            ee()->db->update('socialposter_templates', ['is_default' => 0]);
        }

        if ($id > 0 && $this->find($id)) {
            ee()->db->where('id', $id)->update('socialposter_templates', $row);
            $this->clearTemplateScaffoldCache($id);
            return $id;
        }

        $row['created_at'] = $now;
        ee()->db->insert('socialposter_templates', $row);

        $newId = (int) ee()->db->insert_id();
        $this->clearTemplateScaffoldCache($newId);

        return $newId;
    }

    public function delete(int $id): bool
    {
        if ($id < 1 || ! ee()->db->table_exists('socialposter_templates')) {
            return false;
        }

        if (ee()->db->table_exists('socialposter_schedules') && ee()->db->field_exists('template_id', 'socialposter_schedules')) {
            ee()->db->where('template_id', $id)->update('socialposter_schedules', ['template_id' => 0]);
        }
        ee()->db->where('id', $id)->delete('socialposter_templates');
        $this->clearTemplateScaffoldCache($id);

        return ee()->db->affected_rows() > 0;
    }

    public function instructionsFor(int $id): string
    {
        $template = $this->find($id);
        if (! $template) {
            return '';
        }

        $cached = $this->cachedScaffoldFor($template);
        if ($cached !== null) {
            return $cached;
        }

        $lines = [
            'Use this generation template:',
            'Template: ' . $template['title'],
            'Content type: ' . $template['content_type'],
            'Platform: ' . $template['platform'],
            'Behavior profile: ' . $this->generationProfileLabel((string) ($template['generation_profile'] ?? '')),
            'Length preset: ' . $template['length_preset'],
            'Target word count: ' . (int) $template['word_count'],
            'Tone: ' . $template['tone'],
            'Audience: ' . $template['audience'],
            'Goal: ' . $template['goal'],
            'Research mode: ' . $template['research_mode'],
            'Citation count: ' . (int) $template['citation_count'],
            'Internal links: ' . (int) $template['internal_link_count'],
            'External links: ' . (int) $template['external_link_count'],
            'Schema type: ' . $template['schema_type'],
            'Image style: ' . $template['image_style'],
            'CTA style: ' . $template['cta_style'],
            'Instructions: ' . $template['prompt_instructions'],
            $this->profileContractFor($template),
            $this->platformContractFor($template),
            'Content structure: ' . $this->articleStructureFor($template),
            'Image generation requirements: image_prompt must describe a realistic, photorealistic scene. Do not create infographic, diagram, quote-card, vector, illustration, text-heavy, or branded graphic prompts unless the user explicitly asks for visible text.',
            $this->bodyRequirementsFor($template),
            'Internal link requirements: use only relative internal URLs beginning with /, or exact site URLs supplied by the user. Do not invent placeholder domains.',
        ];

        $instructions = implode("\n", array_filter($lines, fn($line) => trim($line) !== ''));
        $this->storeCachedScaffold($template, $instructions);

        return $instructions;
    }

    private function platformContractFor(array $template): string
    {
        if (! $this->isInstagramCarousel($template)) {
            return '';
        }

        return 'Instagram carousel contract: Generate carousel-ready social content, not a blog post. post_text must contain at least 6 slide blocks plus an Instagram Caption block. Slide 1 is the cover hook. Each remaining slide should teach one idea only, stay short enough to fit on a designed Instagram slide, and use concise scannable bullets or support lines. Each slide block should also include one short "Visual Direction:" line for that slide. table_of_contents should contain slide titles only for internal structure. The system currently generates one image, so image_brief and image_prompt must describe a single square carousel cover / visual system, not separate images for every slide.';
    }

    private function profileContractFor(array $template): string
    {
        return match ((string) ($template['generation_profile'] ?? '')) {
            'website_seo_article' => 'Behavior contract: Prioritize search-intent alignment, clear article hierarchy, semantic keyword coverage, citations when available, and publish-ready Markdown body quality.',
            'hot_tech_dev_article' => 'Behavior contract: Treat freshness as part of the value. Focus on what changed, what is signal versus hype, implementation implications, technical credibility, and practical decision support.',
            'howto_tutorial' => 'Behavior contract: Teach in a step-by-step way. Favor prerequisites, ordered actions, warnings, validation steps, and operational clarity over broad commentary.',
            'instagram_carousel' => 'Behavior contract: Optimize for short, slide-native teaching. Keep copy scannable, visual-system aware, and caption-ready rather than article-like.',
            'facebook_short_post' => 'Behavior contract: Optimize for in-feed reading. Keep one clear point, short paragraphs, human tone, and a comment-friendly CTA.',
            default => '',
        };
    }

    private function bodyRequirementsFor(array $template): string
    {
        $type = (string) ($template['content_type'] ?? '');

        if ($this->isInstagramCarousel($template)) {
            return 'Instagram body requirements: post_text must be native carousel content, not a blog article. Use slide labels such as "Slide 1 - Cover", "Slide 2 - ...", and so on. Slide 1 should contain the main hook. Slides 2 to the final slide should each cover one idea with short, skimmable bullets or support lines. Every slide should also include a short "Visual Direction:" line describing that slide image. End with an "Instagram Caption" section. Keep table_of_contents as slide titles only for internal structure. Do not add SEO article sections, FAQ sections, citations, long Markdown essays, internal links, external links, or recommended follow-up topics.';
        }

        if ($type === 'social_post') {
            return 'Social body requirements: post_text must be native social copy for the selected platform, not an SEO article. Keep table_of_contents as a short outline of the social post, thread, or carousel.';
        }

        return 'Article body requirements: post_text must be the full publishable body in Markdown. Include one ## heading for each table_of_contents item, using the exact same heading text. Include actionable detail under each heading, not just a paragraph summary.';
    }

    private function isInstagramCarousel(array $template): bool
    {
        $platform = strtolower((string) ($template['platform'] ?? ''));
        $title = strtolower((string) ($template['title'] ?? ''));

        return $platform === 'instagram' || str_contains($title, 'instagram carousel');
    }

    public function articleStructureFor(array $template): string
    {
        $profile = (string) ($template['generation_profile'] ?? '');
        $title = strtolower((string) ($template['title'] ?? ''));
        $type = (string) ($template['content_type'] ?? '');

        if ($profile !== '') {
            $profileStructure = match ($profile) {
                'website_seo_article' => 'search-intent intro, table of contents, explanatory sections, examples/proof points, practical steps, FAQ, conclusion with CTA.',
                'hot_tech_dev_article' => 'strong opening hook, technical context, what changed (only for news under 3 days old) or how it works, why it matters, practical developer and buyer implications, implementation guidance, risks and tradeoffs, next steps, FAQ or key takeaway, CTA.',
                'howto_tutorial' => 'problem statement, prerequisites, step-by-step process, warnings/common mistakes, validation checklist, next steps, CTA.',
                'instagram_carousel' => 'carousel hook, slide-by-slide teaching points, caption, hashtags, CTA, visual direction.',
                'facebook_short_post' => 'opening hook, one clear practical point, short supporting context or example, concise takeaway, comment-friendly CTA.',
                default => '',
            };

            if ($profileStructure !== '') {
                return $profileStructure;
            }
        }

        if (str_contains($title, 'linkedin authority')) {
            return 'hook, context/problem, practical insight, example or proof point, concise takeaway, conversation-starting CTA.';
        }

        if (str_contains($title, 'content cluster')) {
            return 'pillar topic overview, search intent map, supporting article list, internal linking map, priority order, recommended next topics.';
        }

        if (str_contains($title, 'pillar guide')) {
            return 'executive summary, definition/context, core concepts, detailed subtopics, implementation steps, common mistakes, FAQ, conclusion with CTA.';
        }

        if (str_contains($title, 'how-to')) {
            return 'problem statement, prerequisites, step-by-step process, warnings/common mistakes, validation checklist, next steps, CTA.';
        }

        if (str_contains($title, 'hot tech') || str_contains($title, 'dev blog')) {
            return 'timely hook, technical context, what changed, why it matters, practical developer and buyer implications, implementation guidance, risks and tradeoffs, next steps, FAQ or key takeaway, CTA.';
        }

        if (str_contains($title, 'faq')) {
            return 'short intro, grouped FAQ questions with direct answers, objection-handling answers, internal link suggestions, closing CTA.';
        }

        if (str_contains($title, 'audit')) {
            return 'summary, findings grouped by priority, issue/impact/evidence/fix for each finding, implementation checklist, next steps.';
        }

        if (str_contains($title, 'founder story')) {
            return 'personal observation, tension or lesson, what changed, practical takeaway, reflective CTA question.';
        }

        if (str_contains($title, 'x thread')) {
            return 'opening hook, 6 to 8 numbered thread posts, proof or example post, final recap, CTA.';
        }

        if (str_contains($title, 'instagram carousel')) {
            return 'carousel hook, slide-by-slide teaching points, caption, hashtags, CTA, visual direction.';
        }

        if (str_contains($title, 'facebook short post')) {
            return 'opening hook, one clear practical point, short supporting context or example, concise takeaway, comment-friendly CTA.';
        }

        if (str_contains($title, 'reddit')) {
            return 'direct answer, context, practical steps, caveats, optional resources, non-promotional close.';
        }

        if (str_contains($title, 'newsletter')) {
            return 'subject options, preview text, opening note, main sections, links/resources, CTA, closing note.';
        }

        if (str_contains($title, 'comparison')) {
            return 'intro with decision context, criteria, option-by-option comparison, pros and cons, best-fit recommendations, FAQ, CTA.';
        }

        if (str_contains($title, 'case study')) {
            return 'client/context, challenge, constraints, approach, solution, results placeholders, lessons learned, CTA.';
        }

        if (str_contains($title, 'outreach')) {
            return 'value angle, subject lines, short pitch email, supporting proof bullets, resource offer, follow-up message.';
        }

        return match ($type) {
            'seo_article' => 'search-intent intro, table of contents, explanatory sections, examples/proof points, practical steps, FAQ, conclusion with CTA.',
            'cluster' => 'pillar overview, supporting topics, keyword clusters, internal link structure, priority sequence, next topics.',
            'faq' => 'intro, grouped questions, concise answers, objection handling, related links, closing CTA.',
            'technical_audit' => 'summary, prioritized issues, impact/evidence/fix details, checklist, next steps.',
            'comparison' => 'decision context, comparison criteria, option analysis, recommendations, FAQ, CTA.',
            'case_study' => 'context, challenge, approach, solution, results, takeaway, CTA.',
            'newsletter' => 'subject, preview, opening, sections, links, CTA, close.',
            'reddit_answer' => 'direct answer, details, caveats, resources, non-promotional close.',
            'outreach' => 'angle, subject lines, pitch, proof, offer, follow-up.',
            default => 'hook, context, main points, proof or example, takeaway, CTA.',
        };
    }

    public function seedDefaults(): void
    {
        if (! ee()->db->table_exists('socialposter_templates')) {
            return;
        }

        foreach ($this->defaults() as $default) {
            $this->resetQueryBuilder();
            $existing = ee()->db
                ->where('title', $default['title'])
                ->get('socialposter_templates')
                ->row_array();

            if ($existing) {
                $this->syncDefaultTemplate($existing, $default);
                continue;
            }

            $this->save($default);
        }
    }

    private function visibleTemplates(array $templates): array
    {
        if ($templates === []) {
            return [];
        }

        $legacyDefaults = $this->legacyDefaultPromptMap();
        $usageById = $this->templateUsageById();

        return array_values(array_filter($templates, function (array $template) use ($legacyDefaults, $usageById): bool {
            $id = (int) ($template['id'] ?? 0);
            $title = trim((string) ($template['title'] ?? ''));
            $instructions = trim((string) ($template['prompt_instructions'] ?? ''));

            if (! isset($legacyDefaults[$title])) {
                return true;
            }

            if ($instructions !== trim((string) $legacyDefaults[$title])) {
                return true;
            }

            return (($usageById[$id] ?? 0) > 0);
        }));
    }

    private function templateUsageById(): array
    {
        $usage = [];

        if (ee()->db->table_exists('socialposter_generations') && ee()->db->field_exists('template_id', 'socialposter_generations')) {
            foreach (ee()->db->select('template_id, COUNT(*) AS count', false)->group_by('template_id')->get('socialposter_generations')->result_array() as $row) {
                $templateId = (int) ($row['template_id'] ?? 0);
                if ($templateId > 0) {
                    $usage[$templateId] = ($usage[$templateId] ?? 0) + (int) ($row['count'] ?? 0);
                }
            }
        }

        if (ee()->db->table_exists('socialposter_instagram_posts') && ee()->db->field_exists('template_id', 'socialposter_instagram_posts')) {
            foreach (ee()->db->select('template_id, COUNT(*) AS count', false)->group_by('template_id')->get('socialposter_instagram_posts')->result_array() as $row) {
                $templateId = (int) ($row['template_id'] ?? 0);
                if ($templateId > 0) {
                    $usage[$templateId] = ($usage[$templateId] ?? 0) + (int) ($row['count'] ?? 0);
                }
            }
        }

        if (ee()->db->table_exists('socialposter_schedules') && ee()->db->field_exists('template_id', 'socialposter_schedules')) {
            foreach (ee()->db->select('template_id, COUNT(*) AS count', false)->group_by('template_id')->get('socialposter_schedules')->result_array() as $row) {
                $templateId = (int) ($row['template_id'] ?? 0);
                if ($templateId > 0) {
                    $usage[$templateId] = ($usage[$templateId] ?? 0) + (int) ($row['count'] ?? 0);
                }
            }
        }

        return $usage;
    }

    private function legacyDefaultPromptMap(): array
    {
        return [
            'LinkedIn Authority Post' => 'Create a platform-neutral social package with a hook, concise post text, SEO keywords, one external link idea, one internal link idea, recommended follow-up topics, and a realistic social image prompt.',
            'Content Cluster Plan' => 'Create a pillar topic, supporting article topics, table of contents, semantic keyword clusters, internal linking structure, external citation ideas, recommended next topics, and a realistic image prompt.',
            'SEO Article - Medium' => 'Create a focused SEO blog post package around one clear search intent. Include headline options, intro, outline, keywords, citation ideas, internal links, external references, and a practical image prompt.',
            'Pillar Guide' => 'Create a definitive pillar guide package with sections, subtopics, entity coverage, FAQs, citations, internal link opportunities, JSON-LD recommendations, and a realistic topic image prompt.',
            'FAQ Answer Page' => 'Create an FAQ-style page package with concise answers, objection handling, FAQPage schema suggestions, internal links, and a simple realistic image prompt.',
            'Technical SEO Audit Summary' => 'Create a technical SEO audit content package. Frame each issue as problem, ranking/LLM impact, evidence to collect, recommended fix, and priority. Include schema ideas and realistic technical image direction.',
            'LinkedIn Founder Story' => 'Create a concise founder-led LinkedIn post with a personal observation, lesson learned, practical takeaway, natural CTA question, and a realistic portrait-style image prompt.',
            'X Thread' => 'Create an X thread package with a strong first post, 6 to 8 short thread posts, a final CTA, SEO keywords, link ideas, and one realistic image prompt.',
            'Reddit Helpful Answer' => 'Create a helpful Reddit-style answer that avoids sounding promotional. Include direct advice, caveats, optional resource suggestions, and a subtle brand-authority angle without hard selling.',
            'Newsletter Brief' => 'Create a newsletter package with subject line options, preview text, opening hook, main sections, link recommendations, CTA, and a newsletter header image prompt.',
            'Comparison Page' => 'Create a comparison page package with decision criteria, pros/cons, ideal users, objection handling, FAQ ideas, schema suggestions, and a realistic comparison image prompt.',
            'Case Study Story' => 'Create a case study content package with challenge, constraints, approach, solution, result metrics placeholders, client-facing summary, social version, and a realistic case-study image prompt.',
            'Backlink Outreach Pitch' => 'Create an outreach package with a short pitch email, subject lines, value angle, suggested resource to offer, follow-up message, and social proof bullets.',
        ];
    }

    public function generationProfileLabel(string $profile): string
    {
        $profile = trim($profile);
        if ($profile === '') {
            return 'Generic / custom behavior';
        }

        return (string) ($this->fieldOptions()['generation_profile'][$profile] ?? $profile);
    }

    public function generationProfileForTemplate(?array $template): string
    {
        if (! is_array($template)) {
            return '';
        }

        $profile = trim((string) ($template['generation_profile'] ?? ''));
        if ($profile !== '') {
            return $profile;
        }

        $title = strtolower((string) ($template['title'] ?? ''));
        $platform = strtolower((string) ($template['platform'] ?? ''));
        $type = strtolower((string) ($template['content_type'] ?? ''));

        return match (true) {
            str_contains($title, 'hot tech') || str_contains($title, 'dev blog') => 'hot_tech_dev_article',
            str_contains($title, 'how-to') => 'howto_tutorial',
            str_contains($title, 'instagram carousel') || $platform === 'instagram' => 'instagram_carousel',
            str_contains($title, 'facebook short post') || $platform === 'facebook' => 'facebook_short_post',
            $platform === 'website' && $type === 'seo_article' => 'website_seo_article',
            default => '',
        };
    }

    public function profileRequiresFreshness(string $profile): bool
    {
        return trim($profile) === 'hot_tech_dev_article';
    }

    public function profileFreshnessContract(string $profile): string
    {
        return match (trim($profile)) {
            'hot_tech_dev_article' => 'Freshness requirement: prioritize current, time-aware framing. If the topic involves a release, change, announcement, model, framework, or platform shift, anchor the piece in what changed now and avoid presenting old developments as current.',
            default => '',
        };
    }

    public function profilePlanningContract(string $profile): string
    {
        return match (trim($profile)) {
            'website_seo_article' => 'Planning guidance: shape this as a search-intent aware website article with a clear title, buyer-useful brief, semantic keywords, and realistic editorial image direction.',
            'hot_tech_dev_article' => 'Planning guidance: shape this as a timely technical article. Prefer current changes, concrete product/framework names, implementation consequences, and angles that help developers or technical buyers decide what matters.',
            'howto_tutorial' => 'Planning guidance: shape this as a task-oriented tutorial. Favor prerequisites, ordered steps, warnings, validation, and practical clarity over broad positioning.',
            'instagram_carousel' => 'Planning guidance: shape this as a carousel plan with a clear topic, slide arc, cover-hook direction, caption direction, and concise visual system.',
            'facebook_short_post' => 'Planning guidance: shape this as a short in-feed Facebook post with one clear point, easy skim value, and a comment-friendly CTA.',
            default => '',
        };
    }

    public function templateIdsForProfile(string $profile): array
    {
        $profile = trim($profile);
        if ($profile === '') {
            return [];
        }

        $ids = [];
        foreach ($this->all() as $template) {
            if ($this->generationProfileForTemplate($template) === $profile) {
                $ids[] = (int) ($template['id'] ?? 0);
            }
        }

        return array_values(array_filter(array_unique($ids)));
    }

    private function syncDefaultTemplate(array $existing, array $default): void
    {
        $title = trim((string) ($existing['title'] ?? ''));
        $updates = [];

        if (ee()->db->field_exists('generation_profile', 'socialposter_templates')) {
            $currentProfile = trim((string) ($existing['generation_profile'] ?? ''));
            $defaultProfile = trim((string) ($default['generation_profile'] ?? ''));
            if ($currentProfile === '' && $defaultProfile !== '') {
                $updates['generation_profile'] = $defaultProfile;
            }
        }

        if ($title === 'Hot Tech & Dev Blog Article') {
            $currentInstructions = trim((string) ($existing['prompt_instructions'] ?? ''));
            if ($currentInstructions === '' || $currentInstructions === self::HOT_TECH_OLD_INSTRUCTIONS) {
                $updates['prompt_instructions'] = (string) ($default['prompt_instructions'] ?? self::HOT_TECH_NEW_INSTRUCTIONS);
            }
        }

        if ($updates === []) {
            return;
        }

        $this->resetQueryBuilder();
        ee()->db
            ->where('id', (int) ($existing['id'] ?? 0))
            ->update('socialposter_templates', $updates);
        $this->clearTemplateScaffoldCache((int) ($existing['id'] ?? 0));
    }

    private function resetQueryBuilder(): void
    {
        if (method_exists(ee()->db, 'reset_query')) {
            ee()->db->reset_query();
        }
    }

    private function defaultsWithIds(): array
    {
        $id = 1;
        return array_map(function ($template) use (&$id) {
            $template['id'] = $id++;
            return $template;
        }, $this->defaults());
    }

    private function cachedScaffoldFor(array $template): ?string
    {
        $path = $this->scaffoldCachePath($template);
        if (! is_file($path)) {
            return null;
        }

        $content = @file_get_contents($path);
        if (! is_string($content) || trim($content) === '') {
            return null;
        }

        return $content;
    }

    private function storeCachedScaffold(array $template, string $instructions): void
    {
        $instructions = trim($instructions);
        if ($instructions === '') {
            return;
        }

        $directory = $this->scaffoldCacheDirectory();
        if (! is_dir($directory) && ! @mkdir($directory, 0775, true) && ! is_dir($directory)) {
            return;
        }

        @file_put_contents($this->scaffoldCachePath($template), $instructions);
    }

    private function clearTemplateScaffoldCache(int $id): void
    {
        if ($id < 1) {
            return;
        }

        foreach (glob($this->scaffoldCacheDirectory() . '/template_' . $id . '_*.txt') ?: [] as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }

    private function scaffoldCachePath(array $template): string
    {
        $id = max(0, (int) ($template['id'] ?? 0));
        $hash = $this->scaffoldCacheHash($template);

        return $this->scaffoldCacheDirectory() . '/template_' . $id . '_' . $hash . '.txt';
    }

    private function scaffoldCacheHash(array $template): string
    {
        $payload = [
            'version' => self::TEMPLATE_SCAFFOLD_CACHE_VERSION,
            'title' => (string) ($template['title'] ?? ''),
            'content_type' => (string) ($template['content_type'] ?? ''),
            'platform' => (string) ($template['platform'] ?? ''),
            'generation_profile' => (string) ($template['generation_profile'] ?? ''),
            'length_preset' => (string) ($template['length_preset'] ?? ''),
            'word_count' => (int) ($template['word_count'] ?? 0),
            'tone' => (string) ($template['tone'] ?? ''),
            'audience' => (string) ($template['audience'] ?? ''),
            'goal' => (string) ($template['goal'] ?? ''),
            'research_mode' => (string) ($template['research_mode'] ?? ''),
            'citation_count' => (int) ($template['citation_count'] ?? 0),
            'internal_link_count' => (int) ($template['internal_link_count'] ?? 0),
            'external_link_count' => (int) ($template['external_link_count'] ?? 0),
            'schema_type' => (string) ($template['schema_type'] ?? ''),
            'image_style' => (string) ($template['image_style'] ?? ''),
            'cta_style' => (string) ($template['cta_style'] ?? ''),
            'prompt_instructions' => (string) ($template['prompt_instructions'] ?? ''),
            'profile_contract' => $this->profileContractFor($template),
            'platform_contract' => $this->platformContractFor($template),
            'content_structure' => $this->articleStructureFor($template),
            'body_requirements' => $this->bodyRequirementsFor($template),
        ];

        return sha1(json_encode($payload, JSON_UNESCAPED_UNICODE));
    }

    private function scaffoldCacheDirectory(): string
    {
        return dirname(__DIR__, 3) . '/cache/socialposter/template_scaffolds';
    }
}
