<?php
ee()->load->helper('form');
$h = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$selfUrl = $save_url;
$backUrl = (string) ($back_url ?? $create_url ?? '');
$backLabel = (string) ($back_label ?? 'Back');
$pageIntro = trim((string) ($page_intro ?? ''));
$actionUrl = (string) ($action_url ?? '');
$csrfToken = (string) ($csrf_token ?? '');
$hotArticles = is_array($hot_articles ?? null) ? $hot_articles : [];
$hotArticlesMeta = is_array($hot_articles_meta ?? null) ? $hot_articles_meta : [];
?>
<div class="sp-wrap">

  <p class="sp-toolbar">
    <?php if ($backUrl !== ''): ?>
      <a class="button button--default" href="<?= $h($backUrl) ?>">← <?= $h($backLabel) ?></a>
    <?php endif; ?>
  </p>

  <!-- Add source -->
  <div class="sp-card">
    <h2>Add News Source</h2>
    <p class="sp-muted">
      <?= $pageIntro !== '' ? $h($pageIntro) . ' ' : '' ?>Paste any RSS or Atom feed URL. Most news sites publish a feed at <code>/feed/</code> or <code>/rss/</code>.
    </p>

    <?= form_open($save_url) ?>
      <input type="hidden" name="add_source" value="1">
      <div class="sp-mini-grid sp-ns-add-grid">
        <div class="sp-field">
          <label for="source_name">Name</label>
          <input type="text" id="source_name" name="source_name"
                 placeholder="e.g. TechCrunch" required>
        </div>
        <div class="sp-field">
          <label for="category">Category</label>
          <select id="category" name="category">
            <option value="">— Select —</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= $h($cat) ?>"><?= $h($cat) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="sp-field">
          <label for="feed_url">RSS / Atom Feed URL</label>
          <input type="url" id="feed_url" name="feed_url"
                 placeholder="https://techcrunch.com/feed/" required>
        </div>
      </div>
      <div class="sp-actions">
        <button type="submit" class="button button--primary">Add Source</button>
      </div>
    <?= form_close() ?>
  </div>

  <!-- Sources list -->
  <div class="sp-card">
    <div class="sp-ns-list-header">
      <h2>Saved Sources</h2>
      <div class="sp-ns-filter">
        <label for="ns-filter-cat">Filter:</label>
        <select id="ns-filter-cat" onchange="window.location = '<?= $h($save_url) ?>?category=' + encodeURIComponent(this.value)">
          <option value="">All categories</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= $h($cat) ?>"<?= $filter_category === $cat ? ' selected' : '' ?>><?= $h($cat) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <?php if (empty($sources)): ?>
      <p class="sp-muted">No sources<?= $filter_category !== '' ? ' in ' . $h($filter_category) : '' ?>. <?= $filter_category !== '' ? '<a href="' . $h($save_url) . '">Clear filter</a>' : 'Add a feed URL above to get started.' ?></p>
    <?php else: ?>
      <table class="sp-ns-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Category</th>
            <th>Feed URL</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($sources as $source): ?>
            <tr>
              <td><?= $h($source['name']) ?></td>
              <td>
                <?php if ($source['category'] !== ''): ?>
                  <span class="sp-ns-badge"><?= $h($source['category']) ?></span>
                <?php else: ?>
                  <span class="sp-muted">—</span>
                <?php endif; ?>
              </td>
              <td class="sp-muted sp-ns-url"><?= $h($source['feed_url']) ?></td>
              <td class="sp-ns-action">
                <?= form_open($save_url) ?>
                  <input type="hidden" name="delete_source" value="1">
                  <input type="hidden" name="source_id" value="<?= (int) $source['id'] ?>">
                  <button type="submit" class="button button--danger button--small"
                          onclick="return confirm('Remove this source?')">Remove</button>
                <?= form_close() ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <div class="sp-card">
    <div class="sp-hot-toolbar">
      <div>
        <h2>Hot Articles</h2>
        <p id="sp-hot-meta" class="sp-muted">Shared discovery cache for timely topic research across SocialPoster.</p>
      </div>
      <button type="button" id="sp-hot-refresh-btn" class="button button--default">Refresh Hot Articles</button>
    </div>
    <div id="sp-hot-status" class="sp-muted"></div>
    <div id="sp-hot-article-grid" class="sp-article-grid sp-hot-article-grid"></div>
  </div>

</div>

