<?php

namespace JavidFazaeli\SocialPoster\Service;

class NewsFeedService
{
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
