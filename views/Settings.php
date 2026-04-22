<?php
ee()->load->helper('form');
$h = fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$imageModel = $settings['image_model'] ?? 'gpt-image-1.5';
$imageSize = $settings['image_size'] ?? '1024x1024';
$quality = $settings['image_quality'] ?? 'medium';
$mask = function ($value) {
    $value = trim((string) $value);
    if ($value === '') {
        return 'Not saved';
    }

    if (strlen($value) <= 12) {
        return substr($value, 0, 4) . '...';
    }

    return substr($value, 0, 8) . '...' . substr($value, -4);
};
?>
<div class="sp-wrap">
  <p class="sp-toolbar">
    <a class="button button--default" href="<?= $h($index_url) ?>">Generator</a>
    <a class="button button--default" href="<?= $h($history_url) ?>">History</a>
  </p>

  <?= form_open($save_url, ['class' => 'sp-card']) ?>
    <input type="hidden" name="save_settings" value="1">
    <h2>SocialPoster Settings</h2>

    <div class="sp-field">
      <label for="api_key">OpenAI API Key</label>
      <input type="password" id="api_key" name="api_key" value="" placeholder="<?= $api_key_saved ? 'API key saved. Leave blank to keep it.' : 'sk-...' ?>">
      <p class="sp-muted">Saved: <?= $h($mask($settings['api_key'] ?? '')) ?></p>
    </div>

    <div class="sp-field">
      <label for="admin_api_key">OpenAI Admin API Key</label>
      <input type="password" id="admin_api_key" name="admin_api_key" value="" placeholder="<?= $admin_api_key_saved ? 'Admin key saved. Leave blank to keep it.' : 'sk-admin-...' ?>">
      <p class="sp-muted">Saved: <?= $h($mask($settings['admin_api_key'] ?? '')) ?></p>
      <p class="sp-muted">Required for actual billed costs. Paste the admin key secret, not the key name or project ID.</p>
    </div>

    <div class="sp-field">
      <label for="openai_project_id">OpenAI Project ID</label>
      <input type="text" id="openai_project_id" name="openai_project_id" value="<?= $h($settings['openai_project_id'] ?? '') ?>" placeholder="Optional project filter for actual costs">
    </div>

    <div class="sp-field">
      <label for="text_model">Text Model</label>
      <input type="text" id="text_model" name="text_model" value="<?= $h($settings['text_model'] ?? 'gpt-5.4-mini') ?>">
    </div>

    <div class="sp-field">
      <label for="image_model">Image Model</label>
      <select id="image_model" name="image_model">
        <?php foreach (($image_models ?? []) as $model => $label): ?>
          <option value="<?= $h($model) ?>" <?= $imageModel === $model ? 'selected' : '' ?>><?= $h($label) ?> (<?= $h($model) ?>)</option>
        <?php endforeach; ?>
      </select>
    </div>

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

    <div class="sp-actions">
      <button type="submit" name="save_settings" value="1" class="button button--primary">Save Settings</button>
    </div>
  <?= form_close() ?>
</div>
