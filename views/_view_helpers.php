<?php

return new class {
    public function templateTitle(array $templateMeta, $templateId): string
    {
        $template = $templateMeta[(int) $templateId] ?? null;
        if (! is_array($template)) {
            return '';
        }

        $title = trim((string) ($template['title'] ?? ''));
        if ($title !== '') {
            return $title;
        }

        return trim((string) ($template['platform'] ?? ''));
    }

    public function templatePlatformLabel(array $templateMeta, $templateId): string
    {
        $template = $templateMeta[(int) $templateId] ?? null;
        if (! is_array($template)) {
            return '';
        }

        $platform = trim((string) ($template['platform'] ?? ''));
        if ($platform !== '') {
            return $platform;
        }

        return trim((string) ($template['title'] ?? ''));
    }

    public function templatePlatform(array $templateMeta, $templateId): string
    {
        $template = $templateMeta[(int) $templateId] ?? null;
        if (! is_array($template)) {
            return '';
        }

        return trim((string) ($template['platform'] ?? ''));
    }

    public function templateContentType(array $templateMeta, $templateId): string
    {
        $template = $templateMeta[(int) $templateId] ?? null;
        if (! is_array($template)) {
            return '';
        }

        return trim((string) ($template['content_type'] ?? ''));
    }

    public function isWebsiteTemplate(array $templateMeta, $templateId): bool
    {
        return strtolower($this->templatePlatform($templateMeta, $templateId)) === 'website';
    }

    public function isFacebookTemplate(array $templateMeta, $templateId): bool
    {
        return strtolower($this->templatePlatform($templateMeta, $templateId)) === 'facebook';
    }

    public function socialQuality(string $text): array
    {
        $text = trim((string) $text);
        $plain = trim((string) preg_replace('/\s+/', ' ', $text));
        $firstLine = trim((string) strtok($text, "\n"));
        $paragraphs = array_values(array_filter(array_map('trim', preg_split("/\n\s*\n/", $text) ?: [])));
        $sentenceCount = preg_match_all('/[.!?]+/', $plain) ?: 0;
        $questionCount = preg_match_all('/\?/', $plain) ?: 0;
        $lineBreakCount = substr_count($text, "\n");
        $charCount = mb_strlen($plain);

        $checks = [
            [
                'label' => 'Opening hook',
                'passed' => $firstLine !== '' && mb_strlen($firstLine) <= 110,
                'detail' => $firstLine !== '' ? $firstLine : 'No clear opening line found.',
                'recommendation' => 'Lead with a short first line that makes someone stop scrolling.',
            ],
            [
                'label' => 'Skimmable length',
                'passed' => $charCount >= 60 && $charCount <= 650,
                'detail' => $charCount . ' characters',
                'recommendation' => 'Keep Facebook copy brief enough to skim without feeling abrupt.',
            ],
            [
                'label' => 'Readable shape',
                'passed' => count($paragraphs) >= 2 || $lineBreakCount >= 1,
                'detail' => count($paragraphs) > 1 ? count($paragraphs) . ' short sections' : 'Mostly one block',
                'recommendation' => 'Use short paragraphs or compact line breaks instead of one dense block.',
            ],
            [
                'label' => 'Comment CTA',
                'passed' => $questionCount > 0 || preg_match('/\b(comment|thoughts|agree|disagree|what do you think|have you seen|share)\b/i', $plain),
                'detail' => $questionCount > 0 ? 'Ends with a question or discussion cue' : 'No obvious discussion cue',
                'recommendation' => 'Finish with a question or light invitation to respond.',
            ],
            [
                'label' => 'Single clear point',
                'passed' => $sentenceCount > 0 && $sentenceCount <= 8,
                'detail' => max(1, (int) $sentenceCount) . ' sentence markers',
                'recommendation' => 'Keep the post centered on one clear point instead of stacking multiple ideas.',
            ],
        ];

        $score = 0;
        foreach ($checks as $check) {
            $score += ! empty($check['passed']) ? 20 : 0;
        }

        return [
            'score' => $score,
            'grade' => $score >= 80 ? 'Strong' : ($score >= 60 ? 'Solid' : 'Needs tightening'),
            'summary' => $score >= 80
                ? 'This reads like a concise, comment-friendly Facebook post.'
                : ($score >= 60
                    ? 'This is close; a tighter hook or CTA would make it stronger in-feed.'
                    : 'This likely needs a clearer hook, shorter shape, or better comment prompt.'),
            'char_count' => $charCount,
            'checks' => $checks,
        ];
    }

    public function templateBadge(array $templateMeta, $templateId, callable $escape): string
    {
        $label = $this->templatePlatformLabel($templateMeta, $templateId);
        if ($label === '') {
            return '';
        }

        $class = strtolower(preg_replace('/[^a-z0-9]+/', '-', $label));

        return '<span class="sp-template-badge sp-template-badge--'
            . $escape(trim((string) $class, '-'))
            . '">'
            . $escape($label)
            . '</span>';
    }

    public function isInstagramTemplate(array $templateMeta, $templateId): bool
    {
        $template = $templateMeta[(int) $templateId] ?? null;
        if (! is_array($template)) {
            return false;
        }

        $platform = strtolower(trim((string) ($template['platform'] ?? '')));
        $title = strtolower(trim((string) ($template['title'] ?? '')));

        return $platform === 'instagram' || strpos($title, 'instagram carousel') !== false;
    }

    public function splitInstagramPost($text): array
    {
        $parts = preg_split('/\n\s*(?:#{1,6}\s*)?Instagram Caption:?\s*\n/i', (string) $text, 2);
        if (is_array($parts) && count($parts) === 2) {
            return [trim($parts[0]), trim($parts[1])];
        }

        return [trim((string) $text), ''];
    }

    public function itemUrl(string $pattern, string $fallbackBaseUrl, $id): string
    {
        if ($pattern !== '' && strpos($pattern, '__ID__') !== false) {
            return str_replace('__ID__', (string) (int) $id, $pattern);
        }

        return rtrim($fallbackBaseUrl, '/') . '/' . (int) $id;
    }

    public function statusBadge(string $label, string $variant, callable $escape): string
    {
        $variant = preg_replace('/[^a-z0-9\-]+/i', '-', strtolower($variant)) ?: 'neutral';

        return '<span class="sp-status-badge sp-status-badge--'
            . $escape($variant)
            . '">'
            . $escape($label)
            . '</span>';
    }

    public function excerpt(string $text, int $max = 160, string $suffix = '...'): string
    {
        $text = trim((string) (preg_replace('/\s+/', ' ', $text) ?? ''));
        if ($text === '' || mb_strlen($text) <= $max) {
            return $text;
        }

        return mb_substr($text, 0, max(1, $max - mb_strlen($suffix))) . $suffix;
    }
};