<script>
(function () {
  var actionUrl = <?= json_encode($actionUrl) ?>;
  var csrfToken = <?= json_encode($csrfToken) ?>;
  var hotGrid = document.getElementById('sp-hot-article-grid');
  var hotRefreshBtn = document.getElementById('sp-hot-refresh-btn');
  var hotStatus = document.getElementById('sp-hot-status');
  var hotMeta = document.getElementById('sp-hot-meta');
  var initialHotArticles = <?= json_encode($hotArticles, JSON_UNESCAPED_UNICODE) ?>;
  var initialHotMeta = <?= json_encode($hotArticlesMeta, JSON_UNESCAPED_UNICODE) ?>;

  function esc(str) {
    return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  function updateHotMeta(meta, count) {
    if (! hotMeta) return;
    meta = meta || {};
    if (! meta.exists) {
      hotMeta.textContent = 'Cache table is not available yet. Run the add-on update, then refresh.';
      return;
    }
    if (! count) {
      hotMeta.textContent = 'No cached articles yet.';
      return;
    }
    var age = meta.age_seconds;
    var ageText = typeof age === 'number'
      ? (age < 90 ? 'just now' : Math.round(age / 60) + ' min ago')
      : 'unknown age';
    hotMeta.textContent = count + ' cached articles · refreshed ' + ageText + (meta.is_stale ? ' · stale' : '');
  }

  function renderHotArticles(articles, meta) {
    if (! hotGrid) return;
    hotGrid.innerHTML = '';

    if (!articles || articles.length === 0) {
      hotGrid.innerHTML = '<p class="sp-muted">No cached hot articles yet. Refresh to build the cache.</p>';
      updateHotMeta(meta, 0);
      return;
    }

    articles.forEach(function (a) {
      var card = document.createElement('div');
      card.className = 'sp-article-card';

      var thumb = a.image_url
        ? '<img src="' + esc(a.image_url) + '" alt="" loading="lazy" class="sp-article-thumb">'
        : '<div class="sp-article-no-thumb"></div>';

      var date = '';
      if (a.published_at) {
        try { date = new Date(a.published_at).toLocaleDateString(); } catch (e) {}
      }

      card.innerHTML =
        '<div class="sp-article-card-inner">' +
          thumb +
          '<div class="sp-article-meta">' +
            '<div class="sp-article-title">' + esc(a.title) + '</div>' +
            (a.excerpt ? '<div class="sp-article-excerpt">' + esc(a.excerpt.substring(0, 160)) + (a.excerpt.length > 160 ? '…' : '') + '</div>' : '') +
            '<div class="sp-article-footer">' +
              (a.source_name ? '<span class="sp-hot-source">' + esc(a.source_name) + '</span>' : '') +
              (date ? '<span class="sp-article-date sp-muted">' + esc(date) + '</span>' : '') +
              (a.hot_score ? '<span class="sp-hot-score">' + esc(Math.round(a.hot_score)) + '</span>' : '') +
              (!a.image_url ? '<span class="sp-no-image-tag">No image</span>' : '') +
            '</div>' +
            (a.score_reasons && a.score_reasons.length ? '<div class="sp-hot-reasons">' + a.score_reasons.slice(0, 3).map(esc).join(' · ') + '</div>' : '') +
            (a.url ? '<div class="sp-hot-links"><a href="' + esc(a.url) + '" target="_blank" rel="noopener">Open article →</a></div>' : '') +
          '</div>' +
        '</div>';

      hotGrid.appendChild(card);
    });

    updateHotMeta(meta, articles.length);
  }

  if (hotRefreshBtn && actionUrl && csrfToken) {
    hotRefreshBtn.addEventListener('click', function () {
      hotRefreshBtn.disabled = true;
      if (hotStatus) hotStatus.textContent = 'Refreshing feeds and rebuilding hot article cache...';

      fetch(actionUrl, {
        method: 'POST',
        body: new URLSearchParams({
          csrf_token: csrfToken,
          XID: csrfToken,
          hot_news: '1',
          refresh: '1'
        }),
      })
        .then(function (r) { return r.json(); })
        .then(function (json) {
          hotRefreshBtn.disabled = false;
          if (! json.success) {
            if (hotStatus) hotStatus.textContent = 'Error: ' + (json.message || 'unknown');
            return;
          }
          if (hotStatus) {
            hotStatus.textContent = json.errors && json.errors.length
              ? 'Refreshed with ' + json.errors.length + ' source warning(s).'
              : 'Hot articles refreshed.';
          }
          renderHotArticles(json.articles || [], json.meta || {});
        })
        .catch(function (err) {
          hotRefreshBtn.disabled = false;
          if (hotStatus) hotStatus.textContent = 'Failed: ' + err.message;
        });
    });
  }

  renderHotArticles(initialHotArticles || [], initialHotMeta || {});
}());
</script>
