<?php
ee()->load->helper('form');
$h = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$selfUrl = $save_url;
?>
<div class="sp-wrap">

  <p class="sp-toolbar">
    <a class="button button--default" href="<?= $h($create_url) ?>">← Back to Create</a>
  </p>

  <!-- Add source -->
  <div class="sp-card">
    <h2>Add News Source</h2>
    <p class="sp-muted">Paste any RSS or Atom feed URL. Most news sites publish a feed at <code>/feed/</code> or <code>/rss/</code>.</p>

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

</div>

