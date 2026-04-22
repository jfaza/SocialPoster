<?php

namespace JavidFazaeli\SocialPoster\Service;

class Publisher
{
    private SocialPostGenerator $generator;

    public function __construct(?SocialPostGenerator $generator = null)
    {
        $this->generator = $generator ?: new SocialPostGenerator();
    }

    public function targets(): array
    {
        return [
            'blog' => [
                'label' => 'Blog',
                'status' => 'ready',
                'description' => 'Publish a generated post into the ExpressionEngine blog channel.',
            ],
            'medium' => [
                'label' => 'Medium',
                'status' => 'planned',
                'description' => 'External publishing target planned for a future integration.',
            ],
            'x' => [
                'label' => 'X',
                'status' => 'planned',
                'description' => 'Short-form social publishing target planned for a future integration.',
            ],
            'instagram' => [
                'label' => 'Instagram',
                'status' => 'planned',
                'description' => 'Image-first social publishing target planned for a future integration.',
            ],
        ];
    }

    public function statusOptions(): array
    {
        return [
            'open' => 'Open',
            'closed' => 'Closed',
        ];
    }

    public function categoryOptions(): array
    {
        $channel = $this->blogChannel();
        if (! $channel) {
            return [];
        }

        $options = [];
        foreach ($channel->getCategoryGroups() as $group) {
            foreach ($group->Categories as $category) {
                $label = trim((string) $group->group_name) !== ''
                    ? (string) $group->group_name . ': ' . (string) $category->cat_name
                    : (string) $category->cat_name;
                $options[(int) $category->cat_id] = $label;
            }
        }

        asort($options);
        return $options;
    }

    public function publishToBlog(int $generationId, array $input = []): array
    {
        $post = $this->generator->find($generationId);
        if (! $post) {
            throw new \InvalidArgumentException('Generated post not found.');
        }

        $channel = $this->blogChannel();

        if (! $channel) {
            throw new \RuntimeException('Blog channel was not found.');
        }

        $title = trim((string) ($input['title'] ?? '')) ?: (string) ($post['title'] ?? '');
        if ($title === '') {
            throw new \InvalidArgumentException('A blog title is required.');
        }

        $status = (string) ($input['status'] ?? 'open');
        if (! array_key_exists($status, $this->statusOptions())) {
            $status = 'open';
        }

        $this->validatePublishableBlogPost($post);

        $entry = ee('Model')->make('ChannelEntry');
        $entry->Channel = $channel;
        $includeImage = ! empty($input['include_image']);
        $imageField = $includeImage ? $this->blogImageField($channel) : null;
        if ($includeImage && $imageField === null) {
            throw new \RuntimeException('Blog image field was not found.');
        }

        $imageFile = $imageField !== null ? $this->imageFileForPost($post, $imageField) : null;
        if ($includeImage && $imageFile === null) {
            throw new \RuntimeException('Generated image file is missing. Regenerate the post image or publish without the image checkbox.');
        }

        $imageToken = $imageFile ? $this->fileFieldToken($imageFile) : '';

        $entryData = [
            'site_id' => (int) ee()->config->item('site_id'),
            'channel_id' => (int) $channel->channel_id,
            'author_id' => $this->memberId(),
            'ip_address' => ee()->input->ip_address(),
            'title' => $title,
            'url_title' => $this->uniqueUrlTitle($title, (int) $channel->channel_id),
            'status' => $status,
            'entry_date' => ee()->localize->now,
            'edit_date' => ee()->localize->now,
            'versioning_enabled' => $channel->enable_versioning ?: 'n',
            'allow_comments' => 'y',
            'sticky' => 'n',
            'field_id_2' => $this->blogContent($post),
            'field_id_6' => $title,
            'field_id_7' => $this->seoDescription($post),
        ];

        if ($imageToken !== '' && $imageField !== null && $imageField->field_type === 'file') {
            $entryData['field_id_' . (int) $imageField->field_id] = $imageToken;
        }

        $entry->set($entryData);

        $categoryId = $this->publishCategoryId($channel, $post, (int) ($input['category_id'] ?? 0));
        if ($categoryId > 0) {
            $entry->Categories = ee('Model')->get('Category')->filter('cat_id', $categoryId)->all();
        }

        $result = $entry->validate();
        if (! $result->isValid()) {
            throw new \RuntimeException($this->validationMessage($result->getAllErrors()));
        }

        $entry->save();

        if ($imageFile && $imageField !== null && $imageField->field_type === 'grid') {
            $this->saveGridImage((int) $entry->entry_id, $imageField, $imageFile, $title);
        }

        if ($categoryId > 0) {
            $this->ensureCategoryAssigned((int) $entry->entry_id, $categoryId);
        }

        $this->recordPublishedBlog($generationId, $entry);

        return [
            'entry_id' => (int) $entry->entry_id,
            'title' => $title,
            'status' => $status,
            'url' => ee()->functions->create_url('blog/article/' . $entry->url_title, false),
            'edit_url' => ee('CP/URL')->make('publish/edit/entry/' . (int) $entry->entry_id)->compile(),
        ];
    }

