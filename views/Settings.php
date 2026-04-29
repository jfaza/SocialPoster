<?php
ee()->load->helper('form');
$h = fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$textModel    = $settings['text_model']    ?? 'gpt-5.4';
$imageModel   = $settings['image_model']   ?? 'gpt-image-1.5';
$imageSize    = $settings['image_size']    ?? '1024x1024';
$quality      = $settings['image_quality'] ?? 'medium';
$carouselSlides = (int) ($settings['carousel_slides'] ?? 6);

$mask = function ($value) {
    $value = trim((string) $value);
    if ($value === '') { return 'Not saved'; }
    if (strlen($value) <= 12) { return substr($value, 0, 4) . '...'; }
    return substr($value, 0, 8) . '...' . substr($value, -4);
};

// $text_models is now ['openai' => [...], 'anthropic' => [...]]
$textModelGroups = $text_models ?? [];
$allTextModels   = array_merge(...array_values($textModelGroups));
$currentModelValid = array_key_exists($textModel, $allTextModels);
?>
<div class="sp-wrap">
  <p class="sp-toolbar">
    <a class="button button--default" href="<?= $h($index_url) ?>">Generator</a>
    <a class="button button--default" href="<?= $h($history_url) ?>">History</a>
  </p>

  <?= form_open($save_url, ['class' => 'sp-settings-form']) ?>
    <input type="hidden" name="save_settings" value="1">

    <!-- ── OpenAI ─────────────────────────────────────────── -->
    <div class="sp-card">
      <h2>OpenAI</h2>

      <div class="sp-field">
        <label for="api_key">API Key</label>
        <input type="password" id="api_key" name="api_key" value=""
               placeholder="<?= $api_key_saved ? 'Saved — leave blank to keep' : 'sk-...' ?>">
        <p class="sp-muted">Saved: <?= $h($mask($settings['api_key'] ?? '')) ?></p>
      </div>

      <div class="sp-field">
        <label for="admin_api_key">Admin API Key</label>
        <input type="password" id="admin_api_key" name="admin_api_key" value=""
               placeholder="<?= $admin_api_key_saved ? 'Saved — leave blank to keep' : 'sk-admin-...' ?>">
        <p class="sp-muted">Saved: <?= $h($mask($settings['admin_api_key'] ?? '')) ?> &nbsp;·&nbsp; Required for billed cost tracking.</p>
      </div>

      <div class="sp-field">
        <label for="openai_project_id">Project ID</label>
        <input type="text" id="openai_project_id" name="openai_project_id"
               value="<?= $h($settings['openai_project_id'] ?? '') ?>"
               placeholder="proj_… (optional, for cost filtering)">
      </div>
    </div>

    <!-- ── Anthropic ──────────────────────────────────────── -->
    <div class="sp-card">
      <h2>Anthropic</h2>

      <div class="sp-field">
        <label for="anthropic_api_key">API Key</label>
        <input type="password" id="anthropic_api_key" name="anthropic_api_key" value=""
               placeholder="<?= $anthropic_api_key_saved ? 'Saved — leave blank to keep' : 'sk-ant-...' ?>">
        <p class="sp-muted">Saved: <?= $h($mask($settings['anthropic_api_key'] ?? '')) ?> &nbsp;·&nbsp; Required when a Claude model is selected.</p>
      </div>
    </div>

    <!-- ── Models ─────────────────────────────────────────── -->
    <div class="sp-card">
      <h2>Models</h2>

      <div class="sp-field">
        <label for="text_model">Text Model</label>
        <select id="text_model" name="text_model">
          <?php foreach ($textModelGroups as $provider => $models): ?>
            <optgroup label="<?= $h(ucfirst($provider)) ?>">
              <?php foreach ($models as $model => $label): ?>
                <option value="<?= $h($model) ?>" <?= $textModel === $model ? 'selected' : '' ?>>
                  <?= $h($label) ?>
                </option>
              <?php endforeach; ?>
            </optgroup>
          <?php endforeach; ?>
          <?php if (! $currentModelValid && $textModel !== ''): ?>
            <option value="<?= $h($textModel) ?>" selected><?= $h($textModel) ?> (current)</option>
          <?php endif; ?>
        </select>
        <p class="sp-muted">OpenAI models use the OpenAI API key. Claude models use the Anthropic API key.</p>
      </div>

      <div class="sp-field">
        <label for="image_model">Image Model</label>
        <select id="image_model" name="image_model">
          <?php foreach (($image_models ?? []) as $model => $label): ?>
            <option value="<?= $h($model) ?>" <?= $imageModel === $model ? 'selected' : '' ?>>
              <?= $h($label) ?> (<?= $h($model) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="sp-mini-grid">
        <div class="sp-field">
          <label for="image_size">Image Size</label>
          <select id="image_size" name="image_size">
            <?php foreach (['1024x1024', '1024x1536', '1536x1024'] as $size): ?>
              <option value="<?= $h($size) ?>" <?= $imageSize === $size ? 'selected' : '' ?>><?= $h($size) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="sp-field">
          <label for="image_quality">Image Quality</label>
          <select id="image_quality" name="image_quality">
            <?php foreach (['low', 'medium', 'high'] as $level): ?>
              <option value="<?= $h($level) ?>" <?= $quality === $level ? 'selected' : '' ?>><?= ucfirst($level) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </div>

    <!-- ── Carousel ───────────────────────────────────────── -->
    <div class="sp-card">
      <h2>Carousel</h2>

      <div class="sp-field" style="max-width:200px;">
        <label for="carousel_slides">Number of Slides</label>
        <input type="number" id="carousel_slides" name="carousel_slides"
               value="<?= $carouselSlides ?>" min="3" max="12" step="1">
        <p class="sp-muted">3–12 slides per carousel generation.</p>
      </div>
    </div>

    <div class="sp-actions">
      <button type="submit" class="button button--primary">Save Settings</button>
    </div>

  <?= form_close() ?>
</div>

