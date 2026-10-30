<?php

namespace JavidFazaeli\SocialPoster\Service;

class SeoScorer
{
    public function score(array $post): array
    {
        $title = trim((string) ($post['title'] ?? ''));
        $intro = trim((string) ($post['intro_text'] ?? ''));
        $body = trim((string) ($post['post_text'] ?? ''));
        $toc = $this->cleanList((array) ($post['table_of_contents'] ?? []));
        $keywords = $this->cleanList((array) ($post['keywords'] ?? []));
        $text = trim($title . ' ' . $intro . ' ' . $body);
        $wordCount = $this->wordCount($body);
        $primaryKeyword = $keywords[0] ?? '';
        $hasImageBrief = trim((string) ($post['image_brief'] ?? '')) !== '';
        $hasImagePrompt = trim((string) ($post['image_prompt'] ?? '')) !== '';
        $hasImagePath = $this->hasSavedImage($post);
        $internalStatus = $this->internalLinkStatus(trim((string) ($post['internal_link'] ?? '')), $body);
        $sourceCount = $this->specificSourceCount($body, trim((string) ($post['external_link'] ?? '')));

        $checks = [
            $this->check(
                'Article depth',
                $wordCount >= 900,
                min(14, (int) floor($wordCount / 900 * 14)),
                14,
                $wordCount . ' words',
                'Aim for 900+ words for a publishable SEO article.'
            ),
            $this->check(
                'SEO title',
                $this->titleLength($title) >= 45 && $this->titleLength($title) <= 70,
                $this->rangePoints($this->titleLength($title), 45, 70, 12),
                12,
                $this->titleLength($title) . ' characters',
                'Use a clear 45-70 character title.'
            ),
            $this->check(
                'Primary keyword',
                $primaryKeyword !== '' && $this->keywordMatches($text, $primaryKeyword),
                $primaryKeyword !== '' ? ($this->keywordMatches($title . ' ' . $intro, $primaryKeyword) ? 12 : ($this->keywordMatches($text, $primaryKeyword) ? 8 : 0)) : 0,
                12,
                $primaryKeyword !== '' ? $primaryKeyword : 'Missing',
                'Put the main keyword in the title or intro.'
            ),
            $this->check(
                'Keyword set',
                count($keywords) >= 4,
                min(10, count($keywords) * 2),
                10,
                count($keywords) . ' keywords',
                'Include at least 4 focused SEO keywords.'
            ),
            $this->check(
                'Heading structure',
                count($this->headings($body)) >= max(3, count($toc)),
                min(12, count($this->headings($body)) * 3),
                12,
                count($this->headings($body)) . ' headings',
                'Use structured H2/H3 sections that match the article plan.'
            ),
            $this->check(
                'Table of contents',
                count($toc) >= 3 && $this->tocMatchesHeadings($toc, $body),
                count($toc) >= 3 ? ($this->tocMatchesHeadings($toc, $body) ? 10 : 6) : 0,
                10,
                count($toc) . ' items',
                'Add 3+ TOC items and matching article headings.'
            ),
            $this->check(
                'Internal link',
                $internalStatus === 'found',
                $internalStatus === 'found' ? 8 : ($internalStatus === 'unverified' ? 6 : ($internalStatus === 'not_found' ? 2 : 0)),
                8,
                ['found' => 'Links to a published post', 'unverified' => 'Present (not verified)', 'not_found' => 'Not a published post', 'missing' => 'Missing'][$internalStatus],
                'Link one related post that is published on this site.'
            ),
            $this->check(
                'External sources',
                $sourceCount >= 2,
                $sourceCount >= 2 ? 7 : ($sourceCount === 1 ? 4 : 0),
                7,
                $sourceCount . ' specific ' . ($sourceCount === 1 ? 'source' : 'sources'),
                'Cite at least two specific external pages (not just homepages).'
            ),
            $this->check(
                'Meta summary',
                $this->titleLength($intro) >= 120 && $this->titleLength($intro) <= 160,
                $this->rangePoints($this->titleLength($intro), 120, 160, 8),
                8,
                $this->titleLength($intro) . ' characters',
                'Keep the intro/meta summary around 120-160 characters; search results cut off longer text.'
            ),
            $this->check(
                'Image',
                $hasImagePath && $hasImageBrief && $hasImagePrompt,
                ($hasImagePath ? 3 : 0) + ($hasImageBrief ? 2 : 0) + ($hasImagePrompt ? 2 : 0),
                7,
                $hasImagePath ? 'Saved image' : ($hasImageBrief || $hasImagePrompt ? 'Prompted, no saved image' : 'Missing'),
                'Keep a saved image, image brief, and production prompt.'
            ),
        ];

        $score = array_sum(array_column($checks, 'points'));

        return [
            'score' => max(0, min(100, $score)),
            'grade' => $this->grade($score),
            'summary' => $this->summary($score),
            'word_count' => $wordCount,
            'primary_keyword' => $primaryKeyword,
            'checks' => $checks,
        ];
    }

