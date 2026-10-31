<?php
ee()->load->helper('form');
$spView = require __DIR__ . '/_view_helpers.php';
$h = fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<div class="sp-wrap sp-pubig">

  <header class="sp-pubig-head">
    <div class="sp-pubig-head-text">
      <h1>Publish to Instagram</h1>
      <p class="sp-muted">Review your generated Instagram posts and push them live. Posts without an image are disabled until you generate or attach one.</p>
    </div>
  </header>

  <?php if (empty($instagram_posts)): ?>

    <div class="sp-pubig-empty">
      <svg viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.5" width="48" height="48" aria-hidden="true">
        <rect x="8" y="8" width="32" height="32" rx="8"/>
        <circle cx="24" cy="24" r="7"/>
        <circle cx="33" cy="15" r="1.5" fill="currentColor"/>
      </svg>
      <h2>No Instagram posts yet</h2>
      <p class="sp-muted">Generate your first Instagram post to publish it here.</p>
      <a class="button button--primary" href="<?= $h($instagram_create_url) ?>">Create First Post</a>
    </div>

  <?php else: ?>

    <ul class="sp-pubig-list">
      <?php foreach ($instagram_posts as $post): ?>
        <?php
          $id        = (int) $post['id'];
          $hasImage  = ! empty($post['image_path']) || ! empty($post['image_url']);
          $thumb     = (string) ($post['image_url'] ?? '');
          $title     = trim((string) ($post['title'] ?? ''));
          $caption   = trim((string) ($post['post_text'] ?? $post['caption'] ?? ''));
          $excerpt   = $spView->excerpt($caption, 160, '…');
          $source    = trim((string) ($post['source'] ?? ''));
          $editUrl   = $instagram_history_url . '/' . $id;
        ?>
        <li class="sp-pubig-item">

          <div class="sp-pubig-thumb">
            <?php if ($hasImage && $thumb !== ''): ?>
              <img src="<?= $h($thumb) ?>" alt="" loading="lazy">
            <?php else: ?>
              <div class="sp-pubig-thumb-empty" aria-label="No image">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="22" height="22"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="1.6"/><path d="M21 15l-5-5L5 21"/></svg>
              </div>
            <?php endif; ?>
          </div>

          <div class="sp-pubig-body">
            <div class="sp-pubig-row1">
              <h3 class="sp-pubig-title"><?= $h($title !== '' ? $title : 'Untitled post') ?></h3>
              <?php if ($hasImage): ?>
                <span class="sp-pill sp-pill-ready">Ready</span>
              <?php else: ?>
                <span class="sp-pill sp-pill-warn">No image</span>
              <?php endif; ?>
            </div>

            <p class="sp-pubig-meta">
              <span>#<?= $id ?></span>
              <?php if ($source !== ''): ?>
                <span class="sp-dot"></span>
                <span class="sp-pubig-source sp-source-<?= $h(strtolower($source)) ?>"><?= $h(ucfirst($source)) ?></span>
              <?php endif; ?>
              <span class="sp-dot"></span>
              <time><?= $h(date('M j, Y · g:ia', (int) $post['created_at'])) ?></time>
            </p>

            <?php if ($excerpt !== ''): ?>
              <p class="sp-pubig-excerpt"><?= $h($excerpt) ?></p>
            <?php endif; ?>

            <?php if (! $hasImage): ?>
              <p class="sp-pubig-warn-line">
                Post needs an image before publishing.
                <a href="<?= $h($editUrl) ?>">Open editor →</a>
              </p>
            <?php endif; ?>
          </div>

          <div class="sp-pubig-actions">
            <a class="button button--default" href="<?= $h($editUrl) ?>">View</a>
            <?= form_open($publish_url, ['class' => 'sp-pubig-form']) ?>
              <input type="hidden" name="publish_to_instagram" value="1">
              <input type="hidden" name="generation_id" value="<?= $id ?>">
              <button type="submit" class="button button--primary" <?= $hasImage ? '' : 'disabled' ?>>
                Publish
              </button>
            <?= form_close() ?>
            <?= form_open($publish_url, ['class' => 'sp-pubig-form']) ?>
              <input type="hidden" name="delete_instagram_post" value="1">
              <input type="hidden" name="generation_id" value="<?= $id ?>">
              <button type="submit" class="button button--danger sp-pubig-delete"
                      onclick="return confirm('Delete this Instagram post and its generated image? This cannot be undone.');"
                      title="Delete this post">
                <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" width="14" height="14" aria-hidden="true">
                  <path d="M3 6h14M8 6V4a1 1 0 011-1h2a1 1 0 011 1v2M5 6l1 11a2 2 0 002 2h4a2 2 0 002-2l1-11M9 10v6M11 10v6"/>
                </svg>
                Delete
              </button>
            <?= form_close() ?>
          </div>

        </li>
      <?php endforeach; ?>
    </ul>

  <?php endif; ?>

</div>
