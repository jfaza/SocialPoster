<?php
ee()->load->helper('form');
$h = fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

$blogCount      = is_array($published_blogs ?? null) ? count($published_blogs) : 0;
$instagramCount = is_array($published_instagrams ?? null) ? count($published_instagrams) : 0;
?>
<div class="sp-wrap">

  <div class="sp-toolbar">
    <a class="button button--default" href="<?= $h($publish_url) ?>">Publish Blog</a>
    <a class="button button--default" href="<?= $h($publish_instagram_url) ?>">Publish Instagram</a>
  </div>

  <!-- Tab bar -->
  <div class="sp-pub-tabs">
    <button type="button" class="sp-pub-tab is-active" data-tab="blogs">
      Published Blogs <span class="sp-pub-count"><?= (int) $blogCount ?></span>
    </button>
    <button type="button" class="sp-pub-tab" data-tab="instagram">
      Published Instagram <span class="sp-pub-count"><?= (int) $instagramCount ?></span>
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
          <article class="sp-post-item">
            <h3><?= $h($blog['title'] ?: 'Generated blog entry') ?></h3>
            <p class="sp-muted">
              Entry #<?= (int) $blog['entry_id'] ?> · <?= $h($blog['status']) ?> · <?= $h(date('M j, Y g:ia', (int) $blog['created_at'])) ?>
              <?php if (! empty($blog['generation_id'])): ?>
                · Generated #<?= (int) $blog['generation_id'] ?>
              <?php endif; ?>
            </p>
            <div class="sp-actions">
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
          <article class="sp-post-item">
            <h3>Media ID: <?= $h($row['media_id']) ?></h3>
            <p class="sp-muted">
              <?= $h(date('M j, Y g:ia', (int) $row['created_at'])) ?>
              <?php if (! empty($row['generation_id'])): ?>
                · <a href="<?= $h($instagram_history_url . '/' . (int) $row['generation_id']) ?>">Generated #<?= (int) $row['generation_id'] ?></a>
              <?php endif; ?>
            </p>
            <?php if (! empty($row['caption'])): ?>
              <p class="sp-history-excerpt"><?= $h(strlen($row['caption']) > 200 ? substr($row['caption'], 0, 197) . '...' : $row['caption']) ?></p>
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

  // Activate from URL hash (#blogs or #instagram)
  var initial = (location.hash || '').replace('#', '');
  if (initial === 'instagram' || initial === 'blogs') {
    activate(initial);
  }
}());
</script>
