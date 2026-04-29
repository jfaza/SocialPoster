<?php
ee()->load->helper('form');
$h = fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$pageModeRaw = (string) ($page_mode ?? 'general');
$pageMode = in_array($pageModeRaw, ['instagram', 'news'], true) ? $pageModeRaw : 'general';
$isInstagramPage = $pageMode === 'instagram';
$isNewsPage = $pageMode === 'news';
$historyTitle = (string) ($history_title ?? 'Generated Posts');
$templateBadge = function ($templateId) use ($template_meta, $h) {
    $template = $template_meta[(int) $templateId] ?? null;
    if (! $template) {
        return '';
    }

    $platform = trim((string) ($template['platform'] ?? ''));
    $title = trim((string) ($template['title'] ?? ''));
    $label = $platform !== '' ? $platform : $title;
    if ($label === '') {
        return '';
    }

    $class = strtolower(preg_replace('/[^a-z0-9]+/', '-', $label));
    return '<span class="sp-template-badge sp-template-badge--' . $h(trim((string) $class, '-')) . '">' . $h($label) . '</span>';
};
$isInstagramTemplate = function ($templateId) use ($template_meta) {
    $template = $template_meta[(int) $templateId] ?? null;
    if (! $template) {
        return false;
    }

    $platform = strtolower(trim((string) ($template['platform'] ?? '')));
    $title = strtolower(trim((string) ($template['title'] ?? '')));
    return $platform === 'instagram' || strpos($title, 'instagram carousel') !== false;
};
$splitInstagramPost = function ($text) {
    $text = (string) $text;
    $parts = preg_split('/\n\s*(?:#{1,6}\s*)?Instagram Caption:?\s*\n/i', $text, 2);
    if (is_array($parts) && count($parts) === 2) {
        return [trim($parts[0]), trim($parts[1])];
    }

    return [trim($text), ''];
};
$historyItemUrl = function ($id) use ($history_item_url_pattern, $history_url) {
    $pattern = (string) ($history_item_url_pattern ?? '');
    if ($pattern !== '') {
        return str_replace('__ID__', (string) (int) $id, $pattern);
    }

    return rtrim((string) $history_url, '/') . '/' . (int) $id;
};
?>
<div class="sp-wrap">
  <?php if ($isInstagramPage && ! empty($instagram_nav)): ?>
  <p class="sp-toolbar">
    <a class="button button--default" href="<?= $h($instagram_nav['create']) ?>">Create</a>
    <a class="button button--default" href="<?= $h($instagram_nav['history']) ?>">History</a>
    <a class="button button--default" href="<?= $h($instagram_nav['carousels']) ?>">Carousels</a>
    <a class="button button--default" href="<?= $h($instagram_nav['news_sources']) ?>">News Sources</a>
    <a class="button button--default" href="<?= $h($instagram_nav['publish']) ?>">Publish</a>
    <a class="button button--default" href="<?= $h($instagram_nav['settings']) ?>">Settings</a>
  </p>
  <?php else: ?>
  <p class="sp-toolbar">
    <a class="button button--default" href="<?= $h($index_url) ?>"><?= $isNewsPage ? 'Generate Post' : 'Generator' ?></a>
    <a class="button button--default" href="<?= $h($settings_url) ?>">Settings</a>
    <a class="button button--default" href="<?= $h($history_url) ?>"><?= $historyTitle ?></a>
  </p>
  <?php endif; ?>

  <?php if (($mode ?? 'list') === 'edit' && ! empty($post)): ?>
    <?php
      $isNews = $isNewsPage || (string) ($post['source'] ?? '') === 'news';
      $isInstagram = ! $isNews && $isInstagramTemplate($post['template_id'] ?? 0);
      [$carouselSlides, $instagramCaption] = $splitInstagramPost($post['post_text'] ?? '');
      $instagramSlideImageBriefs = array_values((array) ($post['instagram_slide_image_briefs'] ?? []));
    ?>

    <?php if ($isNews): ?>
    <!-- ── NEWS POST TEMPLATE ──────────────────────────────── -->
    <section class="sp-card">
      <h2>News Post</h2>
      <p class="sp-muted">
        Generated <?= $h(date('M j, Y g:ia', (int) $post['created_at'])) ?>
        <?php if (! empty($post['external_link'])): ?>
          · <a href="<?= $h($post['external_link']) ?>" target="_blank" rel="noopener">View source article →</a>
        <?php endif; ?>
      </p>

      <?php if (! empty($post['image_missing'])): ?>
        <div class="alert inline warn">
          <p>Image file missing. Regenerate before publishing.</p>
        </div>
      <?php endif; ?>

      <?php if (! empty($post['image_url'])): ?>
        <p><img src="<?= $h($post['image_url']) ?>" alt="" class="sp-preview sp-news-image"></p>
      <?php endif; ?>

      <?= form_open($save_url) ?>
        <input type="hidden" name="regenerate_image" value="1">
        <div class="sp-mini-grid">
          <div class="sp-field">
            <label for="sp-image-prompt">Image Prompt</label>
            <textarea id="sp-image-prompt" name="image_prompt" rows="3"><?= $h($post['image_prompt'] ?? '') ?></textarea>
          </div>
          <div class="sp-field">
            <label for="sp-image-brief">Image Instructions</label>
            <textarea id="sp-image-brief" name="image_brief" rows="3"><?= $h($post['image_brief'] ?? '') ?></textarea>
          </div>
        </div>
        <div class="sp-actions">
          <button type="submit" class="button button--default">Regenerate Image</button>
        </div>
      <?= form_close() ?>

      <?= form_open($save_url) ?>
        <input type="hidden" name="save_post" value="1">
        <input type="hidden" name="prompt" value="<?= $h($post['prompt'] ?? '') ?>">
        <input type="hidden" name="intro_text" value="<?= $h($post['intro_text'] ?? '') ?>">
        <input type="hidden" name="table_of_contents" value="">
        <input type="hidden" name="keywords" value="">
        <input type="hidden" name="category" value="">
        <input type="hidden" name="internal_link" value="">
        <input type="hidden" name="recommended_topics" value="">
        <input type="hidden" name="template_id" value="<?= (int) ($post['template_id'] ?? 0) ?>">

        <div class="sp-field">
          <label for="sp-title">Title</label>
          <input id="sp-title" name="title" type="text" value="<?= $h($post['title'] ?? '') ?>">
        </div>

        <div class="sp-field">
          <label for="sp-post-text">Caption</label>
          <p class="sp-muted">The Instagram caption for this news post.</p>
          <textarea id="sp-post-text" name="post_text" rows="6"><?= $h($post['post_text'] ?? '') ?></textarea>
        </div>

        <div class="sp-field">
          <label for="sp-hashtags">Hashtags</label>
          <input id="sp-hashtags" name="hashtags" type="text" value="<?= $h(implode(', ', $post['hashtags'] ?? [])) ?>">
        </div>

        <div class="sp-field">
          <label for="sp-external">Article URL</label>
          <input id="sp-external" name="external_link" type="text" value="<?= $h($post['external_link'] ?? '') ?>">
        </div>

        <div class="sp-actions">
          <button type="submit" class="button button--primary">Save Changes</button>
          <a class="button button--default" href="<?= $h($history_url) ?>">Back to History</a>
        </div>
      <?= form_close() ?>

      <?php if (! empty($publish_instagram_url)): ?>
      <?= form_open($publish_instagram_url) ?>
        <input type="hidden" name="publish_to_instagram" value="1">
        <div class="sp-publish-instagram-section">
          <h3>Publish to Instagram</h3>
          <?php if (empty($post['image_url'])): ?>
            <p class="sp-danger-text">This post has no image. Generate an image before publishing.</p>
          <?php else: ?>
            <p class="sp-muted">Posts directly to your Instagram professional account using the saved credentials.</p>
          <?php endif; ?>
          <div class="sp-actions">
            <button type="submit" class="button button--primary"<?= empty($post['image_url']) ? ' disabled' : '' ?>>Publish to Instagram</button>
          </div>
        </div>
      <?= form_close() ?>
      <?php endif; ?>
    </section>

    <?php else: ?>
    <!-- ── INSTAGRAM CAROUSEL / GENERAL TEMPLATE ─────────── -->
    <section class="sp-card">
      <h2><?= $isInstagram ? 'Edit Instagram Carousel' : 'Edit Generated Post' ?></h2>
      <p class="sp-muted">Generated <?= $h(date('M j, Y g:ia', (int) $post['created_at'])) ?><?= ! empty($post['source']) ? ' · ' . $h($post['source']) : '' ?></p>

      <?php if (! $isInstagram && ! empty($seo_score)): ?>
        <div class="sp-seo-panel <?= (int) $seo_score['score'] >= 75 ? 'is-strong' : ((int) $seo_score['score'] >= 60 ? 'is-warning' : 'is-weak') ?>">
          <div class="sp-seo-score">
            <span><?= (int) $seo_score['score'] ?></span>
            <small>/100</small>
          </div>
          <div class="sp-seo-summary">
            <strong>SEO Score · <?= $h($seo_score['grade']) ?></strong>
            <p><?= $h($seo_score['summary']) ?></p>
            <p class="sp-muted">Primary keyword: <?= $h($seo_score['primary_keyword'] ?: 'missing') ?> · <?= (int) $seo_score['word_count'] ?> words</p>
          </div>
        </div>

        <div class="sp-seo-checks">
          <?php foreach ($seo_score['checks'] as $check): ?>
            <div class="sp-seo-check <?= ! empty($check['passed']) ? 'is-pass' : 'is-fail' ?>">
              <strong><?= $h($check['label']) ?></strong>
              <span><?= (int) $check['points'] ?>/<?= (int) $check['max'] ?></span>
              <p><?= $h($check['detail']) ?></p>
              <?php if (empty($check['passed'])): ?>
                <small><?= $h($check['recommendation']) ?></small>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if (! empty($post['image_missing'])): ?>
        <div class="alert inline warn">
          <p>Image file is missing for this generated post. Use Regenerate Image to repair it before publishing.</p>
        </div>
      <?php endif; ?>

      <?php if (! $isInstagram && ! empty($post['image_url'])): ?>
        <p><img src="<?= $h($post['image_url']) ?>" alt="" class="sp-preview"></p>
      <?php endif; ?>

      <?= form_open($save_url) ?>
        <input type="hidden" name="regenerate_image" value="1">

        <?php if ($isInstagram): ?>
          <h3>Carousel Images</h3>
          <p class="sp-muted">Image 1 is the generated carousel cover/visual system. Additional slide images need separate generation/storage support before they can be published as a true multi-image carousel.</p>
          <div class="sp-carousel-image-grid">
            <div class="sp-carousel-image-slot is-active">
              <strong>Image 1 · Cover</strong>
              <?php if (! empty($post['image_url'])): ?>
                <img src="<?= $h($post['image_url']) ?>" alt="">
              <?php else: ?>
                <span>No image</span>
              <?php endif; ?>
            </div>
            <?php
            $slideImageUrls = array_values((array) ($post['slide_image_urls'] ?? []));
            $slideCount = max(count($slideImageUrls), 7);
            for ($i = 0; $i < $slideCount; $i++):
                $slideUrl = trim((string) ($slideImageUrls[$i] ?? ''));
                $slotLabel = 'Image ' . ($i + 2);
            ?>
              <div class="sp-carousel-image-slot<?= $slideUrl !== '' ? ' is-active' : '' ?>">
                <strong><?= $slotLabel ?></strong>
                <?php if ($slideUrl !== ''): ?>
                  <img src="<?= $h($slideUrl) ?>" alt="">
                <?php else: ?>
                  <span>Not generated</span>
                <?php endif; ?>
              </div>
            <?php endfor; ?>
          </div>

          <div class="sp-field">
            <label for="sp-image-prompt">Image 1 Prompt</label>
            <textarea id="sp-image-prompt" name="image_prompt" rows="5"><?= $h($post['image_prompt'] ?? '') ?></textarea>
          </div>

          <div class="sp-field">
            <label for="sp-image-brief">Image 1 Brief</label>
            <textarea id="sp-image-brief" name="image_brief" rows="5"><?= $h($post['image_brief'] ?? '') ?></textarea>
          </div>

          <?php if ($instagramSlideImageBriefs): ?>
            <h3>Slide Image Briefs</h3>
            <p class="sp-muted">Instagram slide briefs are mapped from the structured carousel response, for example <code>slides[0].image_brief</code> becomes Image 1 Brief.</p>
            <div class="sp-carousel-builder-grid">
              <?php foreach ($instagramSlideImageBriefs as $index => $slideImageBrief): ?>
                <div class="sp-field">
                  <label for="sp-slide-image-brief-<?= (int) ($index + 1) ?>">Image <?= (int) ($index + 1) ?> Brief</label>
                  <textarea id="sp-slide-image-brief-<?= (int) ($index + 1) ?>" rows="4" readonly><?= $h($slideImageBrief) ?></textarea>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        <?php else: ?>
          <div class="sp-field">
            <label for="sp-image-prompt">Image Prompt</label>
            <textarea id="sp-image-prompt" name="image_prompt" rows="5"><?= $h($post['image_prompt'] ?? '') ?></textarea>
          </div>

          <div class="sp-field">
            <label for="sp-image-brief">Image Brief</label>
            <textarea id="sp-image-brief" name="image_brief" rows="5"><?= $h($post['image_brief'] ?? '') ?></textarea>
          </div>
        <?php endif; ?>

        <div class="sp-actions">
          <button type="submit" class="button button--default">Regenerate Image</button>
        </div>
      <?= form_close() ?>

      <?= form_open($save_url, ['id' => $isInstagram ? 'sp-instagram-edit-form' : 'sp-edit-form']) ?>
        <input type="hidden" name="save_post" value="1">
        <?php if ($isInstagram): ?>
          <input id="sp-post-text" name="post_text" type="hidden" value="<?= $h($post['post_text'] ?? '') ?>">
        <?php endif; ?>

        <div class="sp-field">
          <label for="sp-title">Title</label>
          <input id="sp-title" name="title" type="text" value="<?= $h($post['title'] ?? '') ?>">
        </div>

        <div class="sp-field">
          <label for="sp-template">Template</label>
          <select id="sp-template" name="template_id">
            <?php foreach ($template_options as $value => $label): ?>
              <option value="<?= (int) $value ?>" <?= (int) ($post['template_id'] ?? 0) === (int) $value ? 'selected' : '' ?>><?= $h($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="sp-field">
          <label for="sp-prompt">Original Prompt</label>
          <textarea id="sp-prompt" name="prompt" rows="5"><?= $h($post['prompt'] ?? '') ?></textarea>
        </div>

        <?php if ($isInstagram): ?>
          <div class="sp-field">
            <label for="sp-carousel-slides">Carousel Slide Copy</label>
            <p class="sp-muted">Keep only the slide-by-slide carousel copy here.</p>
            <textarea id="sp-carousel-slides" rows="12"><?= $h($carouselSlides) ?></textarea>
          </div>

          <div class="sp-field">
            <label for="sp-instagram-caption">Instagram Caption</label>
            <p class="sp-muted">Meta publishes one caption for the whole Instagram carousel. Hashtags and @mentions belong here, not on individual slides.</p>
            <textarea id="sp-instagram-caption" rows="7"><?= $h($instagramCaption) ?></textarea>
          </div>
        <?php else: ?>
          <div class="sp-field">
            <label for="sp-post-text">Post Text</label>
            <textarea id="sp-post-text" name="post_text" rows="8"><?= $h($post['post_text'] ?? '') ?></textarea>
          </div>
        <?php endif; ?>

        <?php if (! $isInstagram): ?>
          <div class="sp-field">
            <label for="sp-intro">Intro Text</label>
            <textarea id="sp-intro" name="intro_text" rows="4"><?= $h($post['intro_text'] ?? '') ?></textarea>
          </div>

          <div class="sp-field">
            <label for="sp-toc">Table of Contents</label>
            <textarea id="sp-toc" name="table_of_contents" rows="5"><?= $h(implode("\n", $post['table_of_contents'] ?? [])) ?></textarea>
          </div>
        <?php else: ?>
          <input name="intro_text" type="hidden" value="<?= $h($post['intro_text'] ?? '') ?>">
          <input name="table_of_contents" type="hidden" value="<?= $h(implode("\n", $post['table_of_contents'] ?? [])) ?>">
        <?php endif; ?>

        <?php if (! $isInstagram): ?>
          <div class="sp-field">
            <label for="sp-keywords">SEO Keywords</label>
            <input id="sp-keywords" name="keywords" type="text" value="<?= $h(implode(', ', $post['keywords'] ?? [])) ?>">
          </div>

          <div class="sp-field">
            <label for="sp-category">Blog Category</label>
            <input id="sp-category" name="category" type="text" value="<?= $h($post['category'] ?? '') ?>">
          </div>
        <?php else: ?>
          <input name="keywords" type="hidden" value="<?= $h(implode(', ', $post['keywords'] ?? [])) ?>">
          <input name="category" type="hidden" value="<?= $h($post['category'] ?? '') ?>">
        <?php endif; ?>

        <div class="sp-field">
          <label for="sp-hashtags">Hashtags</label>
          <input id="sp-hashtags" name="hashtags" type="text" value="<?= $h(implode(', ', $post['hashtags'] ?? [])) ?>">
        </div>

        <?php if (! $isInstagram): ?>
          <div class="sp-field">
            <label for="sp-external">External Link</label>
            <input id="sp-external" name="external_link" type="text" value="<?= $h($post['external_link'] ?? '') ?>">
          </div>

          <div class="sp-field">
            <label for="sp-internal">Internal Link</label>
            <input id="sp-internal" name="internal_link" type="text" value="<?= $h($post['internal_link'] ?? '') ?>">
          </div>

          <div class="sp-field">
            <label for="sp-topics">Recommended Topics</label>
            <textarea id="sp-topics" name="recommended_topics" rows="5"><?= $h(implode("\n", $post['recommended_topics'] ?? [])) ?></textarea>
          </div>
        <?php else: ?>
          <input name="external_link" type="hidden" value="">
          <input name="internal_link" type="hidden" value="">
          <input name="recommended_topics" type="hidden" value="">
        <?php endif; ?>

        <div class="sp-actions">
          <button type="submit" class="button button--primary">Save Changes</button>
          <a class="button button--default" href="<?= $h($history_url) ?>">Back to History</a>
        </div>
      <?= form_close() ?>

      <?php if (($isInstagram || $isNews) && ! empty($publish_instagram_url)): ?>
      <?php $pubRecord = $published_record ?? null; ?>
      <?= form_open($publish_instagram_url) ?>
        <input type="hidden" name="publish_to_instagram" value="1">
        <div class="sp-publish-instagram-section">
          <h3>Publish to Instagram</h3>
          <?php if ($pubRecord): ?>
            <div class="sp-published-notice">
              <span class="sp-published-badge sp-published-badge--lg">✓ Published <?= $h(date('M j, Y g:ia', (int) $pubRecord['created_at'])) ?></span>
              <?php if (! empty($pubRecord['media_id'])): ?>
                <span class="sp-muted">· Media ID: <code><?= $h($pubRecord['media_id']) ?></code></span>
              <?php endif; ?>
            </div>
            <p class="sp-muted" style="margin-top:8px;">This post has already been published. Publishing again will create a duplicate on Instagram.</p>
          <?php elseif (empty($post['image_url'])): ?>
            <p class="sp-danger-text">This post has no image. Generate an image before publishing.</p>
          <?php else: ?>
            <p class="sp-muted">Posts directly to your Instagram professional account using the saved credentials.</p>
          <?php endif; ?>
          <div class="sp-actions">
            <button type="submit" class="button <?= $pubRecord ? 'button--default' : 'button--primary' ?>"
                    <?= empty($post['image_url']) ? ' disabled' : '' ?>
                    <?= $pubRecord ? 'onclick="return confirm(\'This post was already published. Publish again and create a duplicate?\')"' : '' ?>>
              <?= $pubRecord ? 'Publish Again' : 'Publish to Instagram' ?>
            </button>
          </div>
        </div>
      <?= form_close() ?>
      <?php endif; ?>
    </section>
    <?php if ($isInstagram): ?>
      <script>
      (function () {
        const form = document.getElementById('sp-instagram-edit-form');
        const hidden = document.getElementById('sp-post-text');
        const slides = document.getElementById('sp-carousel-slides');
        const caption = document.getElementById('sp-instagram-caption');

        if (!form || !hidden || !slides || !caption) {
          return;
        }

        form.addEventListener('submit', function () {
          hidden.value = slides.value.trim() + "\n\nInstagram Caption\n" + caption.value.trim();
        });
      })();
      </script>
    <?php endif; ?>
    <?php endif; // end if ($isNews) else ?>
  <?php else: ?>
  <section class="sp-card">
    <h2><?= $h($historyTitle) ?></h2>
    <?php if (empty($rows)): ?>
      <p class="sp-muted"><?= $isInstagramPage ? 'No Instagram generations yet.' : ($isNewsPage ? 'No news posts yet.' : 'No SocialPoster generations yet.') ?></p>
    <?php else: ?>
      <div class="sp-history-list">
      <?php foreach ($rows as $row): ?>
        <?php
          $intro = trim((string) ($row['intro_text'] ?? ''));
          $keywords = array_slice((array) ($row['keywords'] ?? []), 0, 4);
          $templateLabel = ! empty($row['template_id']) && ! empty($template_options[(int) $row['template_id']])
              ? $template_options[(int) $row['template_id']]
              : '';
        ?>
        <article class="sp-history-row">
          <div class="sp-history-thumb">
            <?php if (! empty($row['image_url'])): ?>
              <img src="<?= $h($row['image_url']) ?>" alt="">
            <?php else: ?>
              <span>No image</span>
            <?php endif; ?>
          </div>
          <div class="sp-history-main">
            <div class="sp-history-head">
              <h3><?= $h($row['title'] ?: 'Generated social post') ?></h3>
              <?= $templateBadge($row['template_id'] ?? 0) ?>
              <?php
                $pubTs = ($published_ids ?? [])[(int) ($row['id'] ?? 0)] ?? null;
                if ($pubTs):
              ?>
                <span class="sp-published-badge">✓ Published <?= $h(date('M j', $pubTs)) ?></span>
              <?php endif; ?>
              <?php if (! $isInstagramPage && ! empty($row['seo_score'])): ?>
                <div class="sp-seo-inline <?= (int) $row['seo_score']['score'] >= 75 ? 'is-strong' : ((int) $row['seo_score']['score'] >= 60 ? 'is-warning' : 'is-weak') ?>">
                  <strong>SEO <?= (int) $row['seo_score']['score'] ?>/100</strong>
                  <span><?= $h($row['seo_score']['grade']) ?></span>
                </div>
              <?php endif; ?>
            </div>
            <p class="sp-muted">
              #<?= (int) $row['id'] ?> · <?= $h(date('M j, Y g:ia', (int) $row['created_at'])) ?><?= ! empty($row['source']) ? ' · ' . $h($row['source']) : '' ?><?= $templateLabel !== '' ? ' · ' . $h($templateLabel) : '' ?>
            </p>
            <?php if (! empty($row['image_missing'])): ?>
              <p class="sp-danger-text">Image file missing. Open this post and regenerate the image before publishing.</p>
            <?php endif; ?>
            <?php if ($intro !== ''): ?>
              <p class="sp-history-excerpt"><?= $h(strlen($intro) > 220 ? substr($intro, 0, 217) . '...' : $intro) ?></p>
            <?php endif; ?>
            <div class="sp-history-meta">
              <?php if (! empty($row['category'])): ?>
                <span><?= $h($row['category']) ?></span>
              <?php endif; ?>
              <?php foreach ($keywords as $keyword): ?>
                <span><?= $h($keyword) ?></span>
              <?php endforeach; ?>
              <?php if (! empty($row['internal_link'])): ?>
                <span>Internal link</span>
              <?php endif; ?>
              <?php if (! empty($row['external_link'])): ?>
                <span>External link</span>
              <?php endif; ?>
            </div>
          </div>
          <div class="sp-actions">
            <a class="button button--primary" href="<?= $h($historyItemUrl($row['id'] ?? 0)) ?>">View / Edit</a>
            <?= form_open($history_url) ?>
              <input type="hidden" name="delete_id" value="<?= (int) $row['id'] ?>">
              <button type="submit" class="button button--danger">Delete</button>
            <?= form_close() ?>
          </div>
        </article>
      <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
  <?php endif; ?>
</div>
