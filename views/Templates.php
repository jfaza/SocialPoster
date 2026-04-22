<?php
ee()->load->helper('form');
$h = fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$template = $editing ?: [
    'title' => '',
    'content_type' => 'social_post',
    'platform' => 'Website',
    'length_preset' => 'medium',
    'word_count' => 600,
    'tone' => '',
    'audience' => '',
    'goal' => '',
    'research_mode' => '',
    'citation_count' => 1,
    'internal_link_count' => 1,
    'external_link_count' => 1,
    'schema_type' => 'Article',
    'image_style' => '',
    'cta_style' => '',
    'prompt_instructions' => '',
    'is_default' => 0,
];
$select = function (string $name, $current) use ($field_options, $h) {
    foreach (($field_options[$name] ?? []) as $value => $label) {
        echo '<option value="' . $h($value) . '"' . ((string) $current === (string) $value ? ' selected' : '') . '>' . $h($label) . '</option>';
    }
};
$editorActive = ! empty($editor_active);
?>
<div class="sp-wrap">
  <div class="sp-toolbar">
    <a class="button button--default" href="<?= $h($index_url) ?>">Generator</a>
    <a class="button button--default" href="<?= $h($calendar_url) ?>">Calendar</a>
    <a class="button button--primary" href="<?= $h($new_template_url) ?>">New Template</a>
  </div>

  <div class="tab-wrap sp-native-tabs">
    <div class="tab-bar">
      <div class="tab-bar__tabs">
        <button type="button" class="tab-bar__tab js-tab-button <?= $editorActive ? 'active' : '' ?>" rel="t-0">Template Editor</button>
        <button type="button" class="tab-bar__tab js-tab-button <?= ! $editorActive ? 'active' : '' ?>" rel="t-1">Template Library</button>
      </div>
    </div>

    <section class="sp-card tab t-0 <?= $editorActive ? 'tab-open' : '' ?>">
      <h2><?= $editing ? 'Edit Template' : 'New Template' ?></h2>
      <p class="sp-muted">Reusable generation defaults for length, tone, audience, links, citations, schema, and image style.</p>
      <?= form_open($save_url) ?>
        <input type="hidden" name="save_template" value="1">

        <div class="sp-field">
          <label for="sp-title">Template Name</label>
          <input id="sp-title" name="title" type="text" required value="<?= $h($template['title']) ?>" placeholder="SEO Article - Long">
        </div>

        <div class="sp-mini-grid">
          <div class="sp-field">
            <label for="sp-type">Content Type</label>
            <select id="sp-type" name="content_type">
              <?php $select('content_type', $template['content_type']); ?>
            </select>
          </div>
          <div class="sp-field">
            <label for="sp-platform">Platform</label>
            <select id="sp-platform" name="platform">
              <?php $select('platform', $template['platform']); ?>
            </select>
          </div>
          <div class="sp-field">
            <label for="sp-length">Length</label>
            <select id="sp-length" name="length_preset">
              <?php $select('length_preset', $template['length_preset']); ?>
            </select>
          </div>
        </div>

        <div class="sp-mini-grid">
          <div class="sp-field">
            <label for="sp-words">Word Count</label>
            <select id="sp-words" name="word_count">
              <?php $select('word_count', (int) $template['word_count']); ?>
            </select>
          </div>
          <div class="sp-field">
            <label for="sp-citations">Citations</label>
            <select id="sp-citations" name="citation_count">
              <?php $select('citation_count', (int) $template['citation_count']); ?>
            </select>
          </div>
          <div class="sp-field">
            <label for="sp-schema">Schema</label>
            <select id="sp-schema" name="schema_type">
              <?php $select('schema_type', $template['schema_type']); ?>
            </select>
          </div>
        </div>

        <div class="sp-mini-grid">
          <div class="sp-field">
            <label for="sp-internal">Internal Links</label>
            <select id="sp-internal" name="internal_link_count">
              <?php $select('internal_link_count', (int) $template['internal_link_count']); ?>
            </select>
          </div>
          <div class="sp-field">
            <label for="sp-external">External Links</label>
            <select id="sp-external" name="external_link_count">
              <?php $select('external_link_count', (int) $template['external_link_count']); ?>
            </select>
          </div>
          <div class="sp-field">
            <label for="sp-default">Default</label>
            <label class="sp-checkbox-label"><input id="sp-default" name="is_default" type="checkbox" value="1" <?= ! empty($template['is_default']) ? 'checked' : '' ?>> Use as default</label>
          </div>
        </div>

        <div class="sp-field">
          <label for="sp-tone">Tone</label>
          <select id="sp-tone" name="tone">
            <?php $select('tone', $template['tone']); ?>
          </select>
        </div>
        <div class="sp-field">
          <label for="sp-audience">Audience</label>
          <select id="sp-audience" name="audience">
            <?php $select('audience', $template['audience']); ?>
          </select>
        </div>
        <div class="sp-field">
          <label for="sp-goal">Goal</label>
          <select id="sp-goal" name="goal">
            <?php $select('goal', $template['goal']); ?>
          </select>
        </div>
        <div class="sp-field">
          <label for="sp-research">Research Mode</label>
          <select id="sp-research" name="research_mode">
            <?php $select('research_mode', $template['research_mode']); ?>
          </select>
        </div>
        <div class="sp-field">
          <label for="sp-image-style">Image Style</label>
          <select id="sp-image-style" name="image_style">
            <?php $select('image_style', $template['image_style']); ?>
          </select>
        </div>
        <div class="sp-field">
          <label for="sp-cta">CTA Style</label>
          <select id="sp-cta" name="cta_style">
            <?php $select('cta_style', $template['cta_style']); ?>
          </select>
        </div>
        <div class="sp-field">
          <label for="sp-instructions">Prompt Instructions</label>
          <textarea id="sp-instructions" name="prompt_instructions" rows="6"><?= $h($template['prompt_instructions']) ?></textarea>
        </div>

        <div class="sp-actions">
          <button type="submit" class="button button--primary">Save Template</button>
          <?php if ($editing): ?>
            <a class="button button--default" href="<?= $h($templates_url) ?>">Cancel</a>
          <?php endif; ?>
        </div>
      <?= form_close() ?>
    </section>

    <section class="sp-card tab t-1 <?= ! $editorActive ? 'tab-open' : '' ?>">
      <h2>Templates</h2>
      <?php foreach ($rows as $row): ?>
        <article class="sp-template">
          <h3><?= $h($row['title']) ?><?= ! empty($row['is_default']) ? ' <span class="sp-muted">(default)</span>' : '' ?></h3>
          <p class="sp-muted"><?= $h($row['platform']) ?> · <?= $h($row['length_preset']) ?> · <?= (int) $row['word_count'] ?> words · <?= (int) $row['citation_count'] ?> citations</p>
          <p><?= $h($row['goal']) ?></p>
          <div class="sp-actions">
            <a class="button button--primary" href="<?= $h($templates_url . '/' . (int) $row['id']) ?>#tab=t-0">Edit</a>
            <?= form_open($templates_url) ?>
              <input type="hidden" name="delete_template" value="<?= (int) $row['id'] ?>">
              <button type="submit" class="button button--danger">Delete</button>
            <?= form_close() ?>
          </div>
        </article>
      <?php endforeach; ?>
    </section>
  </div>
</div>

<script>
(function () {
  <?php if (! $editing): ?>
  const title = document.getElementById('sp-title');
  const type = document.getElementById('sp-type');
  const platform = document.getElementById('sp-platform');
  const length = document.getElementById('sp-length');

  function syncTitle() {
    if (title.dataset.touched === '1') {
      return;
    }

    title.value = type.options[type.selectedIndex].text + ' - ' + platform.value + ' - ' + length.options[length.selectedIndex].text;
  }

  title.addEventListener('input', function () {
    title.dataset.touched = title.value.trim() === '' ? '0' : '1';
  });

  [type, platform, length].forEach(function (field) {
    field.addEventListener('change', syncTitle);
  });

  syncTitle();
  <?php endif; ?>
})();
</script>
