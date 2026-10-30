<?php
ee()->load->helper('form');
$h = fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$effective = is_array($effective_settings ?? null) ? $effective_settings : [];
$graphVersion = $settings['facebook_graph_version'] ?? ($effective['graph_version'] ?? 'v24.0');
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
$activeTab = $active_tab ?? 'credentials';
$activeIndex = ['credentials' => 0, 'tokens' => 1][$activeTab] ?? 0;
$tabClass = fn(int $i) => 'tab-bar__tab js-tab-button' . ($activeIndex === $i ? ' active' : '');
$paneClass = fn(int $i) => 'tab t-' . $i . ($activeIndex === $i ? ' tab-open' : '');
?>
<div class="sp-wrap">
  <header class="sp-channel-head">
    <div>
      <p class="sp-channel-kicker">Facebook</p>
      <h1>Settings</h1>
      <p class="sp-muted">Manage Facebook Page credentials, token tools, and the publishing connection used by SocialPoster.</p>
    </div>
    <nav class="sp-channel-nav">
      <a class="button button--default" href="<?= $h($general_url) ?>">Create</a>
      <a class="button button--default" href="<?= $h($history_url) ?>">History</a>
      <a class="button button--default" href="<?= $h($publish_url) ?>">Publish</a>
    </nav>
  </header>

  <div class="tab-wrap">
    <div class="tab-bar">
      <div class="tab-bar__tabs">
        <button type="button" class="<?= $tabClass(0) ?>" rel="t-0">Credentials</button>
        <button type="button" class="<?= $tabClass(1) ?>" rel="t-1">Token Tools</button>
      </div>
    </div>

    <div class="<?= $paneClass(0) ?>">
      <?= form_open($save_url) ?>
        <input type="hidden" name="save_facebook_settings" value="1">

        <div class="sp-field" style="margin:20px 0 16px;">
          <label>Meta Requirements</label>
          <div class="sp-muted">
            <p>Target: Facebook Page publishing. Personal profile posting is not supported by Meta's publishing API.</p>
            <p>Required permissions: <code>pages_manage_posts</code>, <code>pages_read_engagement</code>, and <code>pages_show_list</code> for listing Pages.</p>
            <p>Token type: use a Page access token for publishing. You can fetch one from a long-lived user token in the Token Tools tab.</p>
            <p>If Facebook fields are blank, SocialPoster reuses the matching Instagram Meta/Page settings.</p>
          </div>
        </div>

        <div class="sp-field" style="margin:0 0 16px;">
          <label>Effective Facebook Publishing Settings</label>
          <div class="sp-muted">
            <p>App ID: <?= $h($effective['app_id'] ?? '') !== '' ? $h($mask($effective['app_id'])) : 'Not configured' ?></p>
            <p>Graph version: <code><?= $h($effective['graph_version'] ?? 'v24.0') ?></code></p>
            <p>Page ID: <?= $h($effective['page_id'] ?? '') !== '' ? '<code>' . $h($effective['page_id']) . '</code>' : 'Not configured' ?></p>
            <p>Page token: <?= $h($effective['access_token'] ?? '') !== '' ? $h($mask($effective['access_token'])) : 'Not configured' ?></p>
          </div>
        </div>

        <div class="sp-mini-grid">
          <div class="sp-field">
            <label for="facebook_app_id">Facebook App ID</label>
            <input type="text" id="facebook_app_id" name="facebook_app_id" value="<?= $h($settings['facebook_app_id'] ?? '') ?>" placeholder="App ID from Meta for Developers">
            <p class="sp-muted">Blank uses Instagram Meta App ID.</p>
          </div>

          <div class="sp-field">
            <label for="facebook_graph_version">Graph API Version</label>
            <input type="text" id="facebook_graph_version" name="facebook_graph_version" value="<?= $h($graphVersion) ?>" placeholder="v24.0">
            <p class="sp-muted">Blank uses Instagram Graph API version.</p>
          </div>
        </div>

        <div class="sp-field">
          <label for="facebook_app_secret">Facebook App Secret</label>
          <input type="password" id="facebook_app_secret" name="facebook_app_secret" value="" placeholder="<?= $facebook_app_secret_saved ? 'Saved. Leave blank to keep it.' : 'App secret from Meta for Developers' ?>">
          <p class="sp-muted">Saved: <?= $h($mask($settings['facebook_app_secret'] ?? '')) ?></p>
          <p class="sp-muted">Blank uses Instagram Meta App Secret.</p>
        </div>

        <div class="sp-field">
          <label for="facebook_page_id">Facebook Page ID</label>
          <input type="text" id="facebook_page_id" name="facebook_page_id" value="<?= $h($settings['facebook_page_id'] ?? '') ?>" placeholder="Page ID to publish to">
          <p class="sp-muted">Blank uses the Facebook Page ID saved in Instagram settings.</p>
        </div>

        <div class="sp-field">
          <label for="facebook_access_token">Facebook Page Access Token</label>
          <input type="password" id="facebook_access_token" name="facebook_access_token" value="" placeholder="<?= $facebook_access_token_saved ? 'Access token saved. Leave blank to keep it.' : 'Page access token with pages_manage_posts' ?>">
          <p class="sp-muted">Saved: <?= $h($mask($settings['facebook_access_token'] ?? '')) ?></p>
          <p class="sp-muted">Blank uses the Page access token saved in Instagram settings.</p>
        </div>

        <div class="sp-actions">
          <button type="submit" class="button button--primary">Save Credentials</button>
        </div>
      <?= form_close() ?>
    </div>

    <div class="<?= $paneClass(1) ?>">
      <?php
        $tokenStatus = $token_status ?? null;
        $hasStatus = is_array($tokenStatus) && ($tokenStatus['expires_at'] > 0 || ! empty($tokenStatus['error']) || ! empty($tokenStatus['type']));
      ?>

      <?php if ($hasStatus): ?>
        <div class="sp-field" style="margin:20px 0 16px;">
          <label>Saved Token Status</label>
          <?php if (! empty($tokenStatus['is_valid'])): ?>
            <p class="sp-muted">
              <strong style="color:#16a34a;">Valid</strong>
              <?php if (! empty($tokenStatus['type'])): ?> - Type: <code><?= $h($tokenStatus['type']) ?></code><?php endif; ?>
              <?php if ((int) $tokenStatus['expires_at'] > 0): ?>
                <?php $days = (int) floor(((int) $tokenStatus['expires_at'] - time()) / 86400); ?>
                - Expires <?= $h(date('M j, Y g:ia', (int) $tokenStatus['expires_at'])) ?> (<?= $days ?> days)
              <?php else: ?>
                - Does not expire
              <?php endif; ?>
            </p>
          <?php else: ?>
            <p class="sp-muted" style="color:#dc2626;"><strong>Invalid or expired</strong><?php if (! empty($tokenStatus['error'])): ?> - <?= $h($tokenStatus['error']) ?><?php endif; ?></p>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <?= form_open($save_url, ['class' => 'sp-card']) ?>
        <input type="hidden" name="exchange_facebook_token" value="1">
        <h3 style="margin-top:0;">Exchange Token</h3>
        <p class="sp-muted">Exchange a short-lived user token for a long-lived user token. Then use "List My Pages" to save the Page token used for publishing.</p>
        <div class="sp-field">
          <label for="exchange_short_token">Paste User Token</label>
          <input type="password" id="exchange_short_token" name="exchange_short_token" value="" placeholder="Token from Graph API Explorer">
        </div>
        <div class="sp-actions">
          <button type="submit" class="button button--primary">Exchange &amp; Save User Token</button>
        </div>
      <?= form_close() ?>

      <div class="sp-card">
        <h3 style="margin-top:0;">Save a Page Token</h3>
        <p class="sp-muted">Fetch Pages available to the saved user token and choose the Page SocialPoster should publish to.</p>
        <?= form_open($save_url, ['style' => 'margin-bottom:0;']) ?>
          <input type="hidden" name="list_facebook_pages" value="1">
          <button type="submit" class="button button--default">List My Pages</button>
        <?= form_close() ?>

        <?php if (is_array($pages)): ?>
          <?php if (empty($pages)): ?>
            <p class="sp-muted" style="margin-top:14px;">No pages returned.</p>
          <?php else: ?>
            <div class="sp-pages-list" style="margin-top:14px;">
              <?php foreach ($pages as $page): ?>
                <div class="sp-page-row">
                  <div>
                    <strong><?= $h($page['name']) ?></strong>
                    <p class="sp-muted" style="margin:2px 0 0;font-size:12px;">
                      Page ID: <code><?= $h($page['id']) ?></code>
                      <?php if (! empty($page['tasks'])): ?>
                        - Tasks: <?= $h(implode(', ', $page['tasks'])) ?>
                      <?php endif; ?>
                    </p>
                  </div>
                  <?= form_open($save_url, ['style' => 'margin:0;']) ?>
                    <input type="hidden" name="use_facebook_page_token" value="1">
                    <input type="hidden" name="page_id" value="<?= $h($page['id']) ?>">
                    <button type="submit" class="button button--primary">Use This Page</button>
                  <?= form_close() ?>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        <?php endif; ?>
      </div>

      <?= form_open($save_url, ['class' => 'sp-card']) ?>
        <input type="hidden" name="test_facebook_connection" value="1">
        <h3 style="margin-top:0;">Test Connection</h3>
        <p class="sp-muted">Checks the saved Page token and confirms the Page ID resolves.</p>

        <?php if (isset($test_result) && is_array($test_result)): ?>
          <?php $r = $test_result; ?>
          <div class="sp-test-result <?= empty($r['errors']) && empty($r['missing_scopes']) ? 'sp-test-ok' : 'sp-test-fail' ?>">
            <?php if (! empty($r['errors'])): ?>
              <p><strong>Errors</strong></p>
              <ul><?php foreach ($r['errors'] as $e): ?><li><?= $h($e) ?></li><?php endforeach; ?></ul>
            <?php endif; ?>
            <?php if (! empty($r['warnings'])): ?>
              <p><strong>Warnings</strong></p>
              <ul><?php foreach ($r['warnings'] as $w): ?><li><?= $h($w) ?></li><?php endforeach; ?></ul>
            <?php endif; ?>
            <table class="sp-test-table">
              <tr><td>Token valid</td><td><?= $r['token_valid'] ? '<span style="color:#16a34a;">Yes</span>' : '<span style="color:#dc2626;">No</span>' ?></td></tr>
              <?php if ($r['token_type'] !== ''): ?><tr><td>Token type</td><td><code><?= $h($r['token_type']) ?></code></td></tr><?php endif; ?>
              <?php if ($r['token_expires'] !== ''): ?><tr><td>Expires</td><td><?= $h($r['token_expires']) ?></td></tr><?php endif; ?>
              <?php if ($r['page_name'] !== ''): ?><tr><td>Page</td><td><strong><?= $h($r['page_name']) ?></strong> <span class="sp-muted">(<?= $h($r['page_id']) ?>)</span></td></tr><?php endif; ?>
              <?php if (! empty($r['missing_scopes'])): ?><tr><td>Missing scopes</td><td><code><?= $h(implode(', ', $r['missing_scopes'])) ?></code></td></tr><?php endif; ?>
            </table>
          </div>
        <?php endif; ?>

        <div class="sp-actions">
          <button type="submit" class="button button--default">Test Connection</button>
        </div>
      <?= form_close() ?>
    </div>
  </div>
</div>
