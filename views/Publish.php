<?php
ee()->load->helper('form');
$h = fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$selected = $rows[0] ?? null;
$categoryLabelKey = function ($value) {
    $value = strtolower(strip_tags((string) $value));
    $value = preg_replace('/[^a-z0-9]+/', ' ', $value);
    return trim((string) preg_replace('/\s+/', ' ', $value));
};
$selectedCategoryId = 0;
if (! empty($selected['category']) && ! empty($category_options)) {
    $selectedKey = $categoryLabelKey($selected['category']);
    foreach ($category_options as $catId => $catLabel) {
        $labelParts = explode(':', (string) $catLabel);
        $catName = trim((string) end($labelParts));
        if ($categoryLabelKey($catName) === $selectedKey || $categoryLabelKey($catLabel) === $selectedKey) {
            $selectedCategoryId = (int) $catId;
            break;
        }
    }
}
?>
<div class="sp-wrap">
  <div class="sp-toolbar">
    <a class="button button--default" href="<?= $h($index_url) ?>">Generator</a>
    <a class="button button--default" href="<?= $h($history_url) ?>">History</a>
  </div>

  <?php if (! empty($result)): ?>
    <section class="sp-card sp-result-card">
      <h2>Blog Entry Created</h2>
      <p class="sp-muted">Entry #<?= (int) $result['entry_id'] ?> · <?= $h($result['status']) ?></p>
      <div class="sp-actions">
        <a class="button button--primary" href="<?= $h($result['edit_url']) ?>">Edit Entry</a>
        <a class="button button--default" href="<?= $h($result['url']) ?>" target="_blank" rel="noopener">View Blog Post</a>
      </div>
    </section>
  <?php endif; ?>

  <section class="sp-card">
    <h2>Publish Targets</h2>
    <div class="sp-target-grid">
      <?php foreach ($targets as $key => $target): ?>
        <div class="sp-target <?= $target['status'] === 'ready' ? 'is-ready' : 'is-planned' ?>">
          <strong><?= $h($target['label']) ?></strong>
          <span><?= $target['status'] === 'ready' ? 'Ready' : 'Planned' ?></span>
          <p><?= $h($target['description']) ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="sp-card">
    <h2>Publish to Blog</h2>
    <?php if (empty($rows)): ?>
      <p class="sp-muted">Generate a post first, then publish it to the blog.</p>
    <?php else: ?>
      <?= form_open($publish_url) ?>
        <input type="hidden" name="publish_blog" value="1">

        <div class="sp-field">
          <label for="sp-generation">Generated Post</label>
          <select id="sp-generation" name="generation_id">
            <?php foreach ($rows as $row): ?>
              <option
                value="<?= (int) $row['id'] ?>"
                data-title="<?= $h($row['title'] ?: 'Generated social post') ?>"
                data-category="<?= $h($row['category'] ?? '') ?>"
              >
                #<?= (int) $row['id'] ?> · <?= $h($row['title'] ?: 'Generated social post') ?> · <?= $h(date('M j, Y g:ia', (int) $row['created_at'])) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="sp-field">
          <label for="sp-title">Blog Title</label>
          <input id="sp-title" name="title" type="text" value="<?= $h($selected['title'] ?? '') ?>" required>
        </div>

        <div class="sp-mini-grid">
          <div class="sp-field">
            <label for="sp-status">Entry Status</label>
            <select id="sp-status" name="status">
              <?php foreach ($status_options as $value => $label): ?>
                <option value="<?= $h($value) ?>"><?= $h($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="sp-field">
            <label for="sp-category">Category</label>
            <select id="sp-category" name="category_id">
              <option value="0">Use generated category if matched</option>
              <?php foreach ($category_options as $value => $label): ?>
                <option value="<?= (int) $value ?>" <?= (int) $value === $selectedCategoryId ? 'selected' : '' ?>><?= $h($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="sp-field">
            <label for="sp-image">Image</label>
            <label class="sp-checkbox-label"><input id="sp-image" name="include_image" type="checkbox" value="1" checked> Save generated image to blog image field</label>
          </div>
        </div>

        <div class="sp-actions">
          <button type="submit" class="button button--primary">Publish to Blog</button>
        </div>
      <?= form_close() ?>
    <?php endif; ?>
  </section>

  <section class="sp-card">
    <h2>Published Blog Entries</h2>
    <?php if (empty($published_blogs)): ?>
      <p class="sp-muted">No generated blog entries have been published yet.</p>
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
            <?= form_open($publish_url) ?>
              <input type="hidden" name="delete_blog" value="1">
              <input type="hidden" name="published_blog_id" value="<?= (int) $blog['id'] ?>">
              <button type="submit" class="button button--danger">Delete Blog Entry</button>
            <?= form_close() ?>
          </div>
        </article>
      <?php endforeach; ?>
    <?php endif; ?>
  </section>
</div>

<script>
(function () {
  const select = document.getElementById('sp-generation');
  const title = document.getElementById('sp-title');
  const category = document.getElementById('sp-category');

  if (!select || !title) {
    return;
  }

  select.addEventListener('change', function () {
    const option = select.options[select.selectedIndex];
    title.value = option.dataset.title || '';
    if (category) {
      category.value = '0';
      const categoryKey = (option.dataset.category || '').toLowerCase().replace(/[^a-z0-9]+/g, ' ').trim();
      if (categoryKey) {
        Array.prototype.some.call(category.options, function (categoryOption) {
          const label = categoryOption.textContent.split(':').pop().toLowerCase().replace(/[^a-z0-9]+/g, ' ').trim();
          if (label === categoryKey) {
            category.value = categoryOption.value;
            return true;
          }
          return false;
        });
      }
    }
  });
})();
</script>
