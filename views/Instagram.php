<?php
ee()->load->helper('form');
$h = fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$instagramGraphVersion = $settings['instagram_graph_version'] ?? 'v24.0';
$coverProvider = $settings['instagram_cover_provider'] ?? 'openai';
$slideProvider = $settings['instagram_slide_provider'] ?? 'gd';
$carouselSlides = (int) ($settings['carousel_slides'] ?? 6);
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
$providerOptions = [
    'openai'   => 'OpenAI (AI photo)',
    'ideogram' => 'Ideogram v2 (text-in-image, ~$0.04/image)',
    'gd'       => 'PHP GD (typographic render, free)',
    'none'     => 'None (skip)',
];

$activeTab   = $active_tab ?? 'credentials';
$tabMap      = ['credentials' => 0, 'tokens' => 1, 'images' => 2];
$activeIndex = $tabMap[$activeTab] ?? 0;
$tabClass    = fn(int $i) => 'tab-bar__tab js-tab-button' . ($activeIndex === $i ? ' active' : '');
$paneClass   = fn(int $i) => 'tab t-' . $i . ($activeIndex === $i ? ' tab-open' : '');
?>
<div class="sp-wrap sp-instagram-settings">
  <header class="sp-channel-head">
    <div>
      <p class="sp-channel-kicker">Instagram</p>
      <h1>Settings</h1>
      <p class="sp-muted">Manage channel credentials, token tools, and image-generation settings for Instagram publishing.</p>
    </div>
    <nav class="sp-channel-nav">
      <a class="button button--default" href="<?= $h($generate_url) ?>">Create</a>
      <a class="button button--default" href="<?= $h($instagram_history_url) ?>">History</a>
      <a class="button button--default" href="<?= $h($instagram_carousel_url) ?>">Carousels</a>
      <a class="button button--default" href="<?= $h($publish_url) ?>">Publish</a>
    </nav>
  </header>

  <div class="tab-wrap sp-channel-tabs">
    <div class="tab-bar">
      <div class="tab-bar__tabs">
        <button type="button" class="<?= $tabClass(0) ?>" rel="t-0">Credentials</button>
        <button type="button" class="<?= $tabClass(1) ?>" rel="t-1">Token Tools</button>
        <button type="button" class="<?= $tabClass(2) ?>" rel="t-2">Images</button>
      </div>
    </div>

    <!-- Tab 0: Credentials -->
    <div class="<?= $paneClass(0) ?> sp-instagram-settings-pane">
      <?= form_open($save_url, ['class' => 'sp-instagram-settings-form']) ?>
        <input type="hidden" name="save_instagram_settings" value="1">

        <div class="sp-field sp-requirements-panel" style="margin:20px 0 16px;">
          <label>Meta Requirements</label>
          <div class="sp-muted">
            <p>Account: Instagram Professional account. Creator and Business accounts are supported by Meta's professional API flow.</p>
            <p>Linked assets: the Instagram Professional account must be linked to a Facebook Page.</p>
            <p>Required permissions: <code>instagram_basic</code>, <code>instagram_content_publish</code>, and <code>pages_read_engagement</code>.</p>
            <p>Required Page task access: <code>MANAGE</code> or <code>CREATE_CONTENT</code> on the linked Facebook Page.</p>
            <p>Token type: use a Meta User access token for the app user. Do not paste an Instagram password or a personal session token here.</p>
          </div>
        </div>

        <div class="sp-mini-grid">
          <div class="sp-field">
            <label for="instagram_app_id">Meta App ID</label>
            <input type="text" id="instagram_app_id" name="instagram_app_id" value="<?= $h($settings['instagram_app_id'] ?? '') ?>" placeholder="App ID from Meta for Developers">
            <p class="sp-muted">Needed when you are managing the full Meta app and token flow for publishing.</p>
          </div>

          <div class="sp-field">
            <label for="instagram_graph_version">Graph API Version</label>
            <input type="text" id="instagram_graph_version" name="instagram_graph_version" value="<?= $h($instagramGraphVersion) ?>" placeholder="v24.0">
            <p class="sp-muted">Current default in this add-on is <code>v24.0</code>.</p>
          </div>
        </div>

        <div class="sp-field">
          <label for="instagram_app_secret">Meta App Secret</label>
          <input type="password" id="instagram_app_secret" name="instagram_app_secret" value="" placeholder="<?= $instagram_app_secret_saved ? 'Saved. Leave blank to keep it.' : 'App secret from Meta for Developers' ?>">
          <p class="sp-muted">Saved: <?= $h($mask($settings['instagram_app_secret'] ?? '')) ?></p>
        </div>

        <div class="sp-mini-grid">
          <div class="sp-field">
            <label for="instagram_ig_app_id">Instagram App ID</label>
            <input type="text" id="instagram_ig_app_id" name="instagram_ig_app_id" value="<?= $h($settings['instagram_ig_app_id'] ?? '') ?>" placeholder="From Instagram → API Setup with Instagram Login">
            <p class="sp-muted">Separate from the Meta App ID. Found under your app's Instagram product setup.</p>
          </div>

          <div class="sp-field">
            <label for="instagram_ig_app_secret">Instagram App Secret</label>
            <input type="password" id="instagram_ig_app_secret" name="instagram_ig_app_secret" value="" placeholder="<?= ! empty($settings['instagram_ig_app_secret']) ? 'Saved. Leave blank to keep it.' : 'Instagram-specific app secret' ?>">
            <p class="sp-muted">Saved: <?= $h($mask($settings['instagram_ig_app_secret'] ?? '')) ?></p>
          </div>
        </div>

        <div class="sp-mini-grid">
          <div class="sp-field">
            <label for="instagram_page_id">Facebook Page ID</label>
            <input type="text" id="instagram_page_id" name="instagram_page_id" value="<?= $h($settings['instagram_page_id'] ?? '') ?>" placeholder="Page linked to the Instagram Professional account">
            <p class="sp-muted">Meta's publishing flow requires the Instagram Professional account to be linked to a Facebook Page.</p>
          </div>

          <div class="sp-field">
            <label for="instagram_business_account_id">Instagram Professional Account ID</label>
            <input type="text" id="instagram_business_account_id" name="instagram_business_account_id" value="<?= $h($settings['instagram_business_account_id'] ?? '') ?>" placeholder="IG User ID / Instagram Professional Account ID">
            <p class="sp-muted">The Professional account's IG User ID used by the Graph API (stored as <code>instagram_business_account_id</code>).</p>
          </div>
        </div>

        <div class="sp-field">
          <label for="instagram_access_token">User Access Token</label>
          <input type="password" id="instagram_access_token" name="instagram_access_token" value="" placeholder="<?= $instagram_access_token_saved ? 'Access token saved. Leave blank to keep it.' : 'Meta User access token with publishing permissions' ?>">
          <p class="sp-muted">Saved: <?= $h($mask($settings['instagram_access_token'] ?? '')) ?></p>
          <p class="sp-muted">Use a token that can access the linked Page and publish to the Instagram Professional account.</p>
        </div>

        <div class="sp-actions">
          <button type="submit" name="save_instagram_settings" value="1" class="button button--primary">Save Credentials</button>
        </div>
      <?= form_close() ?>
    </div>

    <!-- Tab 1: Token Tools -->
    <div class="<?= $paneClass(1) ?> sp-instagram-settings-pane sp-instagram-token-pane">
      <?php
        $tokenStatus = $token_status ?? null;
        $hasStatus   = is_array($tokenStatus) && ($tokenStatus['expires_at'] > 0 || ! empty($tokenStatus['error']) || ! empty($tokenStatus['type']));
      ?>

      <?php if ($hasStatus): ?>
        <div class="sp-field sp-token-status-panel" style="margin:20px 0 16px;">
          <label>Saved Token Status</label>
          <?php if (! empty($tokenStatus['is_valid'])): ?>
            <p class="sp-muted">
              <strong style="color:#16a34a;">Valid</strong>
              <?php if (! empty($tokenStatus['type'])): ?> · Type: <code><?= $h($tokenStatus['type']) ?></code><?php endif; ?>
              <?php if ((int) $tokenStatus['expires_at'] > 0): ?>
                <?php $secs = (int) $tokenStatus['expires_at'] - time(); $days = (int) floor($secs / 86400); ?>
                · Expires <?= $h(date('M j, Y g:ia', (int) $tokenStatus['expires_at'])) ?>
                <?php if ($days >= 0): ?> (<?= $days ?> day<?= $days === 1 ? '' : 's' ?> from now)<?php endif; ?>
              <?php else: ?>
                · Does not expire
              <?php endif; ?>
            </p>
          <?php else: ?>
            <p class="sp-muted" style="color:#dc2626;">
              <strong>Invalid or expired</strong><?php if (! empty($tokenStatus['error'])): ?> — <?= $h($tokenStatus['error']) ?><?php endif; ?>
            </p>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <?= form_open($save_url, ['class' => 'sp-card']) ?>
        <input type="hidden" name="exchange_instagram_token" value="1">
        <h3 style="margin-top:0;">Exchange Token</h3>
        <p class="sp-muted">Exchange a short-lived token (e.g. from <a href="https://developers.facebook.com/tools/explorer" target="_blank" rel="noopener">Graph API Explorer</a>) for a 60-day long-lived token. Uses your saved Meta App ID + App Secret.</p>

        <div class="sp-field">
          <label for="exchange_short_token">Paste Short-Lived (or Active) Token</label>
          <input type="password" id="exchange_short_token" name="exchange_short_token" value="" placeholder="Token from Graph API Explorer">
          <p class="sp-muted">Any currently-valid user access token works. The exchange returns a fresh 60-day long-lived token.</p>
        </div>

        <div class="sp-actions">
          <button type="submit" class="button button--primary">Exchange &amp; Save Long-Lived Token</button>
        </div>
      <?= form_close() ?>

      <div class="sp-card">
        <h3 style="margin-top:0;">Convert to Page Access Token (non-expiring)</h3>
        <p class="sp-muted">Page tokens issued from a long-lived user token <strong>do not expire</strong> &mdash; you only need to redo this if you change your Facebook password or lose Page admin access. After exchanging a long-lived user token above, click below to fetch your Pages and pick the right one.</p>

        <?= form_open($save_url, ['style' => 'margin-bottom:0;']) ?>
          <input type="hidden" name="list_instagram_pages" value="1">
          <button type="submit" class="button button--default">List My Pages</button>
        <?= form_close() ?>

        <?php if (is_array($pages)): ?>
          <?php if (empty($pages)): ?>
            <p class="sp-muted" style="margin-top:14px;">No pages returned. Ensure your token has <code>pages_show_list</code> and you administer at least one Facebook Page.</p>
          <?php else: ?>
            <div class="sp-pages-list" style="margin-top:14px;">
              <?php foreach ($pages as $page): ?>
                <div class="sp-page-row">
                  <div>
                    <strong><?= $h($page['name']) ?></strong>
                    <p class="sp-muted" style="margin:2px 0 0;font-size:12px;">
                      Page ID: <code><?= $h($page['id']) ?></code>
                      <?php if (! empty($page['instagram_username'])): ?>
                        &nbsp;·&nbsp; IG: <strong>@<?= $h($page['instagram_username']) ?></strong>
                        <?php if (! empty($page['instagram_business_account_id'])): ?>
                          <span class="sp-muted">(<?= $h($page['instagram_business_account_id']) ?>)</span>
                        <?php endif; ?>
                      <?php else: ?>
                        &nbsp;·&nbsp; <span style="color:#dc2626;">No linked Instagram Professional account</span>
                      <?php endif; ?>
                    </p>
                  </div>
                  <?= form_open($save_url, ['style' => 'margin:0;']) ?>
                    <input type="hidden" name="use_page_token" value="1">
                    <input type="hidden" name="page_id" value="<?= $h($page['id']) ?>">
                    <button type="submit" class="button button--primary"
                            <?= empty($page['instagram_business_account_id']) ? 'title="No IG account linked, but you can still use the token if you set Business Account ID manually."' : '' ?>>
                      Use This Page's Token
                    </button>
                  <?= form_close() ?>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        <?php endif; ?>
      </div>

      <?= form_open($save_url, ['class' => 'sp-card']) ?>
        <input type="hidden" name="test_instagram_connection" value="1">
        <h3 style="margin-top:0;">Test Connection</h3>
        <p class="sp-muted">Checks that your saved token is valid, has the required publishing scopes, and that the Business Account ID resolves correctly.</p>

        <?php if (isset($test_result) && is_array($test_result)): ?>
          <?php
            $r       = $test_result;
            $ok      = empty($r['errors']) && empty($r['missing_scopes']);
            $allGood = $ok && ! empty($r['ig_username']);
          ?>
          <div class="sp-test-result <?= $allGood ? 'sp-test-ok' : (empty($r['errors']) ? 'sp-test-warn' : 'sp-test-fail') ?>">

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
              <?php if ($r['token_type'] !== ''): ?>
                <tr><td>Token type</td><td><code><?= $h($r['token_type']) ?></code></td></tr>
              <?php endif; ?>
              <?php if ($r['token_expires'] !== ''): ?>
                <tr><td>Expires</td><td><?= $h($r['token_expires']) ?></td></tr>
              <?php endif; ?>
              <tr>
                <td>Required scopes</td>
                <td>
                  <?php
                    $needed = [
                        'instagram_basic'           => 'instagram_basic or instagram_business_basic',
                        'instagram_content_publish' => 'instagram_content_publish(ing)',
                    ];
                    foreach ($needed as $scope => $label):
                      $has = in_array($scope, $r['scopes'], true)
                          || ($scope === 'instagram_basic'           && in_array('instagram_business_basic',     $r['scopes'], true))
                          || ($scope === 'instagram_content_publish' && in_array('instagram_content_publishing', $r['scopes'], true));
                  ?>
                    <span style="color:<?= $has ? '#16a34a' : '#dc2626' ?>;"><?= $has ? '✓' : '✗' ?> <?= $h($label) ?></span><br>
                  <?php endforeach; ?>
                </td>
              </tr>
              <?php if ($r['ig_username'] !== ''): ?>
                <tr><td>IG account</td><td><strong>@<?= $h($r['ig_username']) ?></strong> <span class="sp-muted">(<?= $h($r['ig_account']) ?>)</span></td></tr>
              <?php endif; ?>
              <?php if ($r['page_id'] !== ''): ?>
                <tr><td>Facebook Page ID</td><td><code><?= $h($r['page_id']) ?></code></td></tr>
              <?php endif; ?>
            </table>

            <?php if (! empty($r['missing_scopes'])): ?>
              <p class="sp-muted" style="margin-top:10px;">Missing scopes: <code><?= $h(implode(', ', $r['missing_scopes'])) ?></code>. Regenerate your token with these permissions in Graph API Explorer.</p>
            <?php elseif ($allGood): ?>
              <p style="color:#16a34a;margin-top:10px;"><strong>All checks passed — ready to publish.</strong></p>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <div class="sp-actions">
          <button type="submit" class="button button--default">Test Connection</button>
        </div>
      <?= form_close() ?>
    </div>

    <!-- Tab 2: Images -->
    <div class="<?= $paneClass(2) ?> sp-instagram-settings-pane sp-instagram-images-pane">

      <div class="sp-field sp-image-settings-note" style="margin:20px 0 4px;">
        <p class="sp-muted" style="background:#fffbeb;border:1px solid #fde68a;border-radius:6px;padding:10px 14px;margin:0;">
          <strong>Note:</strong> These settings apply to <strong>carousel slide images only</strong> (Instagram Create flow).
          News post images are always generated via <strong>OpenAI</strong> using the model configured in <a href="<?= $h($settings_url) ?>">OpenAI Settings</a>.
        </p>
      </div>

      <?= form_open($save_image_settings_url, ['class' => 'sp-image-settings-form']) ?>
        <input type="hidden" name="save_instagram_image_settings" value="1">

        <div class="sp-field sp-provider-guide" style="margin:20px 0 16px;">
          <label>Provider Guide</label>
          <div class="sp-muted">
            <p>Each carousel has a <strong>cover</strong> (slide 1, the hero) and <strong>content slides</strong> (slides 2–N with headings and bullets). You can use a different provider for each.</p>
            <p><strong>PHP GD</strong> &mdash; renders text onto a dark typographic slide on your server. Free, instant, text is always accurate. Best choice for content slides.</p>
            <p><strong>Ideogram v2</strong> &mdash; sends slide text to Ideogram AI for a designed image. Best for text-in-image at ~$0.04/image. Requires an Ideogram API key below.</p>
            <p><strong>OpenAI</strong> &mdash; generates an AI photo/illustration via your OpenAI key. Good for abstract cover images; poor text accuracy, so avoid for content slides.</p>
            <p><strong>None</strong> &mdash; skips image generation for that slot.</p>
          </div>
        </div>

        <div class="sp-mini-grid">
          <div class="sp-field">
            <label for="instagram_cover_provider">Cover Slide Provider (Slide 1)</label>
            <select id="instagram_cover_provider" name="instagram_cover_provider">
              <?php foreach ($providerOptions as $val => $label): ?>
                <option value="<?= $h($val) ?>"<?= $coverProvider === $val ? ' selected' : '' ?>><?= $h($label) ?></option>
              <?php endforeach; ?>
            </select>
            <p class="sp-muted">The hero/hook image. OpenAI works well here for a visual splash.</p>
          </div>

          <div class="sp-field">
            <label for="instagram_slide_provider">Content Slides Provider (Slides 2–N)</label>
            <select id="instagram_slide_provider" name="instagram_slide_provider">
              <?php foreach ($providerOptions as $val => $label): ?>
                <option value="<?= $h($val) ?>"<?= $slideProvider === $val ? ' selected' : '' ?>><?= $h($label) ?></option>
              <?php endforeach; ?>
            </select>
            <p class="sp-muted">Slides with headings and bullets. PHP GD is recommended — text is always accurate and free.</p>
          </div>
        </div>

        <div class="sp-field" style="max-width:220px; margin-top:10px;">
          <label for="carousel_slides">Default Carousel Slides</label>
          <input type="number" id="carousel_slides" name="carousel_slides"
                 value="<?= $carouselSlides ?>" min="3" max="12" step="1">
          <p class="sp-muted">Used as the default slide count for Instagram carousel generation.</p>
        </div>

        <div class="sp-field">
          <label for="ideogram_api_key">Ideogram API Key</label>
          <input type="password" id="ideogram_api_key" name="ideogram_api_key" value="" placeholder="<?= $ideogram_api_key_saved ? 'Key saved. Leave blank to keep it.' : 'Required only when Ideogram is selected above' ?>">
          <p class="sp-muted">Saved: <?= $h($mask($settings['ideogram_api_key'] ?? '')) ?></p>
          <p class="sp-muted">Get a key at <strong>ideogram.ai</strong> &rarr; API. Only needed if Ideogram is selected as cover or content slide provider.</p>
        </div>

        <div class="sp-actions">
          <button type="submit" class="button button--primary">Save Image Settings</button>
        </div>
      <?= form_close() ?>
    </div>

  </div><!-- /.tab-wrap -->
</div><!-- /.sp-wrap -->