    public function publishedBlogs(int $limit = 50): array
    {
        if (! ee()->db->table_exists('socialposter_published_blogs')) {
            return [];
        }

        $rows = ee()->db
            ->order_by('created_at', 'DESC')
            ->limit($limit)
            ->get('socialposter_published_blogs')
            ->result_array();

        $blogs = [];
        foreach ($rows as $row) {
            $entry = ee('Model')->get('ChannelEntry', (int) $row['entry_id'])->first();
            if (! $entry) {
                ee()->db->where('id', (int) $row['id'])->delete('socialposter_published_blogs');
                continue;
            }

            $blogs[] = [
                'id' => (int) $row['id'],
                'generation_id' => (int) $row['generation_id'],
                'entry_id' => (int) $entry->entry_id,
                'title' => (string) $entry->title,
                'status' => (string) $entry->status,
                'created_at' => (int) $row['created_at'],
                'url' => ee()->functions->create_url('blog/article/' . $entry->url_title, false),
                'edit_url' => ee('CP/URL')->make('publish/edit/entry/' . (int) $entry->entry_id)->compile(),
            ];
        }

        return $blogs;
    }

    public function deletePublishedBlog(int $publishedBlogId): bool
    {
        if (! ee()->db->table_exists('socialposter_published_blogs')) {
            throw new \RuntimeException('Published blog tracking table is missing. Update the add-on.');
        }

        $row = ee()->db
            ->where('id', $publishedBlogId)
            ->get('socialposter_published_blogs')
            ->row_array();

        if (! $row) {
            throw new \InvalidArgumentException('Generated blog entry was not found.');
        }

        $entry = ee('Model')->get('ChannelEntry', (int) $row['entry_id'])->first();
        if ($entry) {
            $entry->delete();
        }

        ee()->db->where('id', $publishedBlogId)->delete('socialposter_published_blogs');
        return true;
    }

    private function recordPublishedBlog(int $generationId, $entry): void
    {
        if (! ee()->db->table_exists('socialposter_published_blogs')) {
            return;
        }

        ee()->db->insert('socialposter_published_blogs', [
            'generation_id' => $generationId,
            'entry_id' => (int) $entry->entry_id,
            'title' => (string) $entry->title,
            'status' => (string) $entry->status,
            'url_title' => (string) $entry->url_title,
            'created_at' => ee()->localize->now,
        ]);
    }

    private function blogContent(array $post): string
    {
        $html = [];
        $tocItems = $this->cleanList((array) ($post['table_of_contents'] ?? []));
        $tocAnchors = $this->tocAnchors($tocItems);

        if (! empty($post['intro_text'])) {
            $html[] = $this->markdownToHtml((string) $post['intro_text']);
        }

        if ($tocItems) {
            $html[] = $this->tableOfContentsHtml($tocItems, $tocAnchors);
        }

        if (! empty($post['post_text'])) {
            $html[] = $this->addHeadingAnchors($this->markdownToHtml((string) $post['post_text']), $tocAnchors);
        }

        $links = [];
        if (! empty($post['internal_link'])) {
            $links[] = 'Internal: ' . (string) $post['internal_link'];
        }
        if (! empty($post['external_link'])) {
            $links[] = 'External: ' . (string) $post['external_link'];
        }
        if ($links) {
            $html[] = $this->linksHtml($links);
        }

        if (! empty($post['recommended_topics'])) {
            $html[] = $this->markdownToHtml("## Recommended Topics\n\n" . $this->markdownList((array) $post['recommended_topics']));
        }

        if (! empty($post['hashtags'])) {
            $html[] = $this->hashtagsHtml((array) $post['hashtags']);
        }

        return implode("\n\n", array_filter($html));
    }

