<?php
ee()->load->helper('form');
$h = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$templates_url = $templates_url ?? '';

$styleOptions = [
    'base'      => 'Base — practical and useful',
    'viral'     => 'Viral — bold hooks, shareability',
    'technical' => 'Technical — deep dives for builders',
];
?>
<div class="sp-wrap">
  <header class="sp-channel-head">
    <div>
      <p class="sp-channel-kicker">Instagram</p>
      <h1>Create</h1>
      <p class="sp-muted">Build news posts, single-image posts, or carousels from the same channel workspace.</p>
    </div>
  </header>

  <!-- Mode toggle -->
  <div class="sp-mode-bar">
    <button type="button" class="sp-mode-btn is-active" data-mode="news">
      <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" width="18" height="18"><rect x="3" y="3" width="14" height="14" rx="2"/><path d="M7 7h6M7 10h6M7 13h4"/></svg>
      News Post
    </button>
    <button type="button" class="sp-mode-btn" data-mode="single">
      <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" width="18" height="18"><rect x="3" y="3" width="14" height="14" rx="2"/><circle cx="10" cy="9" r="3"/><path d="M5 17c0-2.21 2.24-4 5-4s5 1.79 5 4" stroke-linecap="round"/></svg>
      Single Image
    </button>
    <button type="button" class="sp-mode-btn" data-mode="carousel">
      <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" width="18" height="18"><rect x="1" y="5" width="6" height="10" rx="1.5" opacity=".45"/><rect x="7" y="3" width="6" height="14" rx="1.5"/><rect x="13" y="5" width="6" height="10" rx="1.5" opacity=".45"/></svg>
      Carousel
    </button>
  </div>

  <!-- ── NEWS POST ─────────────────────────────────────────── -->
  <div id="sp-mode-news" class="sp-mode-panel">

    <?php if (empty($news_sources)): ?>
      <!-- No sources configured -->
      <div class="sp-card sp-news-empty">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="40" height="40"><path d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10l6 6v8a2 2 0 01-2 2z"/><path d="M13 2v6h6M9 13h6M9 17h3"/></svg>
        <h3>No news sources yet</h3>
        <p class="sp-muted">Add RSS feeds to pull articles for post generation.</p>
        <a class="button button--primary" href="<?= $h($news_sources_url) ?>">Manage News Sources</a>
      </div>
    <?php else: ?>

      <!-- Step 1: Source picker -->
      <div class="sp-card">
        <h2>News Post</h2>
        <p class="sp-muted">Pick a source, choose an article, then generate a caption and hashtags.</p>

        <div class="sp-mini-grid" style="align-items:flex-end;">
          <div class="sp-field" style="margin-bottom:0;">
            <label for="sp-news-source">News Source</label>
            <select id="sp-news-source">
              <option value="">— select a source —</option>
              <?php foreach ($news_sources as $src): ?>
                <option value="<?= (int) $src['id'] ?>"><?= $h($src['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div style="padding-bottom:0;">
            <button type="button" id="sp-news-load-btn" class="button button--default" disabled>Load Articles</button>
            <a href="<?= $h($news_sources_url) ?>" class="sp-muted" style="font-size:12px;margin-left:12px;">Manage sources + hot articles</a>
          </div>
        </div>
        <div id="sp-news-load-status" class="sp-muted" style="margin-top:8px;"></div>
      </div>

      <!-- Step 2: Article list (rendered via JS) -->
      <div id="sp-news-articles-wrap" style="display:none;">
        <div class="sp-card">
          <h3 style="margin-bottom:12px;">Choose an Article</h3>
          <div id="sp-news-article-grid" class="sp-article-grid"></div>
        </div>
      </div>

      <!-- Step 3: Generation form (shown after article selected) -->
      <div id="sp-news-form-wrap" style="display:none;">
        <div class="sp-card">
          <h3>Selected Article</h3>
          <div id="sp-news-selected-preview" class="sp-selected-article"></div>

          <div class="sp-field" style="margin-top:16px;">
            <label for="sp-news-caption-hint">Caption Instructions <span class="sp-muted" style="font-weight:400;">(optional)</span></label>
            <textarea id="sp-news-caption-hint" rows="2"
              placeholder="e.g. 'write in first person', 'focus on the business impact', 'mention developers specifically'"></textarea>
          </div>

          <div class="sp-field">
            <label>Image</label>
            <div class="sp-radio-group">
              <label class="sp-radio">
                <input type="radio" name="sp-news-image-choice" value="article" id="sp-news-img-article">
                <span>Use article image</span>
              </label>
              <label class="sp-radio">
                <input type="radio" name="sp-news-image-choice" value="generate" id="sp-news-img-generate">
                <span>Generate with AI</span>
              </label>
              <label class="sp-radio">
                <input type="radio" name="sp-news-image-choice" value="none" id="sp-news-img-none" checked>
                <span>No image</span>
              </label>
            </div>
            <div id="sp-news-img-article-warn" class="sp-muted" style="display:none;font-size:12px;margin-top:4px;">
              No image found in this article feed — AI generation will be used instead.
            </div>
          </div>

          <div id="sp-news-image-hint-wrap" style="display:none;">
            <div class="sp-field">
              <label for="sp-news-image-hint">Image Instructions <span class="sp-muted" style="font-weight:400;">(optional)</span></label>
              <textarea id="sp-news-image-hint" rows="2"
                placeholder="e.g. 'abstract tech cityscape, dark blue tones', 'person at a laptop in a café'"></textarea>
            </div>
          </div>

          <div class="sp-actions">
            <button type="button" id="sp-news-generate-btn" class="button button--primary">Generate Post</button>
            <span id="sp-news-status" class="sp-muted"></span>
          </div>
        </div>
      </div>

    <?php endif; ?>
  </div>

  <!-- ── SINGLE IMAGE ─────────────────────────────────────────── -->
  <div id="sp-mode-single" class="sp-mode-panel" style="display:none;">
    <div class="sp-card">
      <h2>Generate Single Image Post</h2>
      <p class="sp-muted">AI writes an Instagram caption and hashtags, then generates one polished image to go with it.</p>

      <div class="sp-single-assist-panel">
        <h3>AI Assist</h3>
        <p class="sp-muted">Pick one news source and let AI suggest a timely topic, caption direction, and image direction.</p>

        <?php if (empty($news_sources)): ?>
          <div class="sp-single-assist-empty">
            <p class="sp-muted">No active news sources are configured yet.</p>
            <a class="button button--default" href="<?= $h($news_sources_url) ?>">Manage News Sources</a>
          </div>
        <?php else: ?>
          <div class="sp-mini-grid sp-mini-grid--3">
            <div class="sp-field">
              <label for="sp-single-source">News Source</label>
              <select id="sp-single-source">
                <option value="">Select a source</option>
                <?php foreach ($news_sources as $src): ?>
                  <option value="<?= (int) $src['id'] ?>"><?= $h($src['name']) ?><?= ! empty($src['category']) ? ' - ' . $h($src['category']) : '' ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="sp-field">
              <label for="sp-single-tone">Tone</label>
              <select id="sp-single-tone">
                <option value="practical, polished, conversational">Practical + polished</option>
                <option value="bold, punchy, contrarian">Bold + punchy</option>
                <option value="warm, reflective, first-person">Warm + reflective</option>
                <option value="technical, precise, builder-focused">Technical + precise</option>
              </select>
            </div>
            <div class="sp-field">
              <label for="sp-single-goal">Goal</label>
              <select id="sp-single-goal">
                <option value="educate and invite engagement">Educate + engage</option>
                <option value="explain the business impact and invite a DM">Business impact + DM</option>
                <option value="teach one practical takeaway and ask a question">Practical takeaway</option>
                <option value="position Javid Fazaeli as a helpful technical partner">Position expertise</option>
              </select>
            </div>
          </div>

          <div class="sp-field">
            <label for="sp-single-audience">Audience</label>
            <input type="text" id="sp-single-audience" value="developers, founders, and AI-curious business owners">
          </div>

          <div class="sp-actions sp-single-assist-actions">
            <button type="button" id="sp-single-assist-btn" class="button button--default">Generate Topic + Instructions</button>
            <span id="sp-single-assist-status" class="sp-muted"></span>
          </div>
          <p id="sp-single-source-context" class="sp-muted sp-single-source-context"></p>
        <?php endif; ?>
      </div>

      <div class="sp-field sp-generated-field">
        <label for="sp-single-topic">Topic / Subject</label>
        <input type="text" id="sp-single-topic"
               placeholder="e.g. morning coffee ritual, new product launch, behind-the-scenes studio"
               autocomplete="off">
        <p class="sp-muted">A clear subject gets a more focused caption and image.</p>
      </div>

      <div class="sp-field sp-generated-field">
        <label for="sp-single-caption-prompt">Caption Instructions <span class="sp-muted" style="font-weight:400;">(optional)</span></label>
        <textarea id="sp-single-caption-prompt" rows="3"
          placeholder="e.g. 'tone should be warm and conversational', 'mention the 3 key benefits', 'end with a question to drive comments'"></textarea>
      </div>

      <div class="sp-field sp-generated-field">
        <label for="sp-single-image-hint">Image Instructions <span class="sp-muted" style="font-weight:400;">(optional)</span></label>
        <textarea id="sp-single-image-hint" rows="2"
          placeholder="e.g. 'golden-hour lighting, flat lay composition', 'dark moody background, product centre-frame', 'person looking away from camera'"></textarea>
      </div>

      <div class="sp-field">
        <label>Final Image Prompt Source</label>
        <div class="sp-radio-group sp-single-image-mode">
          <label class="sp-radio">
            <input type="radio" name="sp-single-image-mode" value="combined" checked>
            <span>Caption + topic + image instructions</span>
          </label>
          <label class="sp-radio">
            <input type="radio" name="sp-single-image-mode" value="image_only">
            <span>Image instructions only</span>
          </label>
        </div>
      </div>

      <div class="sp-actions">
        <button type="button" id="sp-single-generate-btn" class="button button--primary">Generate Post</button>
        <span id="sp-single-status" class="sp-muted"></span>
      </div>
    </div>
  </div>

  <!-- ── CAROUSEL ───────────────────────────────────────────── -->
  <div id="sp-mode-carousel" class="sp-mode-panel" style="display:none;">
    <div class="sp-card">
      <h2>Generate Carousel</h2>
      <p class="sp-muted">Enter a topic and the AI generates structured slide content using the selected visual template.</p>

      <div class="sp-field">
        <label for="sp-carousel-topic">Topic</label>
        <input type="text" id="sp-carousel-topic"
               placeholder="e.g. AI tools for developers, React performance tips, Git workflow"
               autocomplete="off">
        <p class="sp-muted">Be specific — this drives the slide content and structure.</p>
      </div>

      <div class="sp-field">
        <label for="sp-carousel-audience">Audience</label>
        <input type="text" id="sp-carousel-audience"
               value="developers, builders, and AI-curious professionals"
               autocomplete="off">
        <p class="sp-muted">Who will read this carousel? Tailors tone and vocabulary.</p>
      </div>

      <div class="sp-mini-grid">
        <div class="sp-field">
          <label for="sp-carousel-style">Style</label>
          <select id="sp-carousel-style">
            <?php foreach ($styleOptions as $val => $label): ?>
              <option value="<?= $h($val) ?>"><?= $h($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="sp-field">
          <label for="sp-carousel-slides">Slides <span class="sp-muted" style="font-weight:400;">(3–12, blank = default)</span></label>
          <input type="number" id="sp-carousel-slides" min="3" max="12" step="1" placeholder="Default from settings">
        </div>
      </div>

      <div class="sp-field">
        <label for="sp-carousel-content-prompt">Content Instructions <span class="sp-muted" style="font-weight:400;">(optional)</span></label>
        <textarea id="sp-carousel-content-prompt" rows="3"
          placeholder="Extra guidance for what the slides should say — e.g. 'focus on practical code examples', 'include a comparison table', 'reference the 80/20 rule'."></textarea>
      </div>

      <div class="sp-field">
        <label for="sp-carousel-design-prompt">Design Instructions <span class="sp-muted" style="font-weight:400;">(optional)</span></label>
        <textarea id="sp-carousel-design-prompt" rows="2"
          placeholder="Visual hints for the template — e.g. 'dark background with neon accents', 'minimal with lots of whitespace', 'use brand color #C65A2E prominently'."></textarea>
      </div>

      <div class="sp-mini-grid">
        <div class="sp-field">
          <label for="sp-carousel-tpl-select">Visual Template</label>
          <?php if ($templates_url !== ''): ?>
            <p class="sp-muted">Manage reusable visual templates from <a href="<?= $h($templates_url) ?>#tab=t-2">Carousel Templates</a>.</p>
          <?php endif; ?>
          <select id="sp-carousel-tpl-select">
            <?php foreach ($carousel_templates as $tpl): ?>
              <option value="<?= $h($tpl['folder']) ?>">
                <?= $h($tpl['name'] ?? $tpl['folder']) ?><?= $tpl['description'] ? ' — ' . $h(mb_strimwidth($tpl['description'], 0, 60, '…')) : '' ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <!-- Template card picker -->
      <?php if ($carousel_templates): ?>
      <div class="sp-field">
        <label>Visual Template</label>
        <div class="sp-template-grid" id="sp-carousel-tpl-grid">
          <?php foreach ($carousel_templates as $idx => $tpl): ?>
            <div class="sp-tpl-card <?= $idx === 0 ? 'is-selected' : '' ?>"
                 data-tpl="<?= $h($tpl['folder']) ?>">
              <?php if (! empty($tpl['previews'])): ?>
                <img src="<?= $h($tpl['previews'][0]) ?>" alt="<?= $h($tpl['name'] ?? $tpl['folder']) ?>" loading="lazy">
              <?php else: ?>
                <div class="sp-tpl-no-preview"><?= $h($tpl['name'] ?? $tpl['folder']) ?></div>
              <?php endif; ?>
              <span class="sp-tpl-label"><?= $h($tpl['name'] ?? $tpl['folder']) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <div class="sp-actions">
        <button type="button" id="sp-carousel-generate-btn" class="button button--primary">Generate Carousel</button>
        <span id="sp-carousel-status" class="sp-muted"></span>
      </div>
    </div>
  </div>

</div><!-- /.sp-wrap -->

<script>
(function () {
  var actionUrl  = <?= json_encode($action_url ?? '') ?>;
  var csrfToken  = <?= json_encode($csrf_token ?? '') ?>;
  var carouselPat = <?= json_encode($carousel_item_pat ?? '') ?>;
  var historyPat = <?= json_encode(ee('CP/URL')->make('addons/settings/socialposter/instagram/history/__ID__')->compile()) ?>;

  /* ── Mode toggle ─────────────────────────────────────────── */
  document.querySelectorAll('.sp-mode-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var mode = btn.dataset.mode;
      document.querySelectorAll('.sp-mode-btn').forEach(function (b) { b.classList.remove('is-active'); });
      btn.classList.add('is-active');
      document.querySelectorAll('.sp-mode-panel').forEach(function (p) { p.style.display = 'none'; });
      var panel = document.getElementById('sp-mode-' + mode);
      if (panel) panel.style.display = '';
    });
  });

  /* ── Carousel template card picker ──────────────────────── */
  var tplGrid   = document.getElementById('sp-carousel-tpl-grid');
  var tplSelect = document.getElementById('sp-carousel-tpl-select');

  if (tplGrid && tplSelect) {
    tplGrid.addEventListener('click', function (e) {
      var card = e.target.closest('.sp-tpl-card');
      if (! card) return;
      tplGrid.querySelectorAll('.sp-tpl-card').forEach(function (c) { c.classList.remove('is-selected'); });
      card.classList.add('is-selected');
      tplSelect.value = card.dataset.tpl;
    });
    tplSelect.addEventListener('change', function () {
      tplGrid.querySelectorAll('.sp-tpl-card').forEach(function (c) {
        c.classList.toggle('is-selected', c.dataset.tpl === tplSelect.value);
      });
    });
  }

  /* ── Single Image generate ──────────────────────────────────── */
  var singleBtn    = document.getElementById('sp-single-generate-btn');
  var singleStatus = document.getElementById('sp-single-status');
  var singleAssistBtn = document.getElementById('sp-single-assist-btn');
  var singleAssistStatus = document.getElementById('sp-single-assist-status');
  var singleSourceContext = document.getElementById('sp-single-source-context');

  if (singleAssistBtn) {
    singleAssistBtn.addEventListener('click', function () {
      var sourceId = (document.getElementById('sp-single-source') || {}).value || '';
      var tone     = (document.getElementById('sp-single-tone') || {}).value || '';
      var goal     = (document.getElementById('sp-single-goal') || {}).value || '';
      var audience = (document.getElementById('sp-single-audience') || {}).value || '';

      if (! sourceId) { alert('Please select a news source.'); return; }

      singleAssistBtn.disabled = true;
      if (singleAssistStatus) singleAssistStatus.textContent = 'Reading latest articles and planning...';
      if (singleSourceContext) singleSourceContext.textContent = '';

      fetch(actionUrl, {
        method: 'POST',
        body: new URLSearchParams({
          csrf_token:         csrfToken,
          XID:                csrfToken,
          single_assist_mode: '1',
          source_id:          sourceId,
          tone:               tone,
          goal:               goal,
          audience:           audience,
        }),
      })
        .then(function (r) { return r.json(); })
        .then(function (json) {
          singleAssistBtn.disabled = false;
          if (! json.success) {
            if (singleAssistStatus) singleAssistStatus.textContent = 'Error: ' + (json.message || 'unknown');
            return;
          }

          var result = json.result || {};
          var topic = document.getElementById('sp-single-topic');
          var caption = document.getElementById('sp-single-caption-prompt');
          var image = document.getElementById('sp-single-image-hint');

          if (topic && result.topic) topic.value = result.topic;
          if (caption && result.caption_prompt) caption.value = result.caption_prompt;
          if (image && result.image_prompt) image.value = result.image_prompt;

          if (singleAssistStatus) singleAssistStatus.textContent = 'Suggestions added. Edit anything before generating.';
          if (singleSourceContext && result.source_context) {
            singleSourceContext.textContent = 'Based on: ' + result.source_context;
          }
        })
        .catch(function (err) {
          singleAssistBtn.disabled = false;
          if (singleAssistStatus) singleAssistStatus.textContent = 'Request failed: ' + err.message;
        });
    });
  }

  if (singleBtn) {
    singleBtn.addEventListener('click', function () {
      var topic        = (document.getElementById('sp-single-topic') || {}).value || '';
      var captionHint  = (document.getElementById('sp-single-caption-prompt') || {}).value || '';
      var imageHint    = (document.getElementById('sp-single-image-hint') || {}).value || '';
      var imageMode    = 'combined';
      document.querySelectorAll('input[name="sp-single-image-mode"]').forEach(function (radio) {
        if (radio.checked) imageMode = radio.value;
      });

      if (! topic.trim()) { alert('Please enter a topic.'); return; }

      singleBtn.disabled = true;
      if (singleStatus) singleStatus.textContent = 'Generating — this takes 20–40 s…';

      fetch(actionUrl, {
        method: 'POST',
        body: new URLSearchParams({
          csrf_token:        csrfToken,
          XID:               csrfToken,
          single_image_mode: '1',
          instagram_source:  '1',
          topic:             topic,
          caption_prompt:    captionHint,
          image_prompt_hint: imageHint,
          image_prompt_mode: imageMode,
        }),
      })
        .then(function (r) { return r.json(); })
        .then(function (json) {
          singleBtn.disabled = false;
          if (! json.success) {
            if (singleStatus) singleStatus.textContent = 'Error: ' + (json.message || 'unknown');
            return;
          }
          if (singleStatus) singleStatus.textContent = '';
          var id = json.result && json.result.id;
          if (id) {
            window.location.href = historyPat.replace('__ID__', id);
          }
        })
        .catch(function (err) {
          singleBtn.disabled = false;
          if (singleStatus) singleStatus.textContent = 'Request failed: ' + err.message;
        });
    });
  }

  /* ── News Post ──────────────────────────────────────────────── */
  (function () {
    var sourceSelect   = document.getElementById('sp-news-source');
    var loadBtn        = document.getElementById('sp-news-load-btn');
    var loadStatus     = document.getElementById('sp-news-load-status');
    var articlesWrap   = document.getElementById('sp-news-articles-wrap');
    var articleGrid    = document.getElementById('sp-news-article-grid');
    var formWrap       = document.getElementById('sp-news-form-wrap');
    var selectedPreview = document.getElementById('sp-news-selected-preview');
    var generateBtn    = document.getElementById('sp-news-generate-btn');
    var newsStatus     = document.getElementById('sp-news-status');
    var imgHintWrap    = document.getElementById('sp-news-image-hint-wrap');
    var imgArticleWarn = document.getElementById('sp-news-img-article-warn');
    var selectedArticle = null;

    if (! sourceSelect) { return; }

    // Enable load button when a source is selected
    sourceSelect.addEventListener('change', function () {
      if (loadBtn) loadBtn.disabled = sourceSelect.value === '';
      // Reset state
      if (articlesWrap) articlesWrap.style.display = 'none';
      if (formWrap) formWrap.style.display = 'none';
      selectedArticle = null;
    });

    // Load articles
    if (loadBtn) {
      loadBtn.addEventListener('click', function () {
        var sourceId = sourceSelect.value;
        if (! sourceId) return;

        loadBtn.disabled = true;
        if (loadStatus) loadStatus.textContent = 'Fetching articles…';

        fetch(actionUrl, {
          method: 'POST',
          body: new URLSearchParams({
            csrf_token: csrfToken,
            XID:        csrfToken,
            fetch_news: '1',
            source_id:  sourceId,
          }),
        })
          .then(function (r) { return r.json(); })
          .then(function (json) {
            loadBtn.disabled = false;
            if (! json.success) {
              if (loadStatus) loadStatus.textContent = 'Error: ' + (json.message || 'unknown');
              return;
            }
            if (loadStatus) loadStatus.textContent = '';
            renderArticles(json.articles || []);
          })
          .catch(function (err) {
            loadBtn.disabled = false;
            if (loadStatus) loadStatus.textContent = 'Failed: ' + err.message;
          });
      });
    }

    function renderArticles(articles) {
      renderArticleCards(articles, articleGrid, 'No articles found in this feed.');

      if (articlesWrap) articlesWrap.style.display = '';
      if (formWrap) formWrap.style.display = 'none';
    }

    function renderArticleCards(articles, grid, emptyMessage) {
      if (! grid) return;
      grid.innerHTML = '';

      if (articles.length === 0) {
        grid.innerHTML = '<p class="sp-muted">' + esc(emptyMessage) + '</p>';
        return;
      }

      articles.forEach(function (a, idx) {
        var card = document.createElement('div');
        card.className = 'sp-article-card';
        card.dataset.idx = idx;

        var thumb = a.image_url
          ? '<img src="' + esc(a.image_url) + '" alt="" loading="lazy" class="sp-article-thumb">'
          : '<div class="sp-article-no-thumb"></div>';

        var date = '';
        if (a.published_at) {
          try { date = new Date(a.published_at).toLocaleDateString(); } catch (e) {}
        }

        card.innerHTML =
          '<div class="sp-article-card-inner">' +
            thumb +
            '<div class="sp-article-meta">' +
              '<div class="sp-article-title">' + esc(a.title) + '</div>' +
              (a.excerpt ? '<div class="sp-article-excerpt">' + esc(a.excerpt.substring(0, 160)) + (a.excerpt.length > 160 ? '…' : '') + '</div>' : '') +
              '<div class="sp-article-footer">' +
                (a.source_name ? '<span class="sp-hot-source">' + esc(a.source_name) + '</span>' : '') +
                (date ? '<span class="sp-article-date sp-muted">' + esc(date) + '</span>' : '') +
                (a.hot_score ? '<span class="sp-hot-score">' + esc(Math.round(a.hot_score)) + '</span>' : '') +
                (! a.image_url ? '<span class="sp-no-image-tag">No image</span>' : '') +
              '</div>' +
              (a.score_reasons && a.score_reasons.length ? '<div class="sp-hot-reasons">' + a.score_reasons.slice(0, 3).map(esc).join(' · ') + '</div>' : '') +
            '</div>' +
          '</div>';

        card.addEventListener('click', function () {
          selectArticle(a, card);
        });

        grid.appendChild(card);
      });
    }

    function selectArticle(a, card) {
      selectedArticle = a;

      // Highlight card
      document.querySelectorAll('.sp-article-card').forEach(function (c) {
        c.classList.remove('is-selected');
      });
      card.classList.add('is-selected');

      // Populate preview
      if (selectedPreview) {
        var thumb = a.image_url
          ? '<img src="' + esc(a.image_url) + '" alt="" class="sp-selected-thumb">'
          : '';
        selectedPreview.innerHTML =
          '<div class="sp-selected-inner">' +
            (thumb ? '<div>' + thumb + '</div>' : '') +
            '<div>' +
              '<div class="sp-selected-title">' + esc(a.title) + '</div>' +
              (a.excerpt ? '<div class="sp-selected-excerpt sp-muted">' + esc(a.excerpt.substring(0, 300)) + '…</div>' : '') +
              (a.url ? '<a href="' + esc(a.url) + '" target="_blank" class="sp-muted" style="font-size:12px;">View article →</a>' : '') +
            '</div>' +
          '</div>';
      }

      // Default image choice based on whether article has an image
      var radioArticle = document.getElementById('sp-news-img-article');
      var radioNone    = document.getElementById('sp-news-img-none');
      if (a.image_url) {
        if (radioArticle) radioArticle.disabled = false;
        if (radioArticle) radioArticle.checked = true;
        if (imgArticleWarn) imgArticleWarn.style.display = 'none';
      } else {
        if (radioNone) radioNone.checked = true;
        if (radioArticle) radioArticle.disabled = true;
        if (imgArticleWarn) imgArticleWarn.style.display = '';
      }

      if (formWrap) formWrap.style.display = '';
      formWrap && formWrap.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    // Show/hide image hint textarea based on radio choice
    document.querySelectorAll('input[name="sp-news-image-choice"]').forEach(function (radio) {
      radio.addEventListener('change', function () {
        if (imgHintWrap) {
          imgHintWrap.style.display = radio.value === 'generate' && radio.checked ? '' : 'none';
        }
      });
    });

    // Generate
    if (generateBtn) {
      generateBtn.addEventListener('click', function () {
        if (! selectedArticle) { alert('Please select a news article first.'); return; }

        var captionHint  = (document.getElementById('sp-news-caption-hint') || {}).value || '';
        var imageHint    = (document.getElementById('sp-news-image-hint') || {}).value || '';
        var imageChoice  = 'none';
        document.querySelectorAll('input[name="sp-news-image-choice"]').forEach(function (r) {
          if (r.checked) imageChoice = r.value;
        });
        // If article image chosen but none available, fall back to generate
        if (imageChoice === 'article' && ! selectedArticle.image_url) {
          imageChoice = 'generate';
        }

        generateBtn.disabled = true;
        if (newsStatus) newsStatus.textContent = 'Generating — this takes 20–40 s…';

        fetch(actionUrl, {
          method: 'POST',
          body: new URLSearchParams({
            csrf_token:       csrfToken,
            XID:              csrfToken,
            news_mode:        '1',
            article_title:    selectedArticle.title || '',
            article_url:      selectedArticle.url || '',
            article_excerpt:  selectedArticle.excerpt || '',
            article_image_url: selectedArticle.image_url || '',
            caption_hint:     captionHint,
            image_choice:     imageChoice,
            image_hint:       imageHint,
          }),
        })
          .then(function (r) { return r.json(); })
          .then(function (json) {
            generateBtn.disabled = false;
            if (! json.success) {
              if (newsStatus) newsStatus.textContent = 'Error: ' + (json.message || 'unknown');
              return;
            }
            if (newsStatus) newsStatus.textContent = '';
            var id = json.result && json.result.id;
            if (id) {
              window.location.href = historyPat.replace('__ID__', id);
            }
          })
          .catch(function (err) {
            generateBtn.disabled = false;
            if (newsStatus) newsStatus.textContent = 'Request failed: ' + err.message;
          });
      });
    }

    function esc(str) {
      return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
  }());

  /* ── Carousel generate ───────────────────────────────────── */
  var carouselBtn    = document.getElementById('sp-carousel-generate-btn');
  var carouselStatus = document.getElementById('sp-carousel-status');

  if (carouselBtn) {
    carouselBtn.addEventListener('click', function () {
      var topic         = (document.getElementById('sp-carousel-topic') || {}).value || '';
      var style         = (document.getElementById('sp-carousel-style') || {}).value || 'base';
      var template      = (tplSelect || {}).value || 'minimal-list';
      var audience      = (document.getElementById('sp-carousel-audience') || {}).value || '';
      var contentPrompt = (document.getElementById('sp-carousel-content-prompt') || {}).value || '';
      var designPrompt  = (document.getElementById('sp-carousel-design-prompt') || {}).value || '';
      var slidesEl      = document.getElementById('sp-carousel-slides');
      var slides        = slidesEl && slidesEl.value.trim() ? slidesEl.value.trim() : '';

      if (! topic.trim()) { alert('Please enter a topic.'); return; }

      carouselBtn.disabled = true;
      if (carouselStatus) carouselStatus.textContent = 'Generating carousel — this takes 20–40 s…';

      var params = {
        csrf_token:        csrfToken,
        XID:               csrfToken,
        carousel_mode:     '1',
        topic:             topic,
        style:             style,
        carousel_template: template,
        audience:          audience,
        content_prompt:    contentPrompt,
        design_prompt:     designPrompt,
      };
      if (slides !== '') { params.slides = slides; }

      fetch(actionUrl, {
        method: 'POST',
        body: new URLSearchParams(params),
      })
        .then(function (r) { return r.json(); })
        .then(function (json) {
          carouselBtn.disabled = false;
          if (! json.success) {
            if (carouselStatus) carouselStatus.textContent = 'Error: ' + (json.message || 'unknown');
            return;
          }
          window.location.href = carouselPat.replace('__ID__', json.result.id);
        })
        .catch(function (err) {
          carouselBtn.disabled = false;
          if (carouselStatus) carouselStatus.textContent = 'Request failed: ' + err.message;
        });
    });
  }
}());
</script>
