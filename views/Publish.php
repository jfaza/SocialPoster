<?php
ee()->load->helper('form');
$spView = require __DIR__ . '/_view_helpers.php';
$h = fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$selected = $rows[0] ?? null;
$publish_targets = $publish_targets ?? [];
$categoryLabelKey = function ($value) {
    $value = strtolower(strip_tags((string) $value));
    $value = preg_replace('/[^a-z0-9]+/', ' ', $value);
    return trim((string) preg_replace('/\s+/', ' ', $value));
};
// Suggested categories for a draft: its generated category, plus any blog
// category whose name appears as a word in the title or keywords.
$suggestCategories = function (array $row) use ($category_options, $categoryLabelKey): array {
    $haystack = ' ' . $categoryLabelKey(($row['title'] ?? '') . ' ' . implode(' ', (array) ($row['keywords'] ?? []))) . ' ';
    $generated = $categoryLabelKey($row['category'] ?? '');
    $ids = [];
    foreach ($category_options as $catId => $catLabel) {
        $labelParts = explode(':', (string) $catLabel);
        $name = $categoryLabelKey(end($labelParts));
        if ($name === '') {
            continue;
        }
        if ($name === $generated || strpos($haystack, ' ' . $name . ' ') !== false) {
            $ids[] = (int) $catId;
        }
    }
    return array_slice($ids, 0, 5);
};
$selectedCategoryIds = $selected ? $suggestCategories($selected) : [];
$templateMeta = is_array($template_meta ?? null) ? $template_meta : [];
?>
<div class="sp-wrap">

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
    <h2>Publish to Blog</h2>
    <p class="sp-muted">Choose a generated website draft, confirm the blog entry details, and publish it into ExpressionEngine.</p>
    <?php if (empty($rows)): ?>
      <p class="sp-muted">Create a post with a Website template first, then publish it here.</p>
    <?php else: ?>
      <?= form_open($publish_url) ?>
        <input type="hidden" name="publish_selected" value="1">

        <div class="sp-field">
          <label for="sp-generation">Generated Post</label>
          <select id="sp-generation" name="generation_id">
            <?php foreach ($rows as $row): ?>
              <?php $rowTemplateLabel = $spView->templatePlatformLabel($templateMeta, $row['template_id'] ?? 0); ?>
              <option
                value="<?= (int) $row['id'] ?>"
                data-title="<?= $h($row['title'] ?: 'Generated social post') ?>"
                data-categories="<?= $h(implode(',', $suggestCategories($row))) ?>"
              >
                #<?= (int) $row['id'] ?><?= $rowTemplateLabel !== '' ? ' · [' . $h($rowTemplateLabel) . ']' : '' ?> · <?= $h($row['title'] ?: 'Generated social post') ?> · <?= $h(date('M j, Y g:ia', (int) $row['created_at'])) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <?php foreach ($publish_targets as $key => $target): ?>
          <input name="publish_targets[]" type="hidden" value="<?= $h($key) ?>">
        <?php endforeach; ?>

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
            <label for="sp-image">Image</label>
            <label class="sp-checkbox-label"><input id="sp-image" name="include_image" type="checkbox" value="1" checked> Save generated image to blog image field</label>
          </div>
        </div>

        <div class="sp-field">
          <label>Categories</label>
          <p class="sp-muted">Suggested from the post's category, title and keywords. Tick as many as fit.</p>
          <div id="sp-categories" class="sp-category-grid">
            <?php foreach ($category_options as $value => $label): ?>
              <?php $labelParts = explode(':', (string) $label); ?>
              <label class="sp-checkbox-label"><input name="category_ids[]" type="checkbox" value="<?= (int) $value ?>" <?= in_array((int) $value, $selectedCategoryIds, true) ? 'checked' : '' ?>> <?= $h(trim((string) end($labelParts))) ?></label>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="sp-actions">
          <button type="submit" class="button button--primary">Publish Selected</button>
        </div>
      <?= form_close() ?>
    <?php endif; ?>
  </section>

</div>

<script>
(function () {
  const select = document.getElementById('sp-generation');
  const title = document.getElementById('sp-title');
  const categoryBoxes = document.querySelectorAll('#sp-categories input[type=checkbox]');

  if (!select || !title) {
    return;
  }

  select.addEventListener('change', function () {
    const option = select.options[select.selectedIndex];
    title.value = option.dataset.title || '';
    const suggested = (option.dataset.categories || '').split(',').filter(Boolean);
    categoryBoxes.forEach(function (box) {
      box.checked = suggested.indexOf(box.value) !== -1;
    });
  });
})();
</script>
