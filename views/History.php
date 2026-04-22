<?php
ee()->load->helper('form');
$h = fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<div class="sp-wrap">
  <p class="sp-toolbar">
    <a class="button button--default" href="<?= $h($index_url) ?>">Generator</a>
    <a class="button button--default" href="<?= $h($settings_url) ?>">Settings</a>
    <a class="button button--default" href="<?= $h($history_url) ?>">All Generated Posts</a>
  </p>

  <?php if (($mode ?? 'list') === 'edit' && ! empty($post)): ?>
    <section class="sp-card">
      <h2>Edit Generated Post</h2>
      <p class="sp-muted">Generated <?= $h(date('M j, Y g:ia', (int) $post['created_at'])) ?><?= ! empty($post['source']) ? ' · ' . $h($post['source']) : '' ?></p>

      <?php if (! empty($seo_score)): ?>
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
      <?php endif; ?>

      <?php if (! empty($post['image_url'])): ?>
        <p><img src="<?= $h($post['image_url']) ?>" alt="" class="sp-preview"></p>
      <?php endif; ?>

      <?= form_open($save_url) ?>
        <input type="hidden" name="regenerate_image" value="1">

        <div class="sp-field">
          <label for="sp-image-prompt">Image Prompt</label>
          <textarea id="sp-image-prompt" name="image_prompt" rows="5"><?= $h($post['image_prompt'] ?? '') ?></textarea>
        </div>

        <div class="sp-field">
          <label for="sp-image-brief">Image Brief</label>
          <textarea id="sp-image-brief" name="image_brief" rows="5"><?= $h($post['image_brief'] ?? '') ?></textarea>
        </div>

        <div class="sp-actions">
          <button type="submit" class="button button--default">Regenerate Image</button>
        </div>
      <?= form_close() ?>

      <?= form_open($save_url) ?>
        <input type="hidden" name="save_post" value="1">

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

        <div class="sp-field">
          <label for="sp-post-text">Post Text</label>
          <textarea id="sp-post-text" name="post_text" rows="8"><?= $h($post['post_text'] ?? '') ?></textarea>
        </div>

        <div class="sp-field">
          <label for="sp-intro">Intro Text</label>
          <textarea id="sp-intro" name="intro_text" rows="4"><?= $h($post['intro_text'] ?? '') ?></textarea>
        </div>

        <div class="sp-field">
          <label for="sp-toc">Table of Contents</label>
          <textarea id="sp-toc" name="table_of_contents" rows="5"><?= $h(implode("\n", $post['table_of_contents'] ?? [])) ?></textarea>
        </div>

        <div class="sp-field">
          <label for="sp-keywords">SEO Keywords</label>
          <input id="sp-keywords" name="keywords" type="text" value="<?= $h(implode(', ', $post['keywords'] ?? [])) ?>">
        </div>

        <div class="sp-field">
          <label for="sp-category">Blog Category</label>
          <input id="sp-category" name="category" type="text" value="<?= $h($post['category'] ?? '') ?>">
        </div>

        <div class="sp-field">
          <label for="sp-hashtags">Hashtags</label>
          <input id="sp-hashtags" name="hashtags" type="text" value="<?= $h(implode(', ', $post['hashtags'] ?? [])) ?>">
        </div>

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

        <div class="sp-actions">
          <button type="submit" class="button button--primary">Save Changes</button>
          <a class="button button--default" href="<?= $h($history_url) ?>">Back to History</a>
        </div>
      <?= form_close() ?>
    </section>
  <?php else: ?>
  <section class="sp-card">
    <h2>Generated Posts</h2>
    <?php if (empty($rows)): ?>
      <p class="sp-muted">No SocialPoster generations yet.</p>
    <?php else: ?>
      <div class="sp-history-list">
      <?php foreach ($rows as $row): ?>
        <?php
          $intro = trim((string) ($row['intro_text'] ?? ''));
          $keywords = array_slice((array) ($row['keywords'] ?? []), 0, 4);
          $templateLabel = ! empty($row['template_id']) && ! empty($template_options[(int) $row['template_id']])
              ? $template_options[(int) $row['template_id']]
              : '';
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
              <h3><?= $h($row['title'] ?: 'Generated social post') ?></h3>
              <?php if (! empty($row['seo_score'])): ?>
                <div class="sp-seo-inline <?= (int) $row['seo_score']['score'] >= 75 ? 'is-strong' : ((int) $row['seo_score']['score'] >= 60 ? 'is-warning' : 'is-weak') ?>">
                  <strong>SEO <?= (int) $row['seo_score']['score'] ?>/100</strong>
                  <span><?= $h($row['seo_score']['grade']) ?></span>
                </div>
              <?php endif; ?>
            </div>
            <p class="sp-muted">
              #<?= (int) $row['id'] ?> · <?= $h(date('M j, Y g:ia', (int) $row['created_at'])) ?><?= ! empty($row['source']) ? ' · ' . $h($row['source']) : '' ?><?= $templateLabel !== '' ? ' · ' . $h($templateLabel) : '' ?>
            </p>
            <?php if ($intro !== ''): ?>
              <p class="sp-history-excerpt"><?= $h(strlen($intro) > 220 ? substr($intro, 0, 217) . '...' : $intro) ?></p>
            <?php endif; ?>
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
            <a class="button button--primary" href="<?= $h($history_url . '/' . (int) $row['id']) ?>">View / Edit</a>
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