    public function scoreMany(array $posts): array
    {
        return array_map(function ($post) {
            $post['seo_score'] = $this->score($post);
            return $post;
        }, $posts);
    }

    private function hasSavedImage(array $post): bool
    {
        $imagePath = trim((string) ($post['image_path'] ?? ''));
        if ($imagePath !== '') {
            return is_file((new ImageStorage())->publicPath($imagePath));
        }

        return trim((string) ($post['image_url'] ?? '')) !== '';
    }

    private function check(string $label, bool $passed, int $points, int $max, string $detail, string $recommendation): array
    {
        $points = max(0, min($max, $points));

        return [
            'label' => $label,
            'passed' => $passed,
            'points' => $points,
            'max' => $max,
            'detail' => $detail,
            'recommendation' => $recommendation,
        ];
    }

    private function grade(int $score): string
    {
        if ($score >= 90) {
            return 'Excellent';
        }

        if ($score >= 75) {
            return 'Strong';
        }

        if ($score >= 60) {
            return 'Needs polish';
        }

        return 'Weak';
    }

    private function summary(int $score): string
    {
        if ($score >= 90) {
            return 'Ready to publish with strong SEO fundamentals.';
        }

        if ($score >= 75) {
            return 'Good article foundation with a few optimization gaps.';
        }

        if ($score >= 60) {
            return 'Usable draft, but improve structure, links, or keyword coverage.';
        }

        return 'Needs SEO work before publishing.';
    }

    private function cleanList(array $items): array
    {
        return array_values(array_filter(array_map(fn($item) => trim((string) $item), $items)));
    }

    private function wordCount(string $value): int
    {
        $value = strip_tags($value);
        $value = preg_replace('/[`#*_>\[\]\(\)-]+/', ' ', $value);
        preg_match_all('/\b[\p{L}\p{N}][\p{L}\p{N}\'-]*\b/u', (string) $value, $matches);
        return count($matches[0] ?? []);
    }

    private function titleLength(string $value): int
    {
        return mb_strlen(trim($value));
    }

    private function normalizeText(string $value): string
    {
        $value = strip_tags($value);
        $value = preg_replace('/[^A-Za-z0-9]+/', ' ', $value);
        return trim((string) preg_replace('/\s+/', ' ', $value));
    }

    /**
     * True when the keyword appears as a phrase, or when every word of it
     * appears, ignoring case, punctuation, hyphens and possessive 's.
     */
    private function keywordMatches(string $haystack, string $keyword): bool
    {
        $words = $this->keywordWords($keyword);
        if ($words === []) {
            return false;
        }

        $haystackWords = $this->keywordWords($haystack);
        if (strpos(' ' . implode(' ', $haystackWords) . ' ', ' ' . implode(' ', $words) . ' ') !== false) {
            return true;
        }

        return array_diff($words, $haystackWords) === [];
    }

