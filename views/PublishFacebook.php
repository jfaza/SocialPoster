<?php
ee()->load->helper('form');
$spView = require __DIR__ . '/_view_helpers.php';
$h = fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<div class="sp-wrap sp-pubig">

  <header class="sp-pubig-head">
    <div class="sp-pubig-head-text">
      <h1>Publish to Facebook</h1>
      <p class="sp-muted">Review generated posts and publish them to the configured Facebook Page. Posts with images are published as Page photo posts.</p>
    </div>
  </header>

  <?php if (empty($facebook_posts)): ?>

    <div class="sp-pubig-empty">
      <h2>No unpublished Facebook posts</h2>
      <p class="sp-muted">Create a post with a Facebook template first, then publish it here.</p>
      <a class="button button--primary" href="<?= $h($generate_url) ?>">Create Facebook Post</a>
    </div>

  <?php else: ?>

    <ul class="sp-pubig-list">
      <?php foreach ($facebook_posts as $post): ?>
        <?php
          $id = (int) $post['id'];
          $hasImage = ! empty($post['image_path']) || ! empty($post['image_url']);
          $thumb = (string) ($post['image_url'] ?? '');
          $title = trim((string) ($post['title'] ?? ''));
          $body = trim((string) ($post['post_text'] ?? $post['intro_text'] ?? ''));
          $excerpt = $spView->excerpt($body);
          $editUrl = $history_url . '/' . $id;
        ?>
        <li class="sp-pubig-item">

          <div class="sp-pubig-thumb">
            <?php if ($hasImage && $thumb !== ''): ?>
              <img src="<?= $h($thumb) ?>" alt="" loading="lazy">
            <?php else: ?>
              <div class="sp-pubig-thumb-empty" aria-label="No image">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="22" height="22"><rect x="4" y="4" width="16" height="16" rx="2"/><path d="M7 9h10M7 13h7M7 17h5"/></svg>
              </div>
            <?php endif; ?>
          </div>

          <div class="sp-pubig-body">
            <div class="sp-pubig-row1">
              <h3 class="sp-pubig-title"><?= $h($title !== '' ? $title : 'Untitled post') ?></h3>
              <span class="sp-pill sp-pill-ready"><?= $hasImage ? 'Photo post' : 'Text post' ?></span>
            </div>

            <p class="sp-pubig-meta">
              <span>#<?= $id ?></span>
              <span class="sp-dot"></span>
              <time><?= $h(date('M j, Y - g:ia', (int) $post['created_at'])) ?></time>
            </p>

            <?php if ($excerpt !== ''): ?>
              <p class="sp-pubig-excerpt"><?= $h($excerpt) ?></p>
            <?php endif; ?>
          </div>

          <div class="sp-pubig-actions">
            <a class="button button--default" href="<?= $h($editUrl) ?>">View</a>
            <?= form_open($publish_url, ['class' => 'sp-pubig-form']) ?>
              <input type="hidden" name="publish_to_facebook" value="1">
              <input type="hidden" name="generation_id" value="<?= $id ?>">
              <button type="submit" class="button button--primary">Publish</button>
            <?= form_close() ?>
          </div>

        </li>
      <?php endforeach; ?>
    </ul>

  <?php endif; ?>

</div>