    private function blogChannel()
    {
        return ee('Model')->get('Channel')
            ->filter('channel_name', 'blog')
            ->with('CategoryGroups')
            ->first();
    }

    private function publishCategoryId($channel, array $post, int $requestedCategoryId): int
    {
        $allowed = array_keys($this->categoryOptions());
        $allowed = array_map('intval', $allowed);

        if ($requestedCategoryId > 0 && in_array($requestedCategoryId, $allowed, true)) {
            return $requestedCategoryId;
        }

        $category = trim((string) ($post['category'] ?? ''));
        if ($category === '') {
            return 0;
        }

        $categoryKey = $this->categoryKey($category);
        foreach ($channel->getCategoryGroups() as $group) {
            foreach ($group->Categories as $candidate) {
                if (
                    $this->categoryKey((string) $candidate->cat_name) === $categoryKey
                    || $this->categoryKey((string) $candidate->cat_url_title) === $categoryKey
                ) {
                    return (int) $candidate->cat_id;
                }
            }
        }

        return 0;
    }

    private function categoryKey(string $value): string
    {
        $value = strtolower(strip_tags($value));
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value);
        return trim((string) preg_replace('/\s+/', ' ', $value));
    }

    private function ensureCategoryAssigned(int $entryId, int $categoryId): void
    {
        if ($entryId < 1 || $categoryId < 1 || ! ee()->db->table_exists('category_posts')) {
            return;
        }

        $exists = ee()->db
            ->where('entry_id', $entryId)
            ->where('cat_id', $categoryId)
            ->count_all_results('category_posts') > 0;

        if (! $exists) {
            ee()->db->insert('category_posts', [
                'entry_id' => $entryId,
                'cat_id' => $categoryId,
            ]);
        }
    }

    private function validatePublishableBlogPost(array $post): void
    {
        $tocItems = $this->cleanList((array) ($post['table_of_contents'] ?? []));
        if (! $tocItems) {
            return;
        }

        $postText = trim((string) ($post['post_text'] ?? ''));
        $headings = $this->markdownHeadings($postText);
        if (count($headings) < count($tocItems)) {
            throw new \RuntimeException('Generated post is not publishable: the table of contents has no matching article sections. Regenerate this post.');
        }

        $missing = [];
        foreach ($tocItems as $item) {
            if (! in_array($this->headingKey($item), $headings, true)) {
                $missing[] = $item;
            }
        }

        if ($missing) {
            throw new \RuntimeException('Generated post is not publishable: missing article sections for ' . implode(', ', $missing) . '. Regenerate this post.');
        }
    }

    private function cleanList(array $items): array
    {
        $clean = [];

        foreach ($items as $item) {
            $item = trim((string) $item);
            if ($item !== '') {
                $clean[] = preg_replace('/\s+/', ' ', $item);
            }
        }

        return $clean;
    }

    private function markdownList(array $items): string
    {
        $lines = [];

        foreach ($this->cleanList($items) as $item) {
            $lines[] = '- ' . $item;
        }

        return implode("\n", $lines);
    }

    private function hashtagsHtml(array $hashtags): string
    {
        $tags = [];

        foreach ($this->cleanList($hashtags) as $tag) {
            $tag = ltrim($tag, '#');
            $tag = preg_replace('/[^A-Za-z0-9_]+/', '', $tag);
            if ($tag !== '') {
                $tags[] = '#' . $tag;
            }
        }

        if (! $tags) {
            return '';
        }

        return '<p class="socialposter-hashtags">' . $this->escapeHtml(implode(' ', array_unique($tags))) . '</p>';
    }

    private function markdownToHtml(string $markdown): string
    {
        ee()->load->library('typography');

        $markdown = $this->linkifyMarkdownUrls($this->preserveMarkdownLineBreaks($markdown));

        return trim((string) ee()->typography->markdown($markdown, [
            'smartypants' => false,
            'no_markup' => true,
        ]));
    }

    private function preserveMarkdownLineBreaks(string $markdown): string
    {
        return preg_replace_callback('/(?<!  )\n(?!\n|[ \t]*(?:[-*+]\s+|\d+\.\s+|#{1,6}\s+|>|\|))/', function ($match) {
            return "  \n";
        }, $markdown);
    }

    private function linkifyMarkdownUrls(string $markdown): string
    {
        return preg_replace_callback('~(?<!\[)(?<!\]\()(?<!["\'=])\bhttps?://[^\s<>()]+~i', function ($match) {
            $url = rtrim($match[0], '.,;:!?)]');
            $suffix = substr($match[0], strlen($url));

            return '[' . $url . '](' . $url . ')' . $suffix;
        }, $markdown);
    }

    private function tableOfContentsHtml(array $items, array $anchors): string
    {
        $links = [];

        foreach ($items as $item) {
            $anchor = $anchors[$item] ?? $this->slug($item);
            $links[] = '<li><a href="#' . $this->escapeAttr($anchor) . '">' . $this->escapeHtml($item) . '</a></li>';
        }

        return '<h2 id="table-of-contents">Table of Contents</h2>' . "\n" . '<ul>' . "\n" . implode("\n", $links) . "\n" . '</ul>';
    }

    private function linksHtml(array $links): string
    {
        $items = [];

        foreach ($links as $link) {
            [$label, $url] = $this->splitLinkLabel($link);
            if ($url === '') {
                $items[] = '<li>' . $this->escapeHtml($label) . '</li>';
                continue;
            }

            $linkText = strtolower($label) === 'internal' ? $this->internalLinkTitle($url) : '';
            $items[] = '<li>' . $this->escapeHtml($label) . ': <a href="' . $this->escapeAttr($this->href($url)) . '" target="_blank" rel="noopener">' . $this->escapeHtml($linkText ?: $url) . '</a></li>';
        }

        return '<h2 id="links">Links</h2>' . "\n" . '<ul>' . "\n" . implode("\n", $items) . "\n" . '</ul>';
    }

    private function internalLinkTitle(string $url): string
    {
        $urlTitle = $this->blogUrlTitleFromUrl($url);
        $channel = $this->blogChannel();
        if ($urlTitle === '' || ! $channel) {
            return '';
        }

        $row = ee()->db
            ->select('title')
            ->from('channel_titles')
            ->where('channel_id', (int) $channel->channel_id)
            ->where('url_title', $urlTitle)
            ->limit(1)
            ->get()
            ->row_array();

        return trim((string) ($row['title'] ?? ''));
    }

    private function blogUrlTitleFromUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        $path = strpos($url, '/') === 0 ? $url : (string) parse_url($url, PHP_URL_PATH);
        if ($path === '') {
            return '';
        }

        if (! preg_match('~/blog/article/([^/?#]+)~', $path, $match)) {
            return '';
        }

        return trim((string) $match[1]);
    }

    private function splitLinkLabel(string $link): array
    {
        $parts = explode(':', $link, 2);
        if (count($parts) !== 2) {
            return [$link, $this->firstLinkTarget($link)];
        }

        return [trim($parts[0]), $this->firstLinkTarget($parts[1]) ?: trim($parts[1])];
    }

    private function firstLinkTarget(string $value): string
    {
        if (preg_match('~https?://[^\s<>()]+~i', $value, $match)) {
            return rtrim($match[0], '.,;:!?)]');
        }

        if (preg_match('~(?<!\S)/[^\s<>()]+~', $value, $match)) {
            return rtrim($match[0], '.,;:!?)]');
        }

        return '';
    }

    private function href(string $target): string
    {
        if (preg_match('~^https?://~i', $target) || strpos($target, '/') === 0 || strpos($target, '#') === 0) {
            return $target;
        }

        return 'https://' . $target;
    }

    private function tocAnchors(array $items): array
    {
        $anchors = [];
        $used = [];

        foreach ($items as $item) {
            $slug = $this->uniqueSlug($this->slug($item), $used);
            $anchors[$item] = $slug;
        }

        return $anchors;
    }

    private function addHeadingAnchors(string $html, array $tocAnchors): string
    {
        $tocSlugMap = array_flip($tocAnchors);
        $used = [];

        return preg_replace_callback('/<h([2-4])([^>]*)>(.*?)<\/h\1>/is', function ($match) use ($tocAnchors, $tocSlugMap, &$used) {
            if (preg_match('/\sid=(["\']).*?\1/i', $match[2])) {
                return $match[0];
            }

            $text = trim(html_entity_decode(strip_tags($match[3]), ENT_QUOTES, 'UTF-8'));
            $slug = $this->slug($text);
            $id = $tocAnchors[$text] ?? (isset($tocSlugMap[$slug]) ? $slug : $this->uniqueSlug($slug, $used));
            $used[$id] = true;

            return '<h' . $match[1] . $match[2] . ' id="' . $this->escapeAttr($id) . '">' . $match[3] . '</h' . $match[1] . '>';
        }, $html);
    }

    private function uniqueSlug(string $slug, array &$used): string
    {
        $slug = $slug ?: 'section';
        $candidate = $slug;
        $i = 2;

        while (isset($used[$candidate])) {
            $candidate = $slug . '-' . $i++;
        }

        $used[$candidate] = true;
        return $candidate;
    }

    private function slug(string $value): string
    {
        $slug = ee('Format')->make('Text', strip_tags($value))->urlSlug()->compile();
        return trim((string) $slug, '-_.') ?: 'section';
    }

    private function markdownHeadings(string $markdown): array
    {
        preg_match_all('/^#{2,4}\s+(.+)$/m', $markdown, $matches);

        return array_values(array_unique(array_map(
            fn($heading) => $this->headingKey((string) $heading),
            $matches[1] ?? []
        )));
    }

    private function headingKey(string $value): string
    {
        $value = strtolower(strip_tags($value));
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value);
        return trim((string) preg_replace('/\s+/', ' ', $value));
    }

    private function imageFileToken(array $post): string
    {
        $path = $this->localImagePath($post);
        if ($path === '') {
            return '';
        }

        $file = $this->registeredFileForPath($path);
        if ($file && (int) $file->file_id > 0) {
            return $this->fileFieldToken($file);
        }

        $destination = $this->uploadDestinationForPath($path);
        if ($destination) {
            $relativePath = ltrim(str_replace('\\', '/', substr($path, strlen($this->uploadDestinationPath($destination)))), '/');
            if ($relativePath !== '') {
                return '{filedir_' . (int) $destination->id . '}' . $relativePath;
            }
        }

        return '';
    }

    private function imageFileForPost(array $post, $field = null)
    {
        $path = $this->localImagePath($post);
        if ($path === '') {
            return null;
        }

        $destination = $this->preferredImageDestination($field);
        if ($destination) {
            $path = $this->copyImageToDestination($path, $destination);
        }

        $file = $this->registeredFileForPath($path);
        return ($file && (int) $file->file_id > 0) ? $file : null;
    }

    private function fileFieldToken($file): string
    {
        $relativePath = method_exists($file, 'getSubfoldersPath')
            ? $file->getSubfoldersPath() . (string) $file->file_name
            : (string) $file->file_name;

        return '{filedir_' . (int) $file->upload_location_id . '}' . ltrim(str_replace('\\', '/', $relativePath), '/');
    }

    private function localImagePath(array $post): string
    {
        $imagePath = trim((string) ($post['image_path'] ?? ''));
        if ($imagePath !== '') {
            $path = FCPATH . ltrim($imagePath, '/');
            return is_file($path) ? $path : '';
        }

        $imageUrl = trim((string) ($post['image_url'] ?? ''));
        if ($imageUrl === '') {
            return '';
        }

        $urlPath = (string) parse_url($imageUrl, PHP_URL_PATH);
        if ($urlPath === '') {
            return '';
        }

        $path = FCPATH . ltrim($urlPath, '/');
        return is_file($path) ? $path : '';
    }

    private function registeredFileForPath(string $path)
    {
        $destination = $this->uploadDestinationForPath($path);
        if (! $destination) {
            return null;
        }

        $relativePath = ltrim(str_replace('\\', '/', substr($path, strlen($this->uploadDestinationPath($destination)))), '/');
        $file = $destination->getFileByPath($relativePath);
        if ($file && $file->model_type === 'File') {
            return $file;
        }

        $this->syncRelativeFolders($destination, $relativePath);

        $errors = $destination->syncFiles([$relativePath]);
        if ($errors !== true) {
            return null;
        }

        $file = $destination->getFileByPath($relativePath);
        return ($file && $file->model_type === 'File') ? $file : null;
    }

    private function syncRelativeFolders($destination, string $relativePath): void
    {
        $parts = explode('/', trim($relativePath, '/'));
        array_pop($parts);

        $folder = '';
        foreach ($parts as $part) {
            $folder = trim($folder . '/' . $part, '/');
            if ($folder !== '' && ! $destination->getFileByPath($folder)) {
                $destination->syncFiles([$folder]);
            }
        }
    }

    private function uploadDestinationForPath(string $path)
    {
        $path = rtrim(str_replace('\\', '/', $path), '/') . '/';
        $matches = [];

        foreach (ee('Model')->get('UploadDestination')->filter('adapter', 'local')->all() as $destination) {
            $destinationPath = $this->uploadDestinationPath($destination);
            if ($destinationPath !== '' && strpos($path, $destinationPath) === 0) {
                $matches[$destinationPath] = $destination;
            }
        }

        if (! $matches) {
            return null;
        }

        uksort($matches, fn($a, $b) => strlen($b) <=> strlen($a));
        return reset($matches);
    }

    private function uploadDestinationPath($destination): string
    {
        $path = (string) $destination->server_path;
        if ($path === '') {
            return '';
        }

        $path = str_replace(
            ['{base_path}', '{base_url}'],
            [rtrim(str_replace('\\', '/', FCPATH), '/') . '/', rtrim((string) ee()->config->item('base_url'), '/') . '/'],
            $path
        );
        $path = preg_replace('#(?<!:)/+#', '/', $path);

        return rtrim(str_replace('\\', '/', $path), '/') . '/';
    }

    private function blogImageField($channel)
    {
        $imageFields = [];
        $exactMatch = null;

        foreach ($channel->CustomFields as $field) {
            if (! in_array($field->field_type, ['file', 'grid'], true)) {
                continue;
            }

            $fieldName = strtolower((string) $field->field_name);
            if ($fieldName === 'blog_image') {
                $exactMatch = $field;
                continue;
            }

            $name = $fieldName . ' ' . strtolower((string) $field->field_label);
            $score = preg_match('/\b(blog_image|blog image|featured|hero|image|photo|thumbnail|cover)\b/', $name) ? 1 : 0;
            $imageFields[] = [$score, (int) $field->field_order, $field];
        }

        if ($exactMatch !== null) {
            return $exactMatch;
        }

        foreach ($this->blogImageFieldRows((int) $channel->channel_id) as $field) {
            if (! in_array($field->field_type, ['file', 'grid'], true)) {
                continue;
            }

            $fieldName = strtolower((string) $field->field_name);
            if ($fieldName === 'blog_image') {
                $exactMatch = $field;
                break;
            }

            $name = $fieldName . ' ' . strtolower((string) $field->field_label);
            $score = preg_match('/\b(blog_image|blog image|featured|hero|image|photo|thumbnail|cover)\b/', $name) ? 1 : 0;
            $imageFields[] = [$score, (int) $field->field_order, $field];
        }

        if ($exactMatch !== null) {
            return $exactMatch;
        }

        if (! $imageFields) {
            return null;
        }

        usort($imageFields, fn($a, $b) => $b[0] <=> $a[0] ?: $a[1] <=> $b[1]);
        return $imageFields[0][2];
    }

    private function blogImageFieldRows(int $channelId): array
    {
        $fields = [];

        $directRows = ee()->db
            ->select('f.*')
            ->from('channel_fields f')
            ->join('channels_channel_fields ccf', 'ccf.field_id = f.field_id')
            ->where('ccf.channel_id', $channelId)
            ->get()
            ->result();

        foreach ($directRows as $row) {
            $fields[(int) $row->field_id] = $row;
        }

        $groupRows = ee()->db
            ->select('f.*')
            ->from('channel_fields f')
            ->join('channel_field_groups_fields fgf', 'fgf.field_id = f.field_id')
            ->join('channels_channel_field_groups ccfg', 'ccfg.group_id = fgf.group_id')
            ->where('ccfg.channel_id', $channelId)
            ->get()
            ->result();

        foreach ($groupRows as $row) {
            $fields[(int) $row->field_id] = $row;
        }

        usort($fields, fn($a, $b) => (int) $a->field_order <=> (int) $b->field_order);
        return $fields;
    }

    private function preferredImageDestination($field = null)
    {
        foreach (ee('Model')->get('UploadDestination')->filter('adapter', 'local')->all() as $destination) {
            if (strtolower((string) $destination->name) === 'blog') {
                return $destination;
            }
        }

        if ($field && $field->field_type === 'file') {
            $settings = @unserialize((string) $field->field_settings);
            $settings = is_array($settings) ? $settings : [];
            $directory = (int) ($settings['allowed_directories'] ?? 0);
            if ($directory > 0) {
                $destination = ee('Model')->get('UploadDestination')->filter('id', $directory)->first();
                if ($destination) {
                    return $destination;
                }
            }
        }

        return null;
    }

    private function copyImageToDestination(string $sourcePath, $destination): string
    {
        $destinationPath = $this->uploadDestinationPath($destination);
        if ($destinationPath === '' || ! is_dir($destinationPath) || strpos($sourcePath, $destinationPath) === 0) {
            return $sourcePath;
        }

        $filename = basename($sourcePath);
        $target = $destinationPath . $filename;
        if (is_file($target)) {
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $name = pathinfo($filename, PATHINFO_FILENAME);
            $target = $destinationPath . $name . '-' . bin2hex(random_bytes(3)) . ($extension ? '.' . $extension : '');
        }

        return copy($sourcePath, $target) ? $target : $sourcePath;
    }

    private function saveGridImage(int $entryId, $field, $file, string $caption): void
    {
        $columns = ee()->db
            ->where('field_id', (int) $field->field_id)
            ->get('grid_columns')
            ->result_array();

        $imageColumn = null;
        $captionColumn = null;
        foreach ($columns as $column) {
            if ($column['col_name'] === 'image' || ($imageColumn === null && $column['col_type'] === 'file')) {
                $imageColumn = $column;
            }

            if ($column['col_name'] === 'caption' || ($captionColumn === null && $column['col_type'] === 'text')) {
                $captionColumn = $column;
            }
        }

        if (! $imageColumn) {
            return;
        }

        ee()->db->where('entry_id', $entryId)->delete('channel_grid_field_' . (int) $field->field_id);

        $row = [
            'entry_id' => $entryId,
            'row_order' => 0,
            'fluid_field_data_id' => 0,
            'col_id_' . (int) $imageColumn['col_id'] => '{file:' . (int) $file->file_id . ':url}',
        ];

        if ($captionColumn) {
            $row['col_id_' . (int) $captionColumn['col_id']] = $caption;
        }

        ee()->db->insert('channel_grid_field_' . (int) $field->field_id, $row);
    }

    private function seoDescription(array $post): string
    {
        $source = trim((string) ($post['intro_text'] ?? '')) ?: trim((string) ($post['post_text'] ?? ''));
        $source = preg_replace('/\s+/', ' ', strip_tags($source));
        return substr((string) $source, 0, 160);
    }

    private function uniqueUrlTitle(string $title, int $channelId): string
    {
        $slug = ee('Format')->make('Text', $title)->urlSlug()->compile();
        $slug = trim((string) $slug, '-_.') ?: 'socialposter-entry';
        $slug = substr($slug, 0, URL_TITLE_MAX_LENGTH);
        $candidate = $slug;
        $i = 2;

        while ($this->urlTitleExists($candidate, $channelId)) {
            $suffix = '-' . $i++;
            $candidate = substr($slug, 0, URL_TITLE_MAX_LENGTH - strlen($suffix)) . $suffix;
        }

        return $candidate;
    }

    private function urlTitleExists(string $urlTitle, int $channelId): bool
    {
        return ee('Model')->get('ChannelEntry')
            ->filter('channel_id', $channelId)
            ->filter('url_title', $urlTitle)
            ->count() > 0;
    }

    private function validationMessage(array $errors): string
    {
        foreach ($errors as $fieldErrors) {
            if (is_array($fieldErrors)) {
                $message = reset($fieldErrors);
                if ($message) {
                    return (string) $message;
                }
            }
        }

        return 'Blog entry validation failed.';
    }

    private function memberId(): int
    {
        $memberId = (int) ee()->session->userdata('member_id');
        return $memberId > 0 ? $memberId : 1;
    }

    private function escapeHtml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    private function escapeAttr(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