    private function keywordWords(string $value): array
    {
        $value = strtolower(strip_tags($value));
        $value = str_replace(["\u{2019}", "\u{2018}", '`'], "'", $value);
        $value = preg_replace("/'s\b/", '', $value);
        $value = preg_replace('/[^a-z0-9]+/', ' ', (string) $value);
        $stopWords = ['a', 'an', 'and', 'the', 'of', 'for', 'to', 'in', 'on', 'with', 'vs', 'versus', 'or'];
        $words = [];
        foreach (explode(' ', trim((string) $value)) as $word) {
            if ($word === '' || in_array($word, $stopWords, true)) {
                continue;
            }
            // Treat simple plurals as the same word: databases -> database.
            $words[] = strlen($word) > 3 && substr($word, -1) === 's' && substr($word, -2) !== 'ss' ? substr($word, 0, -1) : $word;
        }

        return $words;
    }

    /** 'found', 'not_found', 'unverified' (no database available) or 'missing'. */
    private function internalLinkStatus(string $link, string $body): string
    {
        if ($link === '' && ! preg_match('~\]\((/blog/article/[^)\s]+)\)~', $body, $match)) {
            return 'missing';
        }

        $link = $link !== '' ? $link : $match[1];
        if (! preg_match('~/blog/article/([a-z0-9_-]+)~i', $link, $slug)) {
            return 'not_found';
        }

        if (! function_exists('ee') || ! isset(ee()->db)) {
            return 'unverified';
        }

        $found = ee()->db
            ->select('t.entry_id')
            ->from('channel_titles t')
            ->join('channels c', 'c.channel_id = t.channel_id')
            ->where('c.channel_name', 'blog')
            ->where('t.url_title', $slug[1])
            ->where('t.status', 'open')
            ->limit(1)
            ->get();

        return $found->num_rows() > 0 ? 'found' : 'not_found';
    }

    /** Distinct external pages (not bare homepages, not this site) cited in the body. */
    private function specificSourceCount(string $body, string $externalLink): int
    {
        preg_match_all('~https?://[^\s)<>"\]]+~i', $body . ' ' . $externalLink, $matches);
        $siteHost = function_exists('ee') && isset(ee()->config) ? (string) parse_url((string) ee()->config->item('site_url'), PHP_URL_HOST) : '';
        $pages = [];
        foreach ($matches[0] as $url) {
            $url = rtrim($url, '.,;:!?');
            $parts = parse_url($url);
            $host = strtolower((string) ($parts['host'] ?? ''));
            $path = trim((string) ($parts['path'] ?? ''), '/');
            if ($host === '' || $path === '' || ($siteHost !== '' && $host === strtolower($siteHost))) {
                continue;
            }
            $pages[$host . '/' . strtolower($path)] = true;
        }

        return count($pages);
    }

    private function headings(string $markdown): array
    {
        preg_match_all('/^#{2,4}\s+(.+)$/m', $markdown, $matches);
        return array_values(array_filter(array_map('trim', $matches[1] ?? [])));
    }

    private function tocMatchesHeadings(array $toc, string $markdown): bool
    {
        $headingKeys = array_map([$this, 'key'], $this->headings($markdown));
        foreach ($toc as $item) {
            if (! in_array($this->key($item), $headingKeys, true)) {
                return false;
            }
        }

        return ! empty($toc);
    }

    private function key(string $value): string
    {
        return strtolower($this->normalizeText($value));
    }

    private function rangePoints(int $value, int $min, int $max, int $points): int
    {
        if ($value >= $min && $value <= $max) {
            return $points;
        }

        if ($value <= 0) {
            return 0;
        }

        if ($value < $min) {
            return max(0, (int) floor($value / $min * $points));
        }

        $over = $value - $max;
        return max(0, $points - (int) ceil($over / 10));
    }
}
