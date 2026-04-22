<?php

namespace JavidFazaeli\SocialPoster\Service;

class TemplateManager
{
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
                'branded editorial infographic' => 'Branded editorial infographic',
                'clean branded social graphic' => 'Clean branded social graphic',
                'topic map infographic' => 'Topic map infographic',
                'technical diagram' => 'Technical diagram',
                'case-study visual' => 'Case-study visual',
                'minimal quote card' => 'Minimal quote card',
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

    public function defaults(): array
    {
        return [
            [
                'title' => 'SEO Article - Long',
                'content_type' => 'seo_article',
                'platform' => 'Website',
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
                'image_style' => 'branded editorial infographic',
                'cta_style' => 'soft consultation CTA',
                'prompt_instructions' => 'Create an SEO/LLM optimized article package with a strong title, search-intent driven intro, table of contents, semantic keyword set, source/citation ideas, internal link suggestions, recommended topics, and a branded infographic prompt.',
                'is_default' => 1,
            ],
            [
                'title' => 'LinkedIn Authority Post',
                'content_type' => 'social_post',
                'platform' => 'LinkedIn',
                'length_preset' => 'medium',
                'word_count' => 450,
                'tone' => 'confident, human, useful',
                'audience' => 'founders, marketers, and web teams',
                'goal' => 'build authority and start conversations',
                'research_mode' => 'light research with one credible external reference',
                'citation_count' => 1,
                'internal_link_count' => 1,
                'external_link_count' => 1,
                'schema_type' => 'None',
                'image_style' => 'clean branded social graphic',
                'cta_style' => 'conversation starter',
                'prompt_instructions' => 'Create a platform-neutral social package with a hook, concise post text, SEO keywords, one external link idea, one internal link idea, recommended follow-up topics, and a branded image prompt.',
                'is_default' => 0,
            ],
            [
                'title' => 'Content Cluster Plan',
                'content_type' => 'cluster',
                'platform' => 'Website',
                'length_preset' => 'outline',
                'word_count' => 900,
                'tone' => 'strategic, organized, practical',
                'audience' => 'site owners planning topical authority',
                'goal' => 'plan a topical cluster with internal links',
                'research_mode' => 'SERP-style clustering from supplied topic and common search intent',
                'citation_count' => 2,
                'internal_link_count' => 6,
                'external_link_count' => 2,
                'schema_type' => 'ItemList',
                'image_style' => 'topic map infographic',
                'cta_style' => 'next-step content planning CTA',
                'prompt_instructions' => 'Create a pillar topic, supporting article topics, table of contents, semantic keyword clusters, internal linking structure, external citation ideas, recommended next topics, and a branded infographic prompt.',
                'is_default' => 0,
            ],
            [
                'title' => 'SEO Article - Medium',
                'content_type' => 'seo_article',
                'platform' => 'Website',
                'length_preset' => 'medium',
                'word_count' => 1200,
                'tone' => 'friendly, educational, concise',
                'audience' => 'local businesses and service providers',
                'goal' => 'answer buyer questions and reduce objections',
                'research_mode' => 'cite credible sources and include proof points',
                'citation_count' => 3,
                'internal_link_count' => 2,
                'external_link_count' => 2,
                'schema_type' => 'BlogPosting',
                'image_style' => 'branded editorial infographic',
                'cta_style' => 'read related article',
                'prompt_instructions' => 'Create a focused SEO blog post package around one clear search intent. Include headline options, intro, outline, keywords, citation ideas, internal links, external references, and a practical image prompt.',
                'is_default' => 0,
            ],
            [
                'title' => 'Pillar Guide',
                'content_type' => 'seo_article',
                'platform' => 'Website',
                'length_preset' => 'pillar',
                'word_count' => 2500,
                'tone' => 'expert, clear, practical',
                'audience' => 'business owners, founders, and technical decision makers',
                'goal' => 'rank in Google and LLM answers while converting readers into qualified leads',
                'research_mode' => 'cite credible sources and include proof points',
                'citation_count' => 6,
                'internal_link_count' => 5,
                'external_link_count' => 5,
                'schema_type' => 'Article',
                'image_style' => 'topic map infographic',
                'cta_style' => 'soft consultation CTA',
                'prompt_instructions' => 'Create a definitive pillar guide package with sections, subtopics, entity coverage, FAQs, citations, internal link opportunities, JSON-LD recommendations, and a topic-map infographic prompt.',
                'is_default' => 0,
            ],
            [
                'title' => 'How-To Tutorial',
                'content_type' => 'seo_article',
                'platform' => 'Website',
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
                'image_style' => 'technical diagram',
                'cta_style' => 'book a call',
                'prompt_instructions' => 'Create a step-by-step how-to content package with prerequisites, process outline, warnings, validation checklist, SEO keywords, HowTo schema ideas, and a technical diagram prompt.',
                'is_default' => 0,
            ],
            [
                'title' => 'FAQ Answer Page',
                'content_type' => 'faq',
                'platform' => 'Website',
                'length_preset' => 'medium',
                'word_count' => 900,
                'tone' => 'friendly, educational, concise',
                'audience' => 'local businesses and service providers',
                'goal' => 'answer buyer questions and reduce objections',
                'research_mode' => 'use only supplied brand/site context',
                'citation_count' => 1,
                'internal_link_count' => 3,
                'external_link_count' => 1,
                'schema_type' => 'FAQPage',
                'image_style' => 'minimal quote card',
                'cta_style' => 'read related article',
                'prompt_instructions' => 'Create an FAQ-style page package with concise answers, objection handling, FAQPage schema suggestions, internal links, and a simple branded visual prompt.',
                'is_default' => 0,
            ],
            [
                'title' => 'Technical SEO Audit Summary',
                'content_type' => 'technical_audit',
                'platform' => 'Website',
                'length_preset' => 'outline',
                'word_count' => 900,
                'tone' => 'direct, technical, evidence-based',
                'audience' => 'developers and technical buyers',
                'goal' => 'answer buyer questions and reduce objections',
                'research_mode' => 'technical checklist with issue, impact, and fix framing',
                'citation_count' => 2,
                'internal_link_count' => 2,
                'external_link_count' => 2,
                'schema_type' => 'Article',
                'image_style' => 'technical diagram',
                'cta_style' => 'book a call',
                'prompt_instructions' => 'Create a technical SEO audit content package. Frame each issue as problem, ranking/LLM impact, evidence to collect, recommended fix, and priority. Include schema and visual ideas.',
                'is_default' => 0,
            ],
            [
                'title' => 'LinkedIn Founder Story',
                'content_type' => 'social_post',
                'platform' => 'LinkedIn',
                'length_preset' => 'short',
                'word_count' => 300,
                'tone' => 'thoughtful, founder-led, conversational',
                'audience' => 'founders, marketers, and web teams',
                'goal' => 'build authority and start conversations',
                'research_mode' => 'use only supplied brand/site context',
                'citation_count' => 0,
                'internal_link_count' => 1,
                'external_link_count' => 0,
                'schema_type' => 'None',
                'image_style' => 'minimal quote card',
                'cta_style' => 'conversation starter',
                'prompt_instructions' => 'Create a concise founder-led LinkedIn post with a personal observation, lesson learned, practical takeaway, natural CTA question, and simple quote-card image prompt.',
                'is_default' => 0,
            ],
            [
                'title' => 'X Thread',
                'content_type' => 'social_post',
                'platform' => 'X / Twitter',
                'length_preset' => 'medium',
                'word_count' => 600,
                'tone' => 'confident, human, useful',
                'audience' => 'founders, marketers, and web teams',
                'goal' => 'drive newsletter or social engagement',
                'research_mode' => 'light research with one credible external reference',
                'citation_count' => 1,
                'internal_link_count' => 1,
                'external_link_count' => 1,
                'schema_type' => 'None',
                'image_style' => 'clean branded social graphic',
                'cta_style' => 'newsletter signup',
                'prompt_instructions' => 'Create an X thread package with a strong first post, 6 to 8 short thread posts, a final CTA, SEO keywords, link ideas, and one clean visual prompt.',
                'is_default' => 0,
            ],
            [
                'title' => 'Instagram Carousel',
                'content_type' => 'social_post',
                'platform' => 'Instagram',
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
                'image_style' => 'clean branded social graphic',
                'cta_style' => 'conversation starter',
                'prompt_instructions' => 'Create an Instagram carousel package with slide-by-slide copy, caption, hashtags/keywords, CTA, and a consistent branded carousel image prompt.',
                'is_default' => 0,
            ],
            [
                'title' => 'Reddit Helpful Answer',
                'content_type' => 'reddit_answer',
                'platform' => 'Reddit',
                'length_preset' => 'medium',
                'word_count' => 600,
                'tone' => 'friendly, educational, concise',
                'audience' => 'founders, marketers, and web teams',
                'goal' => 'build authority and start conversations',
                'research_mode' => 'use only supplied brand/site context',
                'citation_count' => 1,
                'internal_link_count' => 0,
                'external_link_count' => 1,
                'schema_type' => 'QAPage',
                'image_style' => 'minimal quote card',
                'cta_style' => 'no CTA',
                'prompt_instructions' => 'Create a helpful Reddit-style answer that avoids sounding promotional. Include direct advice, caveats, optional resource suggestions, and a subtle brand-authority angle without hard selling.',
                'is_default' => 0,
            ],
            [
                'title' => 'Newsletter Brief',
                'content_type' => 'newsletter',
                'platform' => 'Newsletter',
                'length_preset' => 'medium',
                'word_count' => 900,
                'tone' => 'thoughtful, founder-led, conversational',
                'audience' => 'agency clients and marketing managers',
                'goal' => 'drive newsletter or social engagement',
                'research_mode' => 'light research with one credible external reference',
                'citation_count' => 1,
                'internal_link_count' => 2,
                'external_link_count' => 1,
                'schema_type' => 'None',
                'image_style' => 'branded editorial infographic',
                'cta_style' => 'newsletter signup',
                'prompt_instructions' => 'Create a newsletter package with subject line options, preview text, opening hook, main sections, link recommendations, CTA, and a newsletter header image prompt.',
                'is_default' => 0,
            ],
            [
                'title' => 'Comparison Page',
                'content_type' => 'comparison',
                'platform' => 'Website',
                'length_preset' => 'long',
                'word_count' => 1800,
                'tone' => 'direct, technical, evidence-based',
                'audience' => 'business owners, founders, and technical decision makers',
                'goal' => 'answer buyer questions and reduce objections',
                'research_mode' => 'compare options, pros, cons, and decision criteria',
                'citation_count' => 4,
                'internal_link_count' => 3,
                'external_link_count' => 3,
                'schema_type' => 'Review',
                'image_style' => 'case-study visual',
                'cta_style' => 'book a call',
                'prompt_instructions' => 'Create a comparison page package with decision criteria, pros/cons, ideal users, objection handling, FAQ ideas, schema suggestions, and a comparison visual prompt.',
                'is_default' => 0,
            ],
            [
                'title' => 'Case Study Story',
                'content_type' => 'case_study',
                'platform' => 'Website',
                'length_preset' => 'long',
                'word_count' => 1800,
                'tone' => 'expert, clear, practical',
                'audience' => 'agency clients and marketing managers',
                'goal' => 'rank in Google and LLM answers while converting readers into qualified leads',
                'research_mode' => 'use only supplied brand/site context',
                'citation_count' => 0,
                'internal_link_count' => 3,
                'external_link_count' => 0,
                'schema_type' => 'Article',
                'image_style' => 'case-study visual',
                'cta_style' => 'soft consultation CTA',
                'prompt_instructions' => 'Create a case study content package with challenge, constraints, approach, solution, result metrics placeholders, client-facing summary, social version, and a branded case-study image prompt.',
                'is_default' => 0,
            ],
            [
                'title' => 'Backlink Outreach Pitch',
                'content_type' => 'outreach',
                'platform' => 'Multi-platform',
                'length_preset' => 'short',
                'word_count' => 300,
                'tone' => 'confident, human, useful',
                'audience' => 'agency clients and marketing managers',
                'goal' => 'earn citations, shares, and backlinks',
                'research_mode' => 'compare options, pros, cons, and decision criteria',
                'citation_count' => 0,
                'internal_link_count' => 1,
                'external_link_count' => 1,
                'schema_type' => 'None',
                'image_style' => 'minimal quote card',
                'cta_style' => 'conversation starter',
                'prompt_instructions' => 'Create an outreach package with a short pitch email, subject lines, value angle, suggested resource to offer, follow-up message, and social proof bullets.',
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

        return ee()->db
            ->order_by('is_default', 'DESC')
            ->order_by('title', 'ASC')
            ->get('socialposter_templates')
            ->result_array();
    }

    public function options(bool $includeEmpty = true): array
    {
        $options = $includeEmpty ? [0 => 'No template'] : [];

        foreach ($this->all() as $template) {
            $options[(int) $template['id']] = (string) $template['title'];
        }

        return $options;
    }

    public function find(int $id): ?array
    {
        if ($id < 1 || ! ee()->db->table_exists('socialposter_templates')) {
            return null;
        }

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
            return $id;
        }

        $row['created_at'] = $now;
        ee()->db->insert('socialposter_templates', $row);

        return (int) ee()->db->insert_id();
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

        return ee()->db->affected_rows() > 0;
    }

    public function instructionsFor(int $id): string
    {
        $template = $this->find($id);
        if (! $template) {
            return '';
        }

        $lines = [
            'Use this generation template:',
            'Template: ' . $template['title'],
            'Content type: ' . $template['content_type'],
            'Platform: ' . $template['platform'],
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
            'Article structure: ' . $this->articleStructureFor($template),
            'Article body requirements: post_text must be the full publishable body in Markdown. Include one ## heading for each table_of_contents item, using the exact same heading text. Include actionable detail under each heading, not just a paragraph summary.',
            'Internal link requirements: use only relative internal URLs beginning with /, or exact site URLs supplied by the user. Do not invent placeholder domains.',
        ];

        return implode("\n", array_filter($lines, fn($line) => trim($line) !== ''));
    }

    public function articleStructureFor(array $template): string
    {
        $title = strtolower((string) ($template['title'] ?? ''));
        $type = (string) ($template['content_type'] ?? '');

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
            $exists = ee()->db
                ->where('title', $default['title'])
                ->count_all_results('socialposter_templates') > 0;

            if ($exists) {
                continue;
            }

            $this->save($default);
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
}
