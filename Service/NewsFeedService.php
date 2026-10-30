<?php

namespace JavidFazaeli\SocialPoster\Service;

class NewsFeedService
{
    private const HOT_CACHE_TABLE = 'socialposter_hot_articles_cache';

    // -------------------------------------------------------------------------
    // Sources CRUD
    // -------------------------------------------------------------------------

    public static function categories(): array
    {
        return ['AI', 'Dev', 'Tech', 'Business', 'Design', 'Marketing', 'Science', 'Other'];
    }

    public function allSources(string $category = ''): array
    {
        if (! ee()->db->table_exists('socialposter_news_sources')) {
            return [];
        }

        $db = ee()->db->where('is_active', 1);
        if ($category !== '') {
            $db->where('category', $category);
        }

        return $db->order_by('category', 'ASC')
            ->order_by('name', 'ASC')
            ->get('socialposter_news_sources')
            ->result_array();
    }

    public function find(int $id): ?array
    {
        if (! ee()->db->table_exists('socialposter_news_sources')) {
            return null;
        }

        $row = ee()->db->where('id', $id)->get('socialposter_news_sources')->row_array();
        return $row ?: null;
    }

    public function addSource(string $name, string $feedUrl, string $category = ''): array
    {
        $name     = trim($name);
        $feedUrl  = trim($feedUrl);
        $category = trim($category);

        if ($name === '') {
            throw new \InvalidArgumentException('Source name is required.');
        }
        if ($feedUrl === '') {
            throw new \InvalidArgumentException('Feed URL is required.');
        }
        if (! filter_var($feedUrl, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('Feed URL must be a valid URL.');
        }
        if ($category !== '' && ! in_array($category, self::categories(), true)) {
            $category = 'Other';
        }

        if (! ee()->db->table_exists('socialposter_news_sources')) {
            throw new \RuntimeException('News sources table is missing. Run the addon migration.');
        }

        $record = [
            'name'       => $name,
            'category'   => $category,
            'feed_url'   => $feedUrl,
            'is_active'  => 1,
            'created_at' => ee()->localize->now,
        ];

        ee()->db->insert('socialposter_news_sources', $record);
        $record['id'] = (int) ee()->db->insert_id();

        return $record;
    }

    public function deleteSource(int $id): bool
    {
        $row = ee()->db->where('id', $id)->get('socialposter_news_sources')->row_array();
        if (! $row) {
            return false;
        }

        ee()->db->where('id', $id)->delete('socialposter_news_sources');
        return true;
    }

    // -------------------------------------------------------------------------
    // Feed fetching
    // -------------------------------------------------------------------------

    public function fetchArticles(int $sourceId, int $limit = 15): array
    {
        $source = $this->find($sourceId);
        if (! $source) {
            throw new \InvalidArgumentException('News source not found.');
        }

        $xml      = $this->fetchUrl((string) $source['feed_url']);
        $articles = $this->parseRss($xml, $limit);

        foreach ($articles as &$article) {
            $article['source_name'] = (string) $source['name'];
            $article['source_id']   = (int) $source['id'];
        }

        return $articles;
    }

    public function hotArticles(int $limit = 20): array
    {
        if (! ee()->db->table_exists(self::HOT_CACHE_TABLE)) {
            return [];
        }

        $rows = ee()->db
            ->order_by('hot_score', 'DESC')
            ->order_by('published_ts', 'DESC')
            ->limit(max(1, min(50, $limit)))
            ->get(self::HOT_CACHE_TABLE)
            ->result_array();

        return array_map([$this, 'normalizeHotArticle'], $rows);
    }

    public function hotCacheMeta(int $ttlSeconds = 3600): array
    {
        if (! ee()->db->table_exists(self::HOT_CACHE_TABLE)) {
            return [
                'exists' => false,
                'count' => 0,
                'cached_at' => 0,
                'age_seconds' => null,
                'is_stale' => true,
            ];
        }

        $row = ee()->db
            ->select('COUNT(*) AS count, MAX(cached_at) AS cached_at')
            ->get(self::HOT_CACHE_TABLE)
            ->row_array();

        $cachedAt = (int) ($row['cached_at'] ?? 0);
        $age = $cachedAt > 0 ? max(0, ee()->localize->now - $cachedAt) : null;

        return [
            'exists' => true,
            'count' => (int) ($row['count'] ?? 0),
            'cached_at' => $cachedAt,
            'age_seconds' => $age,
            'is_stale' => $cachedAt < 1 || ($age !== null && $age > $ttlSeconds),
        ];
    }

    public function refreshHotArticles(int $limitPerSource = 8, int $keep = 40): array
    {
        if (! ee()->db->table_exists(self::HOT_CACHE_TABLE)) {
            throw new \RuntimeException('Hot articles cache table is missing. Run the SocialPoster add-on update.');
        }

        $sources = $this->allSources();
        if (! $sources) {
            return ['articles' => [], 'errors' => [], 'meta' => $this->hotCacheMeta()];
        }

        $errors = [];
        $articles = [];

        foreach ($sources as $source) {
            $sourceId = (int) ($source['id'] ?? 0);
            if ($sourceId < 1) {
                continue;
            }

            try {
                foreach ($this->fetchArticles($sourceId, $limitPerSource) as $article) {
                    $article['source_name'] = (string) ($source['name'] ?? $article['source_name'] ?? '');
                    $article['source_id'] = $sourceId;
                    $article['category'] = (string) ($source['category'] ?? '');
                    $articles[] = $article;
                }
            } catch (\Throwable $e) {
                $errors[] = trim((string) ($source['name'] ?? 'Source ' . $sourceId)) . ': ' . $e->getMessage();
            }
        }

        $deduped = [];
        foreach ($articles as $article) {
            $url = trim((string) ($article['url'] ?? ''));
            $title = trim((string) ($article['title'] ?? ''));
            if ($url === '' || $title === '') {
                continue;
            }
            $hash = sha1(strtolower($url));
            if (! isset($deduped[$hash]) || $this->publishedTimestamp($article) > $this->publishedTimestamp($deduped[$hash])) {
                $article['url_hash'] = $hash;
                $deduped[$hash] = $article;
            }
        }

        $scored = [];
        foreach ($deduped as $article) {
            $score = $this->scoreHotArticle($article);
            $article['hot_score'] = $score['score'];
            $article['score_reasons'] = $score['reasons'];
            $article['published_ts'] = $this->publishedTimestamp($article);
            $scored[] = $article;
        }

        usort($scored, function ($a, $b) {
            return ($b['hot_score'] <=> $a['hot_score']) ?: (($b['published_ts'] ?? 0) <=> ($a['published_ts'] ?? 0));
        });

        $now = ee()->localize->now;
        $siteId = (int) ee()->config->item('site_id');

        if ($scored) {
            ee()->db->empty_table(self::HOT_CACHE_TABLE);
            foreach (array_slice($scored, 0, max(10, min(100, $keep))) as $article) {
                $this->upsertHotArticle($article, $siteId, $now);
            }
        }

        $this->trimHotCache(max(20, min(100, $keep)));

        return [
            'articles' => $this->hotArticles(20),
            'errors' => $errors,
            'meta' => $this->hotCacheMeta(),
        ];
    }

    // -------------------------------------------------------------------------
    // Internal: RSS parsing
    // -------------------------------------------------------------------------

    private function parseRss(string $xml, int $limit): array
    {
        $prev = libxml_use_internal_errors(true);
        $feed = simplexml_load_string($xml);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        if ($feed === false) {
            throw new \RuntimeException('Could not parse RSS feed. Ensure the URL points to a valid RSS/Atom feed.');
        }

        // RSS 2.0 items
        $items = $feed->channel->item ?? null;
        // Atom entries
        if (empty($items)) {
            $items = $feed->entry ?? null;
        }

        if (empty($items)) {
            return [];
        }

        $articles = [];

        foreach ($items as $item) {
            if (count($articles) >= $limit) {
                break;
            }

            $ns = $item->getNamespaces(true);

            $title = trim((string) ($item->title ?? ''));

            // Link: RSS uses <link>, Atom uses <link href="...">
            $url = trim((string) ($item->link ?? ''));
            if ($url === '' && isset($ns['atom'])) {
                $atom = $item->children($ns['atom']);
                if (! empty($atom->link)) {
                    $url = trim((string) ($atom->link->attributes()['href'] ?? ''));
                }
            }
            if ($url === '') {
                $url = trim((string) ($item->id ?? ''));
            }

            // Excerpt: <description> or <content:encoded>
            $excerpt = '';
            if (isset($item->description) && trim((string) $item->description) !== '') {
                $excerpt = strip_tags((string) $item->description);
            } elseif (isset($ns['content'])) {
                $content = $item->children($ns['content']);
                $excerpt = strip_tags((string) ($content->encoded ?? ''));
            } elseif (isset($item->summary)) {
                $excerpt = strip_tags((string) $item->summary);
            }
            $excerpt = trim(mb_strimwidth(trim($excerpt), 0, 400, '…'));

            // Image: try <enclosure>, <media:content>, <media:thumbnail>
            $imageUrl = $this->extractFeedImage($item, $ns);

            // Published date
            $pubDate = '';
            if (isset($item->pubDate)) {
                $pubDate = trim((string) $item->pubDate);
            } elseif (isset($item->published)) {
                $pubDate = trim((string) $item->published);
            } elseif (isset($item->updated)) {
                $pubDate = trim((string) $item->updated);
            }

            if ($title === '' || $url === '') {
                continue;
            }

            $articles[] = [
                'title'        => $title,
                'url'          => $url,
                'excerpt'      => $excerpt,
                'image_url'    => $imageUrl,
                'published_at' => $pubDate,
                'source_name'  => '',
                'source_id'    => 0,
            ];
        }

        return $articles;
    }

    private function extractFeedImage(\SimpleXMLElement $item, array $ns): string
    {
        // <enclosure type="image/…">
        if (isset($item->enclosure)) {
            $type = (string) ($item->enclosure->attributes()['type'] ?? '');
            if (str_starts_with($type, 'image/')) {
                $url = trim((string) ($item->enclosure->attributes()['url'] ?? ''));
                if ($url !== '') {
                    return $url;
                }
            }
        }

        // <media:content> / <media:thumbnail>
        foreach (['media', 'media:content'] as $nsKey) {
            if (isset($ns[$nsKey])) {
                $media = $item->children($ns[$nsKey]);
                if (isset($media->content)) {
                    $url = trim((string) ($media->content->attributes()['url'] ?? ''));
                    if ($url !== '') {
                        return $url;
                    }
                }
                if (isset($media->thumbnail)) {
                    $url = trim((string) ($media->thumbnail->attributes()['url'] ?? ''));
                    if ($url !== '') {
                        return $url;
                    }
                }
            }
        }

        return '';
    }

    private function normalizeHotArticle(array $row): array
    {
        return [
            'title' => (string) ($row['title'] ?? ''),
            'url' => (string) ($row['url'] ?? ''),
            'excerpt' => (string) ($row['excerpt'] ?? ''),
            'image_url' => (string) ($row['image_url'] ?? ''),
            'published_at' => (string) ($row['published_at'] ?? ''),
            'source_name' => (string) ($row['source_name'] ?? ''),
            'source_id' => (int) ($row['source_id'] ?? 0),
            'category' => (string) ($row['category'] ?? ''),
            'hot_score' => (float) ($row['hot_score'] ?? 0),
            'score_reasons' => $this->decodeReasons((string) ($row['score_reasons'] ?? '[]')),
            'cached_at' => (int) ($row['cached_at'] ?? 0),
        ];
    }

    private function scoreHotArticle(array $article): array
    {
        $title = strtolower((string) ($article['title'] ?? ''));
        $excerpt = strtolower((string) ($article['excerpt'] ?? ''));
        $haystack = $title . ' ' . $excerpt;
        $publishedTs = $this->publishedTimestamp($article);
        $ageHours = $publishedTs > 0 ? max(0, (ee()->localize->now - $publishedTs) / 3600) : 999;

        $score = 0.0;
        $reasons = [];

        if ($ageHours <= 6) {
            $score += 45;
            $reasons[] = 'Very recent';
        } elseif ($ageHours <= 24) {
            $score += 32;
            $reasons[] = 'Recent';
        } elseif ($ageHours <= 72) {
            $score += 18;
            $reasons[] = 'This week';
        }

        $keywordWeights = [
            'openai' => 16,
            'anthropic' => 14,
            'google' => 9,
            'meta' => 8,
            'apple' => 8,
            'microsoft' => 8,
            'nvidia' => 10,
            'model' => 7,
            'launch' => 10,
            'release' => 9,
            'benchmark' => 9,
            'security' => 8,
            'regulation' => 8,
            'funding' => 6,
            'acquisition' => 8,
            'agent' => 7,
            'ai' => 5,
            'developer' => 5,
            'startup' => 5,
        ];

        foreach ($keywordWeights as $keyword => $weight) {
            if (str_contains($haystack, $keyword)) {
                $score += $weight;
                if (count($reasons) < 4) {
                    $reasons[] = ucfirst($keyword);
                }
            }
        }

        if (trim((string) ($article['image_url'] ?? '')) !== '') {
            $score += 4;
        }

        if (trim((string) ($article['category'] ?? '')) === 'AI') {
            $score += 6;
        }

        return [
            'score' => round($score, 2),
            'reasons' => array_values(array_unique($reasons)),
        ];
    }

    private function publishedTimestamp(array $article): int
    {
        if (isset($article['published_ts']) && (int) $article['published_ts'] > 0) {
            return (int) $article['published_ts'];
        }

        $raw = trim((string) ($article['published_at'] ?? ''));
        if ($raw === '') {
            return 0;
        }

        $ts = strtotime($raw);
        return $ts !== false ? (int) $ts : 0;
    }

    private function upsertHotArticle(array $article, int $siteId, int $now): void
    {
        $hash = (string) ($article['url_hash'] ?? sha1(strtolower((string) ($article['url'] ?? ''))));
        $record = [
            'site_id' => $siteId,
            'source_id' => (int) ($article['source_id'] ?? 0),
            'source_name' => mb_strimwidth((string) ($article['source_name'] ?? ''), 0, 255, ''),
            'category' => mb_strimwidth((string) ($article['category'] ?? ''), 0, 80, ''),
            'title' => mb_strimwidth((string) ($article['title'] ?? ''), 0, 500, ''),
            'url' => (string) ($article['url'] ?? ''),
            'url_hash' => $hash,
            'excerpt' => (string) ($article['excerpt'] ?? ''),
            'image_url' => (string) ($article['image_url'] ?? ''),
            'published_at' => mb_strimwidth((string) ($article['published_at'] ?? ''), 0, 120, ''),
            'published_ts' => (int) ($article['published_ts'] ?? 0),
            'hot_score' => (float) ($article['hot_score'] ?? 0),
            'score_reasons' => json_encode($article['score_reasons'] ?? [], JSON_UNESCAPED_UNICODE),
            'cached_at' => $now,
        ];

        $existing = ee()->db->where('url_hash', $hash)->get(self::HOT_CACHE_TABLE)->row_array();
        if ($existing) {
            ee()->db->where('id', (int) $existing['id'])->update(self::HOT_CACHE_TABLE, $record);
            return;
        }

        ee()->db->insert(self::HOT_CACHE_TABLE, $record);
    }

    private function trimHotCache(int $keep): void
    {
        $rows = ee()->db
            ->select('id')
            ->order_by('hot_score', 'DESC')
            ->order_by('published_ts', 'DESC')
            ->get(self::HOT_CACHE_TABLE)
            ->result_array();

        $keepIds = array_map(fn($row) => (int) $row['id'], array_slice($rows, 0, $keep));
        if (! $keepIds) {
            return;
        }

        ee()->db->where_not_in('id', $keepIds)->delete(self::HOT_CACHE_TABLE);
    }

    private function decodeReasons(string $raw): array
    {
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? array_values(array_filter($decoded, 'is_string')) : [];
    }

    // -------------------------------------------------------------------------
    // Internal: HTTP
    // -------------------------------------------------------------------------

    private function fetchUrl(string $url): string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_USERAGENT      => 'SocialPoster/1.0 (+https://fazaeli.dev)',
            CURLOPT_HTTPHEADER     => [
                'Accept: application/rss+xml, application/atom+xml, application/xml, text/xml, */*',
            ],
        ]);

        $body   = curl_exec($ch);
        $error  = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false || $body === '') {
            throw new \RuntimeException('Could not fetch feed: ' . ($error ?: 'empty response'));
        }
        if ($status < 200 || $status >= 300) {
            throw new \RuntimeException('Feed returned HTTP ' . $status . '. Check the URL is a valid RSS feed.');
        }

        return (string) $body;
    }
}
