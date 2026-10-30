<?php

namespace JavidFazaeli\SocialPoster\Service;

use JavidFazaeli\SocialPoster\Service\InstagramPostGenerator;

class Publisher
{
    private SocialPostGenerator $generator;
    private SocialPostGenerator $instagramGenerator;

    public function __construct(?SocialPostGenerator $generator = null, ?SocialPostGenerator $instagramGenerator = null)
    {
        $this->generator          = $generator          ?: new SocialPostGenerator();
        $this->instagramGenerator = $instagramGenerator ?: new InstagramPostGenerator();
    }

    public function targets(): array
    {
        $instagramConfigured = $this->instagramConfigured();
        $facebookConfigured = $this->facebookConfigured();

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
                'status' => $instagramConfigured ? 'configured' : 'needs_settings',
                'description' => $instagramConfigured
                    ? 'Credentials are saved. Direct Instagram publishing is available from the Instagram Publish menu.'
                    : 'Add Instagram Graph API credentials in Settings before direct publishing is enabled.',
            ],
            'facebook' => [
                'label' => 'Facebook',
                'status' => $facebookConfigured ? 'configured' : 'needs_settings',
                'description' => $facebookConfigured
                    ? 'Credentials are saved. Direct Facebook Page publishing is available from the Facebook Publish menu.'
                    : 'Add Facebook Page credentials before direct publishing is enabled.',
            ],
        ];
    }

    public function genericPublishTargets(): array
    {
        return array_intersect_key($this->targets(), array_flip(['blog']));
    }

    private function instagramConfigured(): bool
    {
        $settings = $this->generator->getSettings();

        return trim((string) ($settings['instagram_business_account_id'] ?? '')) !== ''
            && trim((string) ($settings['instagram_access_token'] ?? '')) !== '';
    }

    private function facebookConfigured(): bool
    {
        $settings = $this->generator->getSettings();

        return $this->facebookPageId($settings) !== ''
            && $this->facebookAccessToken($settings) !== '';
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

        $existingEntry = $this->publishedBlogEntry($generationId);
        if ($existingEntry) {
            throw new \RuntimeException('This post is already published to the blog as entry #' . (int) $existingEntry->entry_id . ' ("' . $existingEntry->title . '"). Edit that entry instead, or delete it from Published to publish again.');
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

    public function publishToInstagram(int $generationId): array
    {
        $post = $this->instagramGenerator->find($generationId);
        if (! $post) {
            throw new \InvalidArgumentException('Generated post not found.');
        }

        $settings    = $this->generator->getSettings();
        $igUserId    = trim((string) ($settings['instagram_business_account_id'] ?? ''));
        $accessToken = trim((string) ($settings['instagram_access_token'] ?? ''));
        $graphVersion = trim((string) ($settings['instagram_graph_version'] ?? 'v24.0'));

        if ($igUserId === '' || $accessToken === '') {
            throw new \RuntimeException('Instagram credentials are not configured. Add them under Instagram Settings.');
        }

        $imageUrl = $this->absoluteImageUrl($post);
        if ($imageUrl === '') {
            throw new \RuntimeException('No image is available for this post. Generate or upload an image before publishing.');
        }

        $caption = $this->instagramCaption($post);

        $containerId = $this->instagramCreateContainer($graphVersion, $igUserId, $imageUrl, $caption, $accessToken);
        $this->instagramWaitForContainer($graphVersion, $containerId, $accessToken);
        $mediaId     = $this->instagramPublishContainer($graphVersion, $igUserId, $containerId, $accessToken);

        $this->recordPublishedInstagram($generationId, $mediaId, $caption);

        return [
            'media_id' => $mediaId,
            'caption'  => $caption,
        ];
    }

    public function publishToFacebook(int $generationId): array
    {
        $post = $this->generator->find($generationId);
        if (! $post) {
            throw new \InvalidArgumentException('Generated post not found.');
        }

        $settings     = $this->generator->getSettings();
        $pageId       = $this->facebookPageId($settings);
        $accessToken  = $this->facebookAccessToken($settings);
        $graphVersion = $this->facebookGraphVersion($settings);

        if ($pageId === '' || $accessToken === '') {
            throw new \RuntimeException('Facebook Page credentials are not configured. Add them under Facebook Settings.');
        }

        $message  = $this->facebookMessage($post);
        $imageUrl = $this->absoluteImageUrl($post);

        if ($imageUrl !== '') {
            $result = $this->facebookPublishPhoto($graphVersion, $pageId, $imageUrl, $message, $accessToken);
        } else {
            $result = $this->facebookPublishFeed($graphVersion, $pageId, $message, $accessToken);
        }

        $objectId = trim((string) ($result['post_id'] ?? $result['id'] ?? ''));
        if ($objectId === '') {
            throw new \RuntimeException('Facebook API: publish response did not include a post ID.');
        }

        $this->recordPublishedFacebook($generationId, $objectId, $message);

        return [
            'post_id' => $objectId,
            'message' => $message,
            'used_image' => $imageUrl !== '',
        ];
    }

    public function publishedFacebooks(int $limit = 50): array
    {
        if (! ee()->db->table_exists('socialposter_published_facebook')) {
            return [];
        }

        return ee()->db
            ->order_by('created_at', 'DESC')
            ->limit($limit)
            ->get('socialposter_published_facebook')
            ->result_array();
    }

    public function publishedInstagrams(int $limit = 50): array
    {
        if (! ee()->db->table_exists('socialposter_published_instagram')) {
            return [];
        }

        return ee()->db
            ->order_by('created_at', 'DESC')
            ->limit($limit)
            ->get('socialposter_published_instagram')
            ->result_array();
    }

    private function instagramCaption(array $post): string
    {
        $postText = trim((string) ($post['post_text'] ?? ''));

        // For carousel posts the format is: slide copy \n\nInstagram Caption\n caption
        $parts = preg_split('/\n\s*(?:#{1,6}\s*)?Instagram Caption:?\s*\n/i', $postText, 2);
        $caption = count($parts) === 2 ? trim($parts[1]) : $postText;

        $hashtags = array_filter(
            (array) ($post['hashtags'] ?? []),
            fn($t) => trim((string) $t) !== ''
        );

        if ($hashtags) {
            $hashtagStr = implode(' ', array_map(
                fn($t) => '#' . ltrim(trim((string) $t), '#'),
                $hashtags
            ));
            $caption = $caption !== '' ? $caption . "\n\n" . $hashtagStr : $hashtagStr;
        }

        // Append source credit for news-based posts
        $source       = trim((string) ($post['source'] ?? ''));
        $externalLink = trim((string) ($post['external_link'] ?? ''));
        if ($source === 'news' && $externalLink !== '') {
            $host = (string) parse_url($externalLink, PHP_URL_HOST);
            $host = (string) preg_replace('/^www\./i', '', $host);
            if ($host !== '') {
                $caption .= ($caption !== '' ? "\n\n" : '') . 'via ' . $host;
            }
        }

        return $caption;
    }

    private function facebookMessage(array $post): string
    {
        $message = trim((string) ($post['post_text'] ?? ''));
        if ($message === '') {
            $message = trim((string) ($post['intro_text'] ?? ''));
        }
        if ($message === '') {
            $message = trim((string) ($post['title'] ?? ''));
        }
        $message = $this->plainSocialText($message);

        $hashtags = array_filter(
            (array) ($post['hashtags'] ?? []),
            fn($t) => trim((string) $t) !== ''
        );

        if ($hashtags) {
            $hashtagStr = implode(' ', array_map(
                fn($t) => '#' . ltrim(trim((string) $t), '#'),
                $hashtags
            ));
            $message = $message !== '' ? $message . "\n\n" . $hashtagStr : $hashtagStr;
        }

        return $message;
    }

    private function plainSocialText(string $text): string
    {
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES, 'UTF-8');
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        $text = preg_replace('/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/i', '$1: $2', $text);
        $text = preg_replace('/!\[([^\]]*)\]\(([^)]+)\)/', '', $text);
        $text = preg_replace('/^#{1,6}\s*/m', '', $text);
        $text = preg_replace('/^\s*[-*+]\s+/m', '', $text);
        $text = preg_replace('/^\s*\d+[.)]\s+/m', '', $text);
        $text = preg_replace('/^\s*>\s?/m', '', $text);
        $text = preg_replace('/^\s*---+\s*$/m', '', $text);
        $text = preg_replace('/(\*\*|__)(.*?)\1/s', '$2', $text);
        $text = preg_replace('/(\*|_)(.*?)\1/s', '$2', $text);
        $text = preg_replace('/`([^`]+)`/', '$1', $text);
        $text = preg_replace('/[ \t]+\n/', "\n", $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        $text = preg_replace('/[ \t]{2,}/', ' ', $text);

        return trim((string) $text);
    }

    private function absoluteImageUrl(array $post): string
    {
        $imageUrl = trim((string) ($post['image_url'] ?? ''));
        if ($imageUrl === '') {
            return '';
        }

        if (preg_match('/^https?:\/\//i', $imageUrl)) {
            return $imageUrl;
        }

        $base = rtrim((string) ee()->config->item('base_url'), '/');
        return $base . '/' . ltrim($imageUrl, '/');
    }

    private function instagramCreateContainer(string $version, string $userId, string $imageUrl, string $caption, string $accessToken): string
    {
        $url = 'https://graph.facebook.com/' . $version . '/' . $userId . '/media';
        $response = $this->instagramGraphPost($url, [
            'image_url'    => $imageUrl,
            'caption'      => $caption,
            'access_token' => $accessToken,
        ]);

        $id = trim((string) ($response['id'] ?? ''));
        if ($id === '') {
            $message = (string) ($response['error']['message'] ?? 'Unknown error creating media container.');
            throw new \RuntimeException('Instagram API: ' . $message);
        }

        return $id;
    }

    private function instagramPublishContainer(string $version, string $userId, string $containerId, string $accessToken): string
    {
        $url = 'https://graph.facebook.com/' . $version . '/' . $userId . '/media_publish';
        $response = $this->instagramGraphPost($url, [
            'creation_id'  => $containerId,
            'access_token' => $accessToken,
        ]);

        $id = trim((string) ($response['id'] ?? ''));
        if ($id === '') {
            $message = (string) ($response['error']['message'] ?? 'Unknown error publishing container.');
            throw new \RuntimeException('Instagram API: ' . $message);
        }

        return $id;
    }

    private function facebookPublishPhoto(string $version, string $pageId, string $imageUrl, string $message, string $accessToken): array
    {
        $url = 'https://graph.facebook.com/' . $version . '/' . $pageId . '/photos';
        $response = $this->facebookGraphPost($url, [
            'url'          => $imageUrl,
            'message'      => $message,
            'access_token' => $accessToken,
        ]);

        if (trim((string) ($response['id'] ?? $response['post_id'] ?? '')) === '') {
            $message = (string) ($response['error']['message'] ?? 'Unknown error publishing photo.');
            throw new \RuntimeException('Facebook API: ' . $message);
        }

        return $response;
    }

    private function facebookPublishFeed(string $version, string $pageId, string $message, string $accessToken): array
    {
        if (trim($message) === '') {
            throw new \RuntimeException('Facebook publish requires text or an image.');
        }

        $url = 'https://graph.facebook.com/' . $version . '/' . $pageId . '/feed';
        $response = $this->facebookGraphPost($url, [
            'message'      => $message,
            'access_token' => $accessToken,
        ]);

        if (trim((string) ($response['id'] ?? '')) === '') {
            $message = (string) ($response['error']['message'] ?? 'Unknown error publishing feed post.');
            throw new \RuntimeException('Facebook API: ' . $message);
        }

        return $response;
    }

    private function instagramWaitForContainer(string $version, string $containerId, string $accessToken): void
    {
        $url = 'https://graph.facebook.com/' . $version . '/' . $containerId;
        $lastStatus = '';

        for ($attempt = 1; $attempt <= 10; $attempt++) {
            $response = $this->instagramGraphGet($url, [
                'fields'       => 'status_code',
                'access_token' => $accessToken,
            ]);

            $lastStatus = strtoupper(trim((string) ($response['status_code'] ?? '')));
            if ($lastStatus === 'FINISHED') {
                return;
            }

            if (in_array($lastStatus, ['ERROR', 'EXPIRED', 'PUBLISHED'], true)) {
                throw new \RuntimeException('Instagram API: media container status is ' . $lastStatus . '.');
            }

            if ($attempt < 10) {
                sleep(3);
            }
        }

        throw new \RuntimeException(
            'Instagram API: media container was not ready for publishing after 30 seconds'
            . ($lastStatus !== '' ? ' (last status: ' . $lastStatus . ').' : '.')
        );
    }

    public function exchangeInstagramToken(string $shortToken): array
    {
        $shortToken = trim($shortToken);
        if ($shortToken === '') {
            throw new \InvalidArgumentException('Paste a token to exchange.');
        }

        $settings  = ee('socialposter:generator')->getSettings();
        $appId     = trim((string) ($settings['instagram_app_id'] ?? ''));
        $appSecret = trim((string) ($settings['instagram_app_secret'] ?? ''));
        $version   = $this->graphVersion($settings);

        if ($appId === '' || $appSecret === '') {
            throw new \RuntimeException('Meta App ID and App Secret are required to exchange a token. Save those above first.');
        }

        $response = $this->instagramGraphGet(
            'https://graph.facebook.com/' . $version . '/oauth/access_token',
            [
                'grant_type'        => 'fb_exchange_token',
                'client_id'         => $appId,
                'client_secret'     => $appSecret,
                'fb_exchange_token' => $shortToken,
            ]
        );

        $token = trim((string) ($response['access_token'] ?? ''));
        if ($token === '') {
            $msg = $response['error']['message'] ?? 'Token exchange did not return an access_token.';
            throw new \RuntimeException($msg);
        }

        return [
            'access_token' => $token,
            'expires_in'   => (int) ($response['expires_in'] ?? 0),
        ];
    }

    public function exchangeFacebookToken(string $shortToken): array
    {
        $shortToken = trim($shortToken);
        if ($shortToken === '') {
            throw new \InvalidArgumentException('Paste a token to exchange.');
        }

        $settings  = ee('socialposter:generator')->getSettings();
        $appId     = $this->facebookAppId($settings);
        $appSecret = $this->facebookAppSecret($settings);
        $version   = $this->facebookGraphVersion($settings);

        if ($appId === '' || $appSecret === '') {
            throw new \RuntimeException('Facebook App ID and App Secret are required to exchange a token. Save those first.');
        }

        $response = $this->facebookGraphGet(
            'https://graph.facebook.com/' . $version . '/oauth/access_token',
            [
                'grant_type'        => 'fb_exchange_token',
                'client_id'         => $appId,
                'client_secret'     => $appSecret,
                'fb_exchange_token' => $shortToken,
            ]
        );

        $token = trim((string) ($response['access_token'] ?? ''));
        if ($token === '') {
            $msg = $response['error']['message'] ?? 'Token exchange did not return an access_token.';
            throw new \RuntimeException($msg);
        }

        return [
            'access_token' => $token,
            'expires_in'   => (int) ($response['expires_in'] ?? 0),
        ];
    }

    public function listInstagramPages(): array
    {
        $settings = ee('socialposter:generator')->getSettings();
        $version  = $this->graphVersion($settings);
        $token    = trim((string) ($settings['instagram_user_access_token'] ?? ''))
                 ?: trim((string) ($settings['instagram_access_token'] ?? ''));

        if ($token === '') {
            throw new \RuntimeException('Save a user access token first (use "Exchange & Save Long-Lived Token" above).');
        }

        $response = $this->instagramGraphGet(
            'https://graph.facebook.com/' . $version . '/me/accounts',
            [
                'access_token' => $token,
                'fields'       => 'id,name,access_token,instagram_business_account{id,username}',
                'limit'        => 100,
            ]
        );

        $pages = [];
        foreach ((array) ($response['data'] ?? []) as $page) {
            if (! is_array($page)) {
                continue;
            }
            $igAccount = is_array($page['instagram_business_account'] ?? null) ? $page['instagram_business_account'] : [];
            $pages[] = [
                'id'                            => (string) ($page['id'] ?? ''),
                'name'                          => (string) ($page['name'] ?? ''),
                'access_token'                  => (string) ($page['access_token'] ?? ''),
                'instagram_business_account_id' => (string) ($igAccount['id'] ?? ''),
                'instagram_username'            => (string) ($igAccount['username'] ?? ''),
            ];
        }

        return $pages;
    }

    public function listFacebookPages(): array
    {
        $settings = ee('socialposter:generator')->getSettings();
        $version  = $this->facebookGraphVersion($settings);
        $token    = trim((string) ($settings['facebook_user_access_token'] ?? ''))
                 ?: trim((string) ($settings['facebook_access_token'] ?? ''))
                 ?: trim((string) ($settings['instagram_user_access_token'] ?? ''))
                 ?: trim((string) ($settings['instagram_access_token'] ?? ''));

        if ($token === '') {
            throw new \RuntimeException('Save a Facebook user access token first, then list Pages.');
        }

        $response = $this->facebookGraphGet(
            'https://graph.facebook.com/' . $version . '/me/accounts',
            [
                'access_token' => $token,
                'fields'       => 'id,name,access_token,tasks',
                'limit'        => 100,
            ]
        );

        $pages = [];
        foreach ((array) ($response['data'] ?? []) as $page) {
            if (! is_array($page)) {
                continue;
            }
            $pages[] = [
                'id'           => (string) ($page['id'] ?? ''),
                'name'         => (string) ($page['name'] ?? ''),
                'access_token' => (string) ($page['access_token'] ?? ''),
                'tasks'        => array_values((array) ($page['tasks'] ?? [])),
            ];
        }

        return $pages;
    }

    public function selectInstagramPage(string $pageId): array
    {
        $pageId = trim($pageId);
        if ($pageId === '') {
            throw new \InvalidArgumentException('No page selected.');
        }

        foreach ($this->listInstagramPages() as $page) {
            if ($page['id'] === $pageId) {
                if ($page['access_token'] === '') {
                    throw new \RuntimeException('Page found but has no access_token in the response. Make sure your user token has pages_show_list and pages_read_engagement scopes.');
                }
                return $page;
            }
        }

        throw new \RuntimeException('Page not found in your account list.');
    }

    public function selectFacebookPage(string $pageId): array
    {
        $pageId = trim($pageId);
        if ($pageId === '') {
            throw new \InvalidArgumentException('No page selected.');
        }

        foreach ($this->listFacebookPages() as $page) {
            if ($page['id'] === $pageId) {
                if ($page['access_token'] === '') {
                    throw new \RuntimeException('Page found but has no access_token in the response. Make sure your user token has pages_show_list and pages_manage_posts permissions.');
                }
                return $page;
            }
        }

        throw new \RuntimeException('Page not found in your account list.');
    }

    public function inspectInstagramToken(?string $token = null): array
    {
        $settings  = ee('socialposter:generator')->getSettings();
        $appId     = trim((string) ($settings['instagram_app_id'] ?? ''));
        $appSecret = trim((string) ($settings['instagram_app_secret'] ?? ''));
        $version   = $this->graphVersion($settings);
        $token     = $token !== null ? trim($token) : trim((string) ($settings['instagram_access_token'] ?? ''));

        $base = ['is_valid' => false, 'expires_at' => 0, 'scopes' => [], 'app_id' => '', 'type' => '', 'error' => ''];

        if ($token === '') {
            return $base + ['error' => 'No access token saved.'];
        }
        if ($appId === '' || $appSecret === '') {
            return $base + ['error' => 'Set Meta App ID and App Secret to check token status.'];
        }

        try {
            $response = $this->instagramGraphGet(
                'https://graph.facebook.com/' . $version . '/debug_token',
                ['input_token' => $token, 'access_token' => $appId . '|' . $appSecret]
            );
        } catch (\Throwable $e) {
            return $base + ['error' => $e->getMessage()];
        }

        $data = is_array($response['data'] ?? null) ? $response['data'] : [];

        return [
            'is_valid'   => (bool) ($data['is_valid'] ?? false),
            'expires_at' => (int) ($data['expires_at'] ?? 0),
            'scopes'     => array_values((array) ($data['scopes'] ?? [])),
            'app_id'     => (string) ($data['app_id'] ?? ''),
            'type'       => (string) ($data['type'] ?? ''),
            'error'      => (string) ($data['error']['message'] ?? ''),
        ];
    }

    public function inspectFacebookToken(?string $token = null): array
    {
        $settings  = ee('socialposter:generator')->getSettings();
        $appId     = $this->facebookAppId($settings);
        $appSecret = $this->facebookAppSecret($settings);
        $version   = $this->facebookGraphVersion($settings);
        $token     = $token !== null ? trim($token) : $this->facebookAccessToken($settings);

        $base = ['is_valid' => false, 'expires_at' => 0, 'scopes' => [], 'app_id' => '', 'type' => '', 'error' => ''];

        if ($token === '') {
            return $base + ['error' => 'No access token saved.'];
        }
        if ($appId === '' || $appSecret === '') {
            return $base + ['error' => 'Set Facebook App ID and App Secret to check token status.'];
        }

        try {
            $response = $this->facebookGraphGet(
                'https://graph.facebook.com/' . $version . '/debug_token',
                ['input_token' => $token, 'access_token' => $appId . '|' . $appSecret]
            );
        } catch (\Throwable $e) {
            return $base + ['error' => $e->getMessage()];
        }

        $data = is_array($response['data'] ?? null) ? $response['data'] : [];

        return [
            'is_valid'   => (bool) ($data['is_valid'] ?? false),
            'expires_at' => (int) ($data['expires_at'] ?? 0),
            'scopes'     => array_values((array) ($data['scopes'] ?? [])),
            'app_id'     => (string) ($data['app_id'] ?? ''),
            'type'       => (string) ($data['type'] ?? ''),
            'error'      => (string) ($data['error']['message'] ?? ''),
        ];
    }

    public function testInstagramConnection(): array
    {
        $settings   = ee('socialposter:generator')->getSettings();
        $token      = trim((string) ($settings['instagram_access_token'] ?? ''));
        $igUserId   = trim((string) ($settings['instagram_business_account_id'] ?? ''));
        $pageId     = trim((string) ($settings['instagram_page_id'] ?? ''));
        $appId      = trim((string) ($settings['instagram_app_id'] ?? ''));
        $appSecret  = trim((string) ($settings['instagram_app_secret'] ?? ''));
        $version    = $this->graphVersion($settings);

        // instagram_business_basic is the Instagram Login equivalent of instagram_basic
        $required = ['instagram_content_publish', 'instagram_basic'];
        $result = [
            'token_valid'       => false,
            'token_type'        => '',
            'token_expires'     => '',
            'scopes'            => [],
            'missing_scopes'    => $required,
            'ig_account'        => '',
            'ig_username'       => '',
            'page_id'           => $pageId,
            'errors'            => [],
            'warnings'          => [],
        ];

        if ($token === '') {
            $result['errors'][] = 'No access token saved.';
            return $result;
        }
        if ($igUserId === '') {
            $result['errors'][] = 'Instagram Professional Account ID is not set.';
        }

        // 1. Inspect token via debug_token (needs App ID + Secret)
        if ($appId !== '' && $appSecret !== '') {
            try {
                $debug = $this->instagramGraphGet(
                    'https://graph.facebook.com/' . $version . '/debug_token',
                    ['input_token' => $token, 'access_token' => $appId . '|' . $appSecret]
                );
                $data = is_array($debug['data'] ?? null) ? $debug['data'] : [];
                $result['token_valid'] = (bool) ($data['is_valid'] ?? false);
                $result['token_type']  = (string) ($data['type'] ?? '');
                $result['scopes']      = array_values((array) ($data['scopes'] ?? []));
                // Normalise scope aliases so checks pass regardless of Meta's naming variant:
                // instagram_business_basic  (Instagram Login API) → instagram_basic
                // instagram_content_publishing (portal label)     → instagram_content_publish
                $effectiveScopes = $result['scopes'];
                if (in_array('instagram_business_basic', $effectiveScopes, true)) {
                    $effectiveScopes[] = 'instagram_basic';
                }
                if (in_array('instagram_content_publishing', $effectiveScopes, true)) {
                    $effectiveScopes[] = 'instagram_content_publish';
                }
                $result['missing_scopes'] = array_values(array_diff($required, $effectiveScopes));

                $expiresAt = (int) ($data['expires_at'] ?? 0);
                if ($expiresAt === 0) {
                    $result['token_expires'] = 'Does not expire';
                } else {
                    $days = (int) floor(($expiresAt - time()) / 86400);
                    $result['token_expires'] = date('M j, Y', $expiresAt) . ' (' . $days . ' days)';
                }

                if (! $result['token_valid']) {
                    $result['errors'][] = 'Token is invalid or expired: ' . ($data['error']['message'] ?? 'unknown error');
                }
            } catch (\Throwable $e) {
                $result['warnings'][] = 'Could not inspect token: ' . $e->getMessage();
                $result['token_valid'] = true; // assume valid and proceed
            }
        } else {
            $result['warnings'][] = 'App ID or App Secret not saved — skipping token scope check.';
            $result['token_valid'] = true;
            $result['missing_scopes'] = [];
        }

        // 2. Verify the IG Business Account ID resolves
        if ($igUserId !== '' && $result['token_valid']) {
            try {
                $igData = $this->instagramGraphGet(
                    'https://graph.facebook.com/' . $version . '/' . $igUserId,
                    ['fields' => 'id,username,name', 'access_token' => $token]
                );
                $result['ig_account']  = (string) ($igData['id'] ?? '');
                $result['ig_username'] = (string) ($igData['username'] ?? $igData['name'] ?? '');
            } catch (\Throwable $e) {
                $result['errors'][] = 'Business Account ID lookup failed: ' . $e->getMessage();
            }
        }

        return $result;
    }

    public function testFacebookConnection(): array
    {
        $settings  = ee('socialposter:generator')->getSettings();
        $token     = $this->facebookAccessToken($settings);
        $pageId    = $this->facebookPageId($settings);
        $appId     = $this->facebookAppId($settings);
        $appSecret = $this->facebookAppSecret($settings);
        $version   = $this->facebookGraphVersion($settings);
        $required  = ['pages_manage_posts', 'pages_read_engagement', 'pages_show_list'];
        $result = [
            'token_valid'    => false,
            'token_type'     => '',
            'token_expires'  => '',
            'scopes'         => [],
            'missing_scopes' => $required,
            'page_id'        => $pageId,
            'page_name'      => '',
            'errors'         => [],
            'warnings'       => [],
        ];

        if ($token === '') {
            $result['errors'][] = 'No Facebook Page access token saved.';
            return $result;
        }
        if ($pageId === '') {
            $result['errors'][] = 'Facebook Page ID is not set.';
        }

        if ($appId !== '' && $appSecret !== '') {
            try {
                $debug = $this->facebookGraphGet(
                    'https://graph.facebook.com/' . $version . '/debug_token',
                    ['input_token' => $token, 'access_token' => $appId . '|' . $appSecret]
                );
                $data = is_array($debug['data'] ?? null) ? $debug['data'] : [];
                $result['token_valid'] = (bool) ($data['is_valid'] ?? false);
                $result['token_type']  = (string) ($data['type'] ?? '');
                $result['scopes']      = array_values((array) ($data['scopes'] ?? []));
                $result['missing_scopes'] = array_values(array_diff($required, $result['scopes']));

                $expiresAt = (int) ($data['expires_at'] ?? 0);
                $result['token_expires'] = $expiresAt > 0
                    ? date('M j, Y', $expiresAt) . ' (' . (int) floor(($expiresAt - time()) / 86400) . ' days)'
                    : 'Does not expire';

                if (! $result['token_valid']) {
                    $result['errors'][] = 'Token is invalid or expired: ' . ($data['error']['message'] ?? 'unknown error');
                }
            } catch (\Throwable $e) {
                $result['warnings'][] = 'Could not inspect token: ' . $e->getMessage();
                $result['token_valid'] = true;
                $result['missing_scopes'] = [];
            }
        } else {
            $result['warnings'][] = 'App ID or App Secret not saved; skipping token scope check.';
            $result['token_valid'] = true;
            $result['missing_scopes'] = [];
        }

        if ($pageId !== '' && $result['token_valid'] && in_array('pages_read_engagement', $result['scopes'], true)) {
            try {
                $page = $this->facebookGraphGet(
                    'https://graph.facebook.com/' . $version . '/' . $pageId,
                    ['fields' => 'id,name', 'access_token' => $token]
                );
                $result['page_id']   = (string) ($page['id'] ?? '');
                $result['page_name'] = (string) ($page['name'] ?? '');
            } catch (\Throwable $e) {
                $result['errors'][] = 'Facebook Page lookup failed: ' . $e->getMessage();
            }
        } elseif ($pageId !== '' && $result['token_valid']) {
            $result['warnings'][] = 'Skipped Page lookup because the saved token is missing pages_read_engagement.';
        }

        return $result;
    }

    private function graphVersion(array $settings): string
    {
        $version = trim((string) ($settings['instagram_graph_version'] ?? 'v24.0'));
        return $version !== '' ? $version : 'v24.0';
    }

    private function facebookGraphVersion(array $settings): string
    {
        $version = trim((string) ($settings['facebook_graph_version'] ?? $settings['instagram_graph_version'] ?? 'v24.0'));
        return $version !== '' ? $version : 'v24.0';
    }

    private function facebookAppId(array $settings): string
    {
        return trim((string) ($settings['facebook_app_id'] ?? ''))
            ?: trim((string) ($settings['instagram_app_id'] ?? ''));
    }

    private function facebookAppSecret(array $settings): string
    {
        return trim((string) ($settings['facebook_app_secret'] ?? ''))
            ?: trim((string) ($settings['instagram_app_secret'] ?? ''));
    }

    private function facebookPageId(array $settings): string
    {
        return trim((string) ($settings['facebook_page_id'] ?? ''))
            ?: trim((string) ($settings['instagram_page_id'] ?? ''));
    }

    private function facebookAccessToken(array $settings): string
    {
        return trim((string) ($settings['facebook_access_token'] ?? ''))
            ?: trim((string) ($settings['instagram_access_token'] ?? ''));
    }

    private function facebookGraphGet(string $url, array $params): array
    {
        $url .= '?' . http_build_query($params);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_USERAGENT      => 'SocialPoster/1.0',
        ]);

        $body   = curl_exec($ch);
        $error  = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false || $body === '') {
            throw new \RuntimeException('Facebook API request failed: ' . ($error ?: 'empty response'));
        }

        $decoded = json_decode((string) $body, true);
        if (! is_array($decoded)) {
            throw new \RuntimeException('Facebook API returned a non-JSON response.');
        }

        if ($status < 200 || $status >= 300) {
            $msg = $decoded['error']['message'] ?? 'Facebook API request failed (HTTP ' . $status . ').';
            throw new \RuntimeException($msg);
        }

        return $decoded;
    }

    private function facebookGraphPost(string $url, array $params): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($params),
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_USERAGENT      => 'SocialPoster/1.0',
        ]);

        $body   = curl_exec($ch);
        $error  = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false || $body === '') {
            throw new \RuntimeException('Facebook API request failed: ' . ($error ?: 'empty response'));
        }

        $decoded = json_decode((string) $body, true);
        if (! is_array($decoded)) {
            throw new \RuntimeException('Facebook API returned a non-JSON response.');
        }

        if ($status < 200 || $status >= 300) {
            $msg = $decoded['error']['message'] ?? 'Facebook API request failed (HTTP ' . $status . ').';
            throw new \RuntimeException($msg);
        }

        return $decoded;
    }

    private function instagramGraphGet(string $url, array $params): array
    {
        $url .= '?' . http_build_query($params);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_USERAGENT      => 'SocialPoster/1.0',
        ]);

        $body   = curl_exec($ch);
        $error  = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false || $body === '') {
            throw new \RuntimeException('Instagram API request failed: ' . ($error ?: 'empty response'));
        }

        $decoded = json_decode((string) $body, true);
        if (! is_array($decoded)) {
            throw new \RuntimeException('Instagram API returned a non-JSON response.');
        }

        if ($status < 200 || $status >= 300) {
            $msg = $decoded['error']['message'] ?? 'Instagram API request failed (HTTP ' . $status . ').';
            throw new \RuntimeException($msg);
        }

        return $decoded;
    }

    private function instagramGraphPost(string $url, array $params): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($params),
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_USERAGENT      => 'SocialPoster/1.0',
        ]);

        $body  = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($body === false || $body === '') {
            throw new \RuntimeException('Instagram API request failed: ' . $error);
        }

        $decoded = json_decode((string) $body, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function recordPublishedInstagram(int $generationId, string $mediaId, string $caption): void
    {
        if (! ee()->db->table_exists('socialposter_published_instagram')) {
            return;
        }

        ee()->db->insert('socialposter_published_instagram', [
            'generation_id' => $generationId,
            'media_id'      => $mediaId,
            'caption'       => $caption,
            'created_at'    => ee()->localize->now,
        ]);
    }

    private function recordPublishedFacebook(int $generationId, string $postId, string $message): void
    {
        if (! ee()->db->table_exists('socialposter_published_facebook')) {
            return;
        }

        ee()->db->insert('socialposter_published_facebook', [
            'generation_id' => $generationId,
            'post_id'       => $postId,
            'message'       => $message,
            'created_at'    => ee()->localize->now,
        ]);
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

    /**
     * The live blog entry already published from this generation, if any.
     * Records whose entry was deleted outside the add-on are dropped so the
     * generation can be published again.
     */
    private function publishedBlogEntry(int $generationId)
    {
        if (! ee()->db->table_exists('socialposter_published_blogs')) {
            return null;
        }

        $rows = ee()->db
            ->where('generation_id', $generationId)
            ->order_by('created_at', 'DESC')
            ->get('socialposter_published_blogs')
            ->result_array();

        foreach ($rows as $row) {
            $entry = ee('Model')->get('ChannelEntry', (int) $row['entry_id'])->first();
            if ($entry) {
                return $entry;
            }

            ee()->db->where('id', (int) $row['id'])->delete('socialposter_published_blogs');
        }

        return null;
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

        // Sources live in the article body. Hashtags and recommended topics are
        // social and planning output, so they stay out of blog posts. A related
        // internal post is linked once, unless the body already links it.
        $internal = trim((string) ($post['internal_link'] ?? ''));
        if ($internal !== '') {
            [, $internalUrl] = $this->splitLinkLabel('Internal: ' . $internal);
            $internalUrl = $internalUrl !== '' ? $internalUrl : $internal;
            $body = implode("\n", $html);
            if (strpos($body, $this->href($internalUrl)) === false && strpos($body, $internalUrl) === false) {
                $linkText = $this->internalLinkTitle($internalUrl) ?: $internalUrl;
                $html[] = '<p>Related reading: <a href="' . $this->escapeAttr($this->href($internalUrl)) . '">' . $this->escapeHtml($linkText) . '</a></p>';
            }
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
            $path = (new ImageStorage())->publicPath($imagePath);
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

        $path = (new ImageStorage())->publicPath($urlPath);
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
            [(new ImageStorage())->siteRootPath(), rtrim((string) ee()->config->item('base_url'), '/') . '/'],
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
        $source = trim((string) preg_replace('/\s+/', ' ', strip_tags((string) preg_replace('/[#*_`>\[\]]/', '', $source))));
        if (mb_strlen($source) <= 160) {
            return $source;
        }

        // Whole sentences that fit; otherwise cut at a word and mark it.
        $out = '';
        foreach (preg_split('/(?<=[.!?])\s+/', $source) as $sentence) {
            if (mb_strlen(trim($out . ' ' . $sentence)) > 160) {
                break;
            }
            $out = trim($out . ' ' . $sentence);
        }
        if ($out !== '') {
            return $out;
        }

        $cut = mb_substr($source, 0, 157);
        $cut = preg_replace('/\s+\S*$/', '', $cut);
        return rtrim((string) $cut, ' ,;:-') . '…';
    }

    private function uniqueUrlTitle(string $title, int $channelId): string
    {
        $slug = ee('Format')->make('Text', $title)->urlSlug()->compile();
        $slug = trim((string) $slug, '-_.') ?: 'socialposter-entry';
        // Keep addresses readable: whole words, about 70 characters at most.
        if (strlen($slug) > 70) {
            $cut = substr($slug, 0, 71);
            $slug = rtrim(substr($cut, 0, (int) strrpos($cut, '-')) ?: substr($slug, 0, 70), '-_.');
        }
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
