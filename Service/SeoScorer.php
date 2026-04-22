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
                $primaryKeyword !== '' && $this->containsPhrase($title . ' ' . $intro . ' ' . $body, $primaryKeyword),
                $primaryKeyword !== '' ? ($this->containsPhrase($title, $primaryKeyword) ? 12 : ($this->containsPhrase($text, $primaryKeyword) ? 8 : 0)) : 0,
                12,
                $primaryKeyword !== '' ? $primaryKeyword : 'Missing',
                'Put the main keyword in the title or early article copy.'
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
                trim((string) ($post['internal_link'] ?? '')) !== '',
                trim((string) ($post['internal_link'] ?? '')) !== '' ? 8 : 0,
                8,
                trim((string) ($post['internal_link'] ?? '')) !== '' ? 'Present' : 'Missing',
                'Add one relevant internal link to support crawling and topical authority.'
            ),
            $this->check(
                'External citation',
                trim((string) ($post['external_link'] ?? '')) !== '' || preg_match('/https?:\/\//i', $body),
                (trim((string) ($post['external_link'] ?? '')) !== '' || preg_match('/https?:\/\//i', $body)) ? 7 : 0,
                7,
                (trim((string) ($post['external_link'] ?? '')) !== '' || preg_match('/https?:\/\//i', $body)) ? 'Present' : 'Missing',
                'Reference a credible external source when the topic needs support.'
            ),
            $this->check(
                'Meta summary',
                $this->titleLength($intro) >= 120 && $this->titleLength($intro) <= 170,
                $this->rangePoints($this->titleLength($intro), 120, 170, 8),
                8,
                $this->titleLength($intro) . ' characters',
                'Keep the intro/meta summary around 120-170 characters.'
            ),
            $this->check(
                'Image SEO',
                trim((string) ($post['image_brief'] ?? '')) !== '' && trim((string) ($post['image_prompt'] ?? '')) !== '',
                (trim((string) ($post['image_brief'] ?? '')) !== '' ? 4 : 0) + (trim((string) ($post['image_prompt'] ?? '')) !== '' ? 3 : 0),
                7,
                trim((string) ($post['image_brief'] ?? '')) !== '' ? 'Briefed' : 'Missing',
                'Keep an image brief and production prompt for richer publishing.'
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
        return strlen(trim($value));
    }

    private function containsPhrase(string $haystack, string $needle): bool
    {
        $haystack = strtolower($this->normalizeText($haystack));
        $needle = strtolower($this->normalizeText($needle));
        return $needle !== '' && strpos($haystack, $needle) !== false;
    }

    private function normalizeText(string $value): string
    {
        $value = strip_tags($value);
        $value = preg_replace('/[^A-Za-z0-9]+/', ' ', $value);
        return trim((string) preg_replace('/\s+/', ' ', $value));
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
