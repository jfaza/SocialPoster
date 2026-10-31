<?php
ee()->load->helper('form');
$spView = require __DIR__ . '/_view_helpers.php';
$h = fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$pageModeRaw = (string) ($page_mode ?? 'general');
$pageMode = in_array($pageModeRaw, ['instagram', 'news'], true) ? $pageModeRaw : 'general';
$isInstagramPage = $pageMode === 'instagram';
$isNewsPage = $pageMode === 'news';
$historyTitle = (string) ($history_title ?? 'Generated Posts');
$publishedBlogMap = is_array($published_blog_map ?? null) ? $published_blog_map : [];
$publishedFacebookMap = is_array($published_facebook_map ?? null) ? $published_facebook_map : [];
$scheduleMap = is_array($schedule_map ?? null) ? $schedule_map : [];
$scheduleRunMap = is_array($schedule_run_map ?? null) ? $schedule_run_map : [];
$reviewStats = is_array($review_stats ?? null) ? $review_stats : [];
$templateMeta = is_array($template_meta ?? null) ? $template_meta : [];
$historyItemUrlPattern = (string) ($history_item_url_pattern ?? '');
?>
<div class="sp-wrap">

  <?php if (($mode ?? 'list') === 'edit' && ! empty($post)): ?>
    <?php
      $postSource = (string) ($post['source'] ?? '');
      $isNews = $isNewsPage || $postSource === 'news';
      $isInstagramCarousel = ! $isNews && $spView->isInstagramTemplate($templateMeta, $post['template_id'] ?? 0);
      $isInstagramSingle = ! $isNews && ! $isInstagramCarousel && ($isInstagramPage || $postSource === 'instagram');
      $isInstagram = $isInstagramCarousel || $isInstagramSingle;
      $isWebsiteTemplate = $spView->isWebsiteTemplate($templateMeta, $post['template_id'] ?? 0);
      $isFacebookTemplate = $spView->isFacebookTemplate($templateMeta, $post['template_id'] ?? 0);
      $socialQuality = $isFacebookTemplate ? $spView->socialQuality((string) ($post['post_text'] ?? '')) : [];
      [$carouselSlides, $instagramCaption] = $spView->splitInstagramPost($post['post_text'] ?? '');
      $instagramSlideImageBriefs = array_values((array) ($post['instagram_slide_image_briefs'] ?? []));
      $postTemplateTitle = $spView->templateTitle($templateMeta, $post['template_id'] ?? 0);
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
    <!-- ── INSTAGRAM / GENERAL TEMPLATE ───────────────────── -->
    <section class="sp-card">
      <h2><?= $isInstagramCarousel ? 'Edit Instagram Carousel' : ($isInstagramSingle ? 'Edit Instagram Post' : 'Edit Generated Post') ?></h2>
      <p class="sp-muted">
        Generated <?= $h(date('M j, Y g:ia', (int) $post['created_at'])) ?><?= ! empty($post['source']) ? ' · ' . $h($post['source']) : '' ?>
        <?php if ($postTemplateTitle !== ''): ?>
          · Template: <?= $h($postTemplateTitle) ?>
        <?php endif; ?>
      </p>

      <?php if (! $isInstagram): ?>
        <?php
          $blogRecord = is_array($published_blog_record ?? null) ? $published_blog_record : null;
          $facebookRecord = is_array($published_facebook_record ?? null) ? $published_facebook_record : null;
          $scheduleRecord = is_array($schedule_record ?? null) ? $schedule_record : null;
          $scheduleRunRecord = is_array($schedule_run_record ?? null) ? $schedule_run_record : null;
        ?>
        <div class="sp-review-hub">
          <div class="sp-review-summary">
            <div class="sp-review-summary__head">
              <h3>Review Status</h3>
              <div class="sp-review-statuses">
                <?php if ($blogRecord): ?>
                  <?= $spView->statusBadge('Published to Blog', 'success', $h) ?>
                <?php else: ?>
                  <?= $spView->statusBadge('Draft', 'warning', $h) ?>
                <?php endif; ?>
                <?php if ($facebookRecord): ?>
                  <?= $spView->statusBadge('Published to Facebook', 'info', $h) ?>
                <?php endif; ?>
                <?php if ($scheduleRecord): ?>
                  <?= $spView->statusBadge('From Schedule', 'neutral', $h) ?>
                <?php endif; ?>
                <?php if (! empty($post['image_missing'])): ?>
                  <?= $spView->statusBadge('Image Needs Repair', 'danger', $h) ?>
                <?php endif; ?>
              </div>
            </div>
            <div class="sp-review-grid">
              <div>
                <strong>Template</strong>
                <p><?= $h($postTemplateTitle !== '' ? $postTemplateTitle : 'No template saved') ?></p>
              </div>
              <div>
                <strong>Source</strong>
                <p><?= $h((string) ($post['source'] ?? 'manual') ?: 'manual') ?></p>
              </div>
              <div>
                <strong>Origin</strong>
                <p>
                  <?php if ($scheduleRecord): ?>
                    Schedule: <?= $h((string) ($scheduleRecord['title'] ?? ('#' . (int) ($scheduleRecord['id'] ?? 0)))) ?>
                  <?php else: ?>
                    Manual generation
                  <?php endif; ?>
                </p>
              </div>
              <?php if ($scheduleRunRecord): ?>
              <div>
                <strong>Run Result</strong>
                <p>
                  <?= $h(date('M j, Y g:ia', (int) ($scheduleRunRecord['started_at'] ?? 0))) ?>
                  · <?= $h(ucwords(str_replace('_', ' ', (string) ($scheduleRunRecord['status'] ?? 'success')))) ?>
                </p>
              </div>
              <?php endif; ?>
              <div>
                <strong>Next Step</strong>
                <p>
                  <?php if ($isWebsiteTemplate && ! $blogRecord): ?>
                    Publish this draft to Blog
                  <?php elseif ($isFacebookTemplate && ! $facebookRecord): ?>
                    Publish this post to Facebook
                  <?php elseif ($isWebsiteTemplate && ! $facebookRecord): ?>
                    Optional: publish this content to Facebook
                  <?php else: ?>
                    Review this content and any published outputs
                  <?php endif; ?>
                </p>
              </div>
            </div>
            <div class="sp-actions sp-actions--review">
              <?php if ($isWebsiteTemplate && ! $blogRecord): ?>
                <a class="button button--primary" href="<?= $h($publish_url) ?>">Publish to Blog</a>
              <?php elseif ($isWebsiteTemplate): ?>
                <a class="button button--default" href="<?= $h($published_url) ?>">View Published</a>
              <?php endif; ?>
              <?php if ($isWebsiteTemplate || $isFacebookTemplate): ?>
                <a class="button button--default" href="<?= $h($publish_facebook_url) ?>">
                  <?= $facebookRecord ? 'Facebook Published' : 'Publish to Facebook' ?>
                </a>
              <?php endif; ?>
              <?php if ($scheduleRecord): ?>
                <a class="button button--default" href="<?= $h(rtrim((string) $calendar_url, '/') . '/' . (int) ($scheduleRecord['id'] ?? 0)) ?>#tab=t-1">Open Schedule</a>
              <?php endif; ?>
            </div>
            <?php if ($scheduleRunRecord && ! empty($scheduleRunRecord['message'])): ?>
              <p class="sp-muted">Schedule note: <?= $h($scheduleRunRecord['message']) ?></p>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>

      <?php if ($isWebsiteTemplate && ! empty($seo_score)): ?>
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

        <?php
          $verdictLabels = ['ready' => 'Ready to publish', 'fix_first' => 'Fix before publishing', 'rewrite' => 'Needs a rewrite'];
          $verdictClass = ['ready' => 'is-strong', 'fix_first' => 'is-warning', 'rewrite' => 'is-weak'];
          $categoryLabels = ['accuracy' => 'Accuracy', 'unsupported_claim' => 'Unsupported claim', 'freshness' => 'Freshness', 'title_promise' => 'Title promise', 'filler' => 'Filler', 'sources' => 'Sources', 'prompt_leak' => 'Mentions the prompt', 'placeholder' => 'Placeholder', 'other' => 'Other'];
          $reviewVerdict = (string) ($review['verdict'] ?? '');
        ?>
        <div class="sp-review-panel <?= $review ? ($verdictClass[$reviewVerdict] ?? '') : '' ?>">
          <div class="sp-review-head">
            <div>
              <strong>Editor review<?= $review ? ' · ' . $h($verdictLabels[$reviewVerdict] ?? $reviewVerdict) : '' ?></strong>
              <?php if ($review): ?>
                <p class="sp-muted">Reviewed <?= $h(date('M j, Y g:ia', (int) $review['created_at'])) ?> by <?= $h($review['model']) ?>. Checks accuracy, freshness, sources and filler; it can't browse, so treat flags as things to verify.</p>
              <?php else: ?>
                <p class="sp-muted">Not reviewed yet. An AI editor checks accuracy, freshness, sources and filler (about a cent per run).</p>
              <?php endif; ?>
            </div>
            <?php if (! empty($review_available)): ?>
              <?= form_open($save_url) ?>
                <input type="hidden" name="run_review" value="1">
                <button type="submit" class="button button--secondary"><?= $review ? 'Re-run review' : 'Run review' ?></button>
              <?= form_close() ?>
            <?php endif; ?>
          </div>
          <?php if ($review): ?>
            <?php if (! empty($review['summary'])): ?><p><?= $h($review['summary']) ?></p><?php endif; ?>
            <?php if (! empty($review['issues'])): ?>
              <ol class="sp-review-issues">
                <?php foreach ($review['issues'] as $issue): ?>
                  <li class="is-<?= $h($issue['severity'] ?? 'low') ?>">
                    <span class="sp-review-tag"><?= $h(ucfirst((string) ($issue['severity'] ?? ''))) ?> · <?= $h($categoryLabels[$issue['category'] ?? 'other'] ?? 'Other') ?></span>
                    <?php if (! empty($issue['quote'])): ?><q><?= $h($issue['quote']) ?></q><?php endif; ?>
                    <?php if (! empty($issue['fix'])): ?><p>Fix: <?= $h($issue['fix']) ?></p><?php endif; ?>
                  </li>
                <?php endforeach; ?>
              </ol>
            <?php else: ?>
              <p class="sp-muted">No issues found.</p>
            <?php endif; ?>
          <?php endif; ?>
        </div>

        <div class="sp-review-panel <?= empty($duplicates) ? 'is-strong' : 'is-warning' ?>">
          <strong>Duplicate check · <?= empty($duplicates) ? 'No overlap found' : count($duplicates) . ' similar ' . (count($duplicates) === 1 ? 'post' : 'posts') ?></strong>
          <?php if (empty($duplicates)): ?>
            <p class="sp-muted">No published post or recent draft covers the same topic.</p>
          <?php else: ?>
            <ul class="sp-review-duplicates">
              <?php foreach ($duplicates as $duplicate): ?>
                <li>
                  <?php if (($duplicate['kind'] ?? '') === 'published'): ?>
                    Published: <a href="<?= $h($duplicate['url']) ?>" target="_blank" rel="noopener"><?= $h($duplicate['title']) ?></a>
                  <?php else: ?>
                    Draft #<?= (int) $duplicate['id'] ?>: <a href="<?= $h(str_replace('__ID__', (string) (int) $duplicate['id'], $history_item_url_pattern)) ?>"><?= $h($duplicate['title']) ?></a>
                  <?php endif; ?>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <?php if ($isFacebookTemplate && ! empty($socialQuality)): ?>
        <div class="sp-social-panel <?= (int) $socialQuality['score'] >= 80 ? 'is-strong' : ((int) $socialQuality['score'] >= 60 ? 'is-warning' : 'is-weak') ?>">
          <div class="sp-social-score">
            <span><?= (int) $socialQuality['score'] ?></span>
            <small>/100</small>
          </div>
          <div class="sp-social-summary">
            <strong>Facebook Fit · <?= $h($socialQuality['grade']) ?></strong>
            <p><?= $h($socialQuality['summary']) ?></p>
            <p class="sp-muted"><?= (int) ($socialQuality['char_count'] ?? 0) ?> characters · tuned for short in-feed reading</p>
          </div>
        </div>

        <div class="sp-social-checks">
          <?php foreach ($socialQuality['checks'] as $check): ?>
            <div class="sp-social-check <?= ! empty($check['passed']) ? 'is-pass' : 'is-fail' ?>">
              <strong><?= $h($check['label']) ?></strong>
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

        <?php if ($isInstagramCarousel): ?>
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
        <?php elseif ($isInstagramSingle): ?>
          <?php if (! empty($post['image_url'])): ?>
            <p><img src="<?= $h($post['image_url']) ?>" alt="" class="sp-preview"></p>
          <?php endif; ?>

          <div class="sp-field">
            <label for="sp-image-prompt">Image Prompt</label>
            <textarea id="sp-image-prompt" name="image_prompt" rows="5"><?= $h($post['image_prompt'] ?? '') ?></textarea>
          </div>

          <div class="sp-field">
            <label for="sp-image-brief">Image Instructions</label>
            <textarea id="sp-image-brief" name="image_brief" rows="5"><?= $h($post['image_brief'] ?? '') ?></textarea>
          </div>
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
        <?php if ($isInstagramCarousel): ?>
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

        <?php if ($isInstagramCarousel): ?>
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
        <?php elseif ($isInstagramSingle): ?>
          <div class="sp-field">
            <label for="sp-post-text">Instagram Caption</label>
            <p class="sp-muted">The caption that will be published with this single-image post.</p>
            <textarea id="sp-post-text" name="post_text" rows="7"><?= $h($post['post_text'] ?? '') ?></textarea>
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
    <?php if ($isInstagramCarousel): ?>
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
    <?php if (! $isInstagramPage && ! $isNewsPage && $reviewStats): ?>
      <div class="sp-review-overview">
        <article class="sp-review-stat">
          <strong><?= (int) ($reviewStats['total'] ?? 0) ?></strong>
          <span>Total drafts</span>
        </article>
        <article class="sp-review-stat">
          <strong><?= (int) ($reviewStats['ready_to_publish'] ?? 0) ?></strong>
          <span>Ready to publish</span>
        </article>
        <article class="sp-review-stat">
          <strong><?= (int) ($reviewStats['published_blog'] ?? 0) ?></strong>
          <span>Published to blog</span>
        </article>
        <article class="sp-review-stat">
          <strong><?= (int) ($reviewStats['scheduled_origin'] ?? 0) ?></strong>
          <span>Generated by schedule</span>
        </article>
        <?php if ((int) ($reviewStats['image_issues'] ?? 0) > 0): ?>
          <article class="sp-review-stat is-danger">
            <strong><?= (int) ($reviewStats['image_issues'] ?? 0) ?></strong>
            <span>Image issues</span>
          </article>
        <?php endif; ?>
      </div>
    <?php endif; ?>
    <?php if (empty($rows)): ?>
      <p class="sp-muted"><?= $isInstagramPage ? 'No Instagram generations yet.' : ($isNewsPage ? 'No news posts yet.' : 'No SocialPoster generations yet.') ?></p>
    <?php else: ?>
      <div class="sp-history-list">
      <?php foreach ($rows as $row): ?>
        <?php
          $intro = trim((string) ($row['intro_text'] ?? ''));
          $keywords = array_slice((array) ($row['keywords'] ?? []), 0, 4);
          $templateLabel = $spView->templateTitle($templateMeta, $row['template_id'] ?? 0);
          $blogRecord = $publishedBlogMap[(int) ($row['id'] ?? 0)] ?? null;
          $facebookRecord = $publishedFacebookMap[(int) ($row['id'] ?? 0)] ?? null;
          $scheduleRecord = $scheduleMap[(int) ($row['schedule_id'] ?? 0)] ?? null;
          $scheduleRunRecord = $scheduleRunMap[(int) ($row['id'] ?? 0)] ?? null;
          $isWebsiteRow = $spView->isWebsiteTemplate($templateMeta, $row['template_id'] ?? 0);
          $isFacebookRow = $spView->isFacebookTemplate($templateMeta, $row['template_id'] ?? 0);
          $socialQualityRow = $isFacebookRow ? $spView->socialQuality((string) ($row['post_text'] ?? '')) : [];
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
              <div class="sp-history-title-block">
                <h3><?= $h($row['title'] ?: 'Generated social post') ?></h3>
                <div class="sp-history-statuses">
                  <?= $spView->templateBadge($templateMeta, $row['template_id'] ?? 0, $h) ?>
                  <?php if ($isWebsiteRow && $blogRecord): ?>
                    <?= $spView->statusBadge('Published to Blog', 'success', $h) ?>
                  <?php elseif ($isWebsiteRow): ?>
                    <?= $spView->statusBadge('Draft', 'warning', $h) ?>
                  <?php endif; ?>
                  <?php if ($facebookRecord): ?>
                    <?= $spView->statusBadge('Published to Facebook', 'info', $h) ?>
                  <?php endif; ?>
                  <?php if ($scheduleRecord): ?>
                    <?= $spView->statusBadge('Scheduled Source', 'neutral', $h) ?>
                  <?php endif; ?>
                  <?php if (! empty($row['image_missing'])): ?>
                    <?= $spView->statusBadge('Image Issue', 'danger', $h) ?>
                  <?php endif; ?>
                </div>
              </div>
              <?php if (! $isInstagramPage && $isWebsiteRow && ! empty($row['seo_score'])): ?>
                <div class="sp-seo-inline <?= (int) $row['seo_score']['score'] >= 75 ? 'is-strong' : ((int) $row['seo_score']['score'] >= 60 ? 'is-warning' : 'is-weak') ?>">
                  <strong>SEO <?= (int) $row['seo_score']['score'] ?>/100</strong>
                  <span><?= $h($row['seo_score']['grade']) ?></span>
                </div>
              <?php elseif ($isFacebookRow && ! empty($socialQualityRow)): ?>
                <div class="sp-social-inline <?= (int) $socialQualityRow['score'] >= 80 ? 'is-strong' : ((int) $socialQualityRow['score'] >= 60 ? 'is-warning' : 'is-weak') ?>">
                  <strong>Facebook <?= (int) $socialQualityRow['score'] ?>/100</strong>
                  <span><?= $h($socialQualityRow['grade']) ?></span>
                </div>
              <?php endif; ?>
            </div>
            <p class="sp-muted">
              #<?= (int) $row['id'] ?> · <?= $h(date('M j, Y g:ia', (int) $row['created_at'])) ?><?= ! empty($row['source']) ? ' · ' . $h($row['source']) : '' ?>
            </p>
            <?php if ($templateLabel !== ''): ?>
              <p class="sp-muted">Template: <?= $h($templateLabel) ?></p>
            <?php endif; ?>
            <?php if ($scheduleRecord): ?>
              <p class="sp-muted">Origin: schedule <?= $h((string) ($scheduleRecord['title'] ?? ('#' . (int) ($scheduleRecord['id'] ?? 0)))) ?></p>
              <p><a href="<?= $h(rtrim((string) $calendar_url, '/') . '/' . (int) ($scheduleRecord['id'] ?? 0)) ?>#tab=t-1">Open schedule</a></p>
            <?php endif; ?>
            <?php if ($scheduleRunRecord): ?>
              <p class="sp-muted">
                Scheduled run: <?= $h(date('M j, Y g:ia', (int) ($scheduleRunRecord['started_at'] ?? 0))) ?>
                <?php if ((int) ($scheduleRunRecord['duplicate_retry_count'] ?? 0) > 0): ?>
                  · Accepted after <?= (int) $scheduleRunRecord['duplicate_retry_count'] ?> duplicate <?= (int) $scheduleRunRecord['duplicate_retry_count'] === 1 ? 'retry' : 'retries' ?>
                <?php else: ?>
                  · <?= $h(ucfirst((string) ($scheduleRunRecord['topic_source'] ?? 'fresh'))) ?> topic chosen
                <?php endif; ?>
              </p>
              <?php if (! empty($scheduleRunRecord['message'])): ?>
                <p class="sp-muted"><?= $h($scheduleRunRecord['message']) ?></p>
              <?php endif; ?>
            <?php endif; ?>
            <?php if (! empty($row['image_missing'])): ?>
              <p class="sp-danger-text">Image file missing. Open this post and regenerate the image before publishing.</p>
            <?php endif; ?>
            <?php if ($intro !== ''): ?>
              <p class="sp-history-excerpt"><?= $h($spView->excerpt($intro, 220)) ?></p>
            <?php endif; ?>
            <p class="sp-review-next">
              <?php if ($isWebsiteRow && ! $blogRecord): ?>
                Next step: review and publish this draft to Blog.
              <?php elseif ($isFacebookRow && ! $facebookRecord): ?>
                Next step: review and publish this post to Facebook.
              <?php elseif ($isWebsiteRow && ! $facebookRecord): ?>
                Next step: optionally publish or adapt this post for Facebook.
              <?php else: ?>
                Next step: review published results or reuse this prompt.
              <?php endif; ?>
            </p>
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
            <a class="button button--primary" href="<?= $h($spView->itemUrl($historyItemUrlPattern, (string) $history_url, $row['id'] ?? 0)) ?>">View / Edit</a>
            <?php if (! $isInstagramPage && ! $isNewsPage && $isWebsiteRow): ?>
              <a class="button button--default" href="<?= $h($blogRecord ? $published_url : $publish_url) ?>">
                <?= $blogRecord ? 'Published' : 'Publish' ?>
              </a>
            <?php elseif (! $isInstagramPage && ! $isNewsPage && $isFacebookRow): ?>
              <a class="button button--default" href="<?= $h($publish_facebook_url) ?>">
                <?= $facebookRecord ? 'Facebook Published' : 'Publish to Facebook' ?>
              </a>
            <?php endif; ?>
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
