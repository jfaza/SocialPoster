<?php

namespace JavidFazaeli\SocialPoster\Service;

/**
 * Quality checks the SEO score cannot do: overlap with posts already on the
 * blog, and an AI editor's review of accuracy, freshness and sources.
 */
class ContentReview
{
    public const MODEL = 'gpt-6.1-terra';

    private string $table = 'socialposter_reviews';

    public function __construct(private OpenAiClient $openai)
    {
    }

    public function available(): bool
    {
        return ee()->db->table_exists($this->table);
    }

    /** The most recent stored review for a generation, or null. */
    public function latest(int $generationId): ?array
    {
        if ($generationId < 1 || ! $this->available()) {
            return null;
        }

        $row = ee()->db
            ->where('generation_id', $generationId)
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get($this->table)
            ->row_array();

        if (! $row) {
            return null;
        }

        $row['issues'] = json_decode((string) ($row['issues'] ?? '[]'), true) ?: [];
        return $row;
    }

    /** Runs the AI editor on a generated article and stores the result. */
    public function review(int $generationId): array
    {
        if (! $this->available()) {
            throw new \RuntimeException('The reviews table is missing. Run the SocialPoster add-on update.');
        }

        $generator = ee('socialposter:generator');
        $post = $generator->find($generationId);
        if (! $post) {
            throw new \RuntimeException('That generated post does not exist.');
        }

        $apiKey = trim((string) ($generator->getSettings()['api_key'] ?? ''));
        if ($apiKey === '') {
            throw new \RuntimeException('OpenAI API key is not configured.');
        }

        $result = $this->openai->reviewArticle($apiKey, self::MODEL, $this->articleText($post));
        $issues = array_values(array_filter((array) ($result['issues'] ?? []), 'is_array'));

        $row = [
            'generation_id' => $generationId,
            'model' => self::MODEL,
            'verdict' => (string) ($result['verdict'] ?? ''),
            'summary' => trim((string) ($result['summary'] ?? '')),
            'issues' => json_encode($issues, JSON_UNESCAPED_UNICODE),
            'created_at' => ee()->localize->now,
        ];
        ee()->db->insert($this->table, $row);

        $row['issues'] = $issues;
        return $row;
    }

    /**
     * Published posts and recent drafts whose topic overlaps this post,
     * using the generator's duplicate-topic matching.
     */
    public function duplicates(array $post, int $limit = 5): array
    {
        $generator = ee('socialposter:generator');
        $title = trim((string) ($post['title'] ?? ''));
        $id = (int) ($post['id'] ?? 0);
        if ($title === '') {
            return [];
        }

        $publishedFromThis = [];
        $publishedGenerations = [];
        if (ee()->db->table_exists('socialposter_published_blogs')) {
            foreach (ee()->db->select('generation_id, entry_id')->get('socialposter_published_blogs')->result_array() as $link) {
                $publishedGenerations[(int) $link['generation_id']] = true;
                if ((int) $link['generation_id'] === $id) {
                    $publishedFromThis[(int) $link['entry_id']] = true;
                }
            }
        }

        $matches = [];

        $entries = ee()->db
            ->select('t.entry_id, t.title, t.url_title')
            ->from('channel_titles t')
            ->join('channels c', 'c.channel_id = t.channel_id')
            ->where('c.channel_name', 'blog')
            ->where('t.status', 'open')
            ->order_by('t.entry_date', 'DESC')
            ->get()
            ->result_array();
        foreach ($entries as $entry) {
            if (isset($publishedFromThis[(int) $entry['entry_id']])) {
                continue;
            }
            $match = $generator->duplicateTopicMatchText($title, [['title' => (string) $entry['title']]]);
            if ($match) {
                $matches[] = [
                    'kind' => 'published',
                    'title' => (string) $entry['title'],
                    'url' => '/blog/article/' . $entry['url_title'],
                    'reason' => (string) ($match['reason'] ?? ''),
                ];
            }
        }

        $drafts = ee()->db
            ->select('id, title')
            ->where('id !=', $id)
            ->order_by('id', 'DESC')
            ->limit(60)
            ->get('socialposter_generations')
            ->result_array();
        foreach ($drafts as $draft) {
            if (isset($publishedGenerations[(int) $draft['id']]) || trim((string) $draft['title']) === '') {
                continue;
            }
            $match = $generator->duplicateTopicMatchText($title, [['title' => (string) $draft['title']]]);
            if ($match) {
                $matches[] = [
                    'kind' => 'draft',
                    'id' => (int) $draft['id'],
                    'title' => (string) $draft['title'],
                    'reason' => (string) ($match['reason'] ?? ''),
                ];
            }
        }

        return array_slice($matches, 0, $limit);
    }

    private function articleText(array $post): string
    {
        // Only what readers see; link fields are already in the body.
        return implode("\n\n", [
            'Title: ' . trim((string) ($post['title'] ?? '')),
            'Intro: ' . trim((string) ($post['intro_text'] ?? '')),
            "Article:\n" . trim((string) ($post['post_text'] ?? '')),
        ]);
    }
}
