<?php
ee()->load->helper('form');
$spView = require __DIR__ . '/_view_helpers.php';
$h = fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$generationMap = is_array($generation_map ?? null) ? $generation_map : [];
$publishedStats = is_array($published_stats ?? null) ? $published_stats : [];
$templateMeta = is_array($template_meta ?? null) ? $template_meta : [];

$blogCount      = is_array($published_blogs ?? null) ? count($published_blogs) : 0;
$instagramCount = is_array($published_instagrams ?? null) ? count($published_instagrams) : 0;
$facebookCount  = is_array($published_facebooks ?? null) ? count($published_facebooks) : 0;
?>
<div class="sp-wrap">

  <div class="sp-toolbar">
    <a class="button button--default" href="<?= $h($history_url) ?>">History</a>
    <a class="button button--default" href="<?= $h($publish_url) ?>">Publish</a>
  </div>

  <section class="sp-card">
    <h2>Published Output</h2>
    <p class="sp-muted">Completed publishing activity across blog, Instagram, and Facebook. Use this page to trace each published result back to its source generation.</p>
    <div class="sp-review-overview">
      <article class="sp-review-stat">
        <strong><?= (int) ($publishedStats['total'] ?? 0) ?></strong>
        <span>Total published items</span>
      </article>
      <article class="sp-review-stat">
        <strong><?= (int) ($publishedStats['blogs'] ?? 0) ?></strong>
        <span>Blog entries</span>
      </article>
      <article class="sp-review-stat">
        <strong><?= (int) ($publishedStats['instagrams'] ?? 0) ?></strong>
        <span>Instagram posts</span>
      </article>
      <article class="sp-review-stat">
        <strong><?= (int) ($publishedStats['facebooks'] ?? 0) ?></strong>
        <span>Facebook posts</span>
      </article>
    </div>
  </section>

  <!-- Tab bar -->
  <div class="sp-pub-tabs">
    <button type="button" class="sp-pub-tab is-active" data-tab="blogs">
      Published Blogs <span class="sp-pub-count"><?= (int) $blogCount ?></span>
    </button>
    <button type="button" class="sp-pub-tab" data-tab="instagram">
      Published Instagram <span class="sp-pub-count"><?= (int) $instagramCount ?></span>
    </button>
    <button type="button" class="sp-pub-tab" data-tab="facebook">
      Published Facebook <span class="sp-pub-count"><?= (int) $facebookCount ?></span>
    </button>
  </div>

  <!-- Blogs tab -->
  <section id="sp-pub-tab-blogs" class="sp-pub-panel">
    <div class="sp-card">
      <h2>Published Blog Entries</h2>
      <?php if (empty($published_blogs)): ?>
        <p class="sp-muted">No generated blog entries have been published yet. Use <a href="<?= $h($publish_url) ?>">Publish &rsaquo; Blogs</a> to publish one.</p>
      <?php else: ?>
        <?php foreach ($published_blogs as $blog): ?>
          <?php
            $generation = $generationMap[(int) ($blog['generation_id'] ?? 0)] ?? null;
            $template = $generation ? $spView->templateTitle($templateMeta, $generation['template_id'] ?? 0) : '';
          ?>
          <article class="sp-post-item sp-post-item--published">
            <div class="sp-published-head">
              <div>
                <h3><?= $h($blog['title'] ?: 'Generated blog entry') ?></h3>
                <p class="sp-muted">
                  Entry #<?= (int) $blog['entry_id'] ?> · <?= $h($blog['status']) ?> · <?= $h(date('M j, Y g:ia', (int) $blog['created_at'])) ?>
                </p>
              </div>
              <div class="sp-history-statuses">
                <span class="sp-status-badge sp-status-badge--success">Published to Blog</span>
              </div>
            </div>
            <?php if ($generation): ?>
              <div class="sp-review-grid" style="margin-top:12px;">
                <div>
                  <strong>Source generation</strong>
                  <p>#<?= (int) $generation['id'] ?><?= $template !== '' ? ' · ' . $h($template) : '' ?></p>
                </div>
                <div>
                  <strong>Original draft title</strong>
                  <p><?= $h((string) ($generation['title'] ?? '')) ?: 'Untitled draft' ?></p>
                </div>
              </div>
            <?php endif; ?>
            <div class="sp-actions">
              <?php if (! empty($blog['generation_id'])): ?>
                <a class="button button--default" href="<?= $h($history_url . '/' . (int) $blog['generation_id']) ?>">Open Draft</a>
              <?php endif; ?>
              <a class="button button--primary" href="<?= $h($blog['edit_url']) ?>">Edit Entry</a>
              <a class="button button--default" href="<?= $h($blog['url']) ?>" target="_blank" rel="noopener">View Blog Post</a>
              <?= form_open($published_url) ?>
                <input type="hidden" name="delete_blog" value="1">
                <input type="hidden" name="published_blog_id" value="<?= (int) $blog['id'] ?>">
                <button type="submit" class="button button--danger">Delete Blog Entry</button>
              <?= form_close() ?>
            </div>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>

  <!-- Instagram tab -->
  <section id="sp-pub-tab-instagram" class="sp-pub-panel" style="display:none;">
    <div class="sp-card">
      <h2>Published Instagram Posts</h2>
      <?php if (empty($published_instagrams)): ?>
        <p class="sp-muted">No posts have been published to Instagram yet. Use <a href="<?= $h($publish_instagram_url) ?>">Publish &rsaquo; Instagram</a> to send a post.</p>
      <?php else: ?>
        <?php foreach ($published_instagrams as $row): ?>
          <?php
            $generation = $generationMap[(int) ($row['generation_id'] ?? 0)] ?? null;
            $template = $generation ? $spView->templateTitle($templateMeta, $generation['template_id'] ?? 0) : '';
          ?>
          <article class="sp-post-item sp-post-item--published">
            <div class="sp-published-head">
              <div>
                <h3><?= $generation ? $h((string) ($generation['title'] ?? 'Instagram post')) : 'Media ID: ' . $h($row['media_id']) ?></h3>
                <p class="sp-muted">
                  <?= $h(date('M j, Y g:ia', (int) $row['created_at'])) ?>
                  <?php if ($template !== ''): ?>
                    · <?= $h($template) ?>
                  <?php endif; ?>
                </p>
              </div>
              <div class="sp-history-statuses">
                <span class="sp-status-badge sp-status-badge--success">Published to Instagram</span>
              </div>
            </div>
            <div class="sp-review-grid" style="margin-top:12px;">
              <div>
                <strong>Media ID</strong>
                <p><code><?= $h($row['media_id']) ?></code></p>
              </div>
              <div>
                <strong>Source generation</strong>
                <p>
                  <?php if (! empty($row['generation_id'])): ?>
                    <a href="<?= $h($instagram_history_url . '/' . (int) $row['generation_id']) ?>">Generated #<?= (int) $row['generation_id'] ?></a>
                  <?php else: ?>
                    No generation linked
                  <?php endif; ?>
                </p>
              </div>
            </div>
            <?php if (! empty($row['caption'])): ?>
              <p class="sp-history-excerpt"><?= $h($spView->excerpt((string) $row['caption'], 200)) ?></p>
            <?php endif; ?>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>

  <!-- Facebook tab -->
  <section id="sp-pub-tab-facebook" class="sp-pub-panel" style="display:none;">
    <div class="sp-card">
      <h2>Published Facebook Posts</h2>
      <?php if (empty($published_facebooks)): ?>
        <p class="sp-muted">No posts have been published to Facebook yet. Use <a href="<?= $h($publish_facebook_url) ?>">Publish &rsaquo; Facebook</a> to send a post.</p>
      <?php else: ?>
        <?php foreach ($published_facebooks as $row): ?>
          <?php
            $generation = $generationMap[(int) ($row['generation_id'] ?? 0)] ?? null;
            $template = $generation ? $spView->templateTitle($templateMeta, $generation['template_id'] ?? 0) : '';
          ?>
          <article class="sp-post-item sp-post-item--published">
            <div class="sp-published-head">
              <div>
                <h3><?= $generation ? $h((string) ($generation['title'] ?? 'Facebook post')) : 'Post ID: ' . $h($row['post_id']) ?></h3>
                <p class="sp-muted">
                  <?= $h(date('M j, Y g:ia', (int) $row['created_at'])) ?>
                  <?php if ($template !== ''): ?>
                    · <?= $h($template) ?>
                  <?php endif; ?>
                </p>
              </div>
              <div class="sp-history-statuses">
                <span class="sp-status-badge sp-status-badge--success">Published to Facebook</span>
              </div>
            </div>
            <div class="sp-review-grid" style="margin-top:12px;">
              <div>
                <strong>Post ID</strong>
                <p><code><?= $h($row['post_id']) ?></code></p>
              </div>
              <div>
                <strong>Source generation</strong>
                <p>
                  <?php if (! empty($row['generation_id'])): ?>
                    <a href="<?= $h($history_url . '/' . (int) $row['generation_id']) ?>">Generated #<?= (int) $row['generation_id'] ?></a>
                  <?php else: ?>
                    No generation linked
                  <?php endif; ?>
                </p>
              </div>
            </div>
            <?php if (! empty($row['message'])): ?>
              <p class="sp-history-excerpt"><?= $h($spView->excerpt((string) $row['message'], 200)) ?></p>
            <?php endif; ?>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>

</div>


<script>
(function () {
  var tabs   = document.querySelectorAll('.sp-pub-tab');
  var panels = document.querySelectorAll('.sp-pub-panel');

  function activate(name) {
    tabs.forEach(function (t) { t.classList.toggle('is-active', t.dataset.tab === name); });
    panels.forEach(function (p) { p.style.display = (p.id === 'sp-pub-tab-' + name) ? '' : 'none'; });
    if (history.replaceState) {
      history.replaceState(null, '', '#' + name);
    }
  }

  tabs.forEach(function (t) {
    t.addEventListener('click', function () { activate(t.dataset.tab); });
  });

  // Activate from URL hash (#blogs, #instagram, or #facebook)
  var initial = (location.hash || '').replace('#', '');
  if (initial === 'instagram' || initial === 'facebook' || initial === 'blogs') {
    activate(initial);
  }
}());
</script>
