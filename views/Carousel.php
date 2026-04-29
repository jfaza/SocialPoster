<?php
ee()->load->helper('form');
$h    = fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$mode = $mode ?? 'generate';
$rows = $rows ?? [];
$post = $post ?? [];

$styleOptions = [
    'base'      => 'Base — practical and useful',
    'viral'     => 'Viral — bold hooks, shareability',
    'technical' => 'Technical — deep dives for builders',
];
?>
<div class="sp-wrap">
  <p class="sp-toolbar">
    <a class="button button--default" href="<?= $h($instagram_url) ?>">Instagram Settings</a>
    <a class="button button--default" href="<?= $h($publish_url) ?>">Publish</a>
    <?php if ($mode === 'edit'): ?>
      <a class="button button--default" href="<?= $h($index_url) ?>">All Carousels</a>
    <?php endif; ?>
  </p>

  <?php /* ============================================================
     EDIT MODE — view + edit a specific carousel
  ============================================================ */ ?>
  <?php if ($mode === 'edit' && $post): ?>
    <?php
      $slides      = $post['slides'] ?? [];
      $previewUrl  = $post['preview_url'] ?? '';
      $tpl         = $post['carousel_template'] ?? 'minimal-list';
    ?>

    <div class="sp-carousel-layout">

      <!-- LEFT: live iframe preview -->
      <div class="sp-carousel-preview-col">
        <div class="sp-card sp-preview-card">
          <div class="sp-preview-header">
            <strong>Live Preview</strong>
            <span class="sp-muted">&nbsp;· <?= $h($tpl) ?> · <?= $h($post['style'] ?? '') ?></span>
            <a href="<?= $h($previewUrl) ?>" target="_blank" class="sp-preview-open">Open full size &rarr;</a>
          </div>
          <div class="sp-iframe-wrap">
            <iframe id="sp-preview-iframe"
                    src="<?= $h($previewUrl) ?>"
                    class="sp-preview-iframe"
                    scrolling="yes"
                    allowtransparency="true">
            </iframe>
          </div>

          <div class="sp-actions sp-preview-actions">
            <button type="button" id="sp-download-btn" class="button button--default">
              Download Slides (PNG)
            </button>
            <span id="sp-download-status" class="sp-muted"></span>
          </div>
        </div>
      </div>

      <!-- RIGHT: editor -->
      <div class="sp-carousel-editor-col">

        <!-- Caption + hashtags -->
        <?= form_open($save_url, ['id' => 'sp-carousel-edit-form']) ?>
          <input type="hidden" name="save_carousel" value="1">
          <input type="hidden" id="sp-slides-json" name="slides_json" value="">

          <div class="sp-card">
            <h2><?= $h($post['topic'] ?? '') ?></h2>

            <div class="sp-field">
              <label for="sp-caption">Caption</label>
              <textarea id="sp-caption" name="caption" rows="4"><?= $h($post['caption'] ?? '') ?></textarea>
            </div>

            <div class="sp-field">
              <label for="sp-hashtags">Hashtags</label>
              <input type="text" id="sp-hashtags" name="hashtags"
                     value="<?= $h(implode(' ', $post['hashtags_list'] ?? [])) ?>">
              <p class="sp-muted">Space-separated, with or without #</p>
            </div>
          </div>

          <!-- Slides editor -->
          <div id="sp-slides-editor">
            <?php foreach ($slides as $i => $slide): ?>
              <?php
                $slideType = $slide['type'] ?? 'content';
                $slideNum  = $slide['slide_num'] ?? ($i + 1 . ' / ' . count($slides));
              ?>
              <div class="sp-card sp-slide-card" data-index="<?= $i ?>">
                <div class="sp-slide-card-header">
                  <span class="sp-slide-badge"><?= $h(strtoupper($slideType)) ?></span>
                  <span class="sp-muted"><?= $h($slideNum) ?></span>
                  <button type="button" class="sp-regen-slide button button--small button--default"
                          data-index="<?= $i ?>">
                    Regenerate with AI
                  </button>
                </div>

                <?php foreach ($slide as $key => $value): ?>
                  <?php if (in_array($key, ['type', 'slide_num', 'number', 'items'], true)) continue; ?>
                  <?php if (is_array($value)) continue; ?>
                  <div class="sp-field">
                    <label><?= $h(ucwords(str_replace('_', ' ', $key))) ?></label>
                    <?php if (strlen((string) $value) > 80 || $key === 'subtitle' || $key === 'cta'): ?>
                      <textarea class="sp-slide-field" name="slide_<?= $i ?>_<?= $h($key) ?>"
                                data-idx="<?= $i ?>" data-field="<?= $h($key) ?>"
                                rows="3"><?= $h($value) ?></textarea>
                    <?php else: ?>
                      <input type="text" class="sp-slide-field"
                             name="slide_<?= $i ?>_<?= $h($key) ?>"
                             data-idx="<?= $i ?>" data-field="<?= $h($key) ?>"
                             value="<?= $h($value) ?>">
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>

                <?php if (! empty($slide['items']) && is_array($slide['items'])): ?>
                  <div class="sp-field">
                    <label>Items</label>
                    <?php foreach ($slide['items'] as $j => $item): ?>
                      <div class="sp-item-row">
                        <?php foreach ($item as $itemKey => $itemVal): ?>
                          <div class="sp-item-field">
                            <label class="sp-small-label"><?= $h(ucfirst($itemKey)) ?></label>
                            <input type="text" class="sp-item-input"
                                   data-idx="<?= $i ?>" data-item="<?= $j ?>" data-field="<?= $h($itemKey) ?>"
                                   value="<?= $h($itemVal) ?>">
                          </div>
                        <?php endforeach; ?>
                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>

          <div class="sp-actions">
            <button type="submit" class="button button--primary" id="sp-save-btn">Save Changes</button>
            <span id="sp-save-status" class="sp-muted"></span>
          </div>
        <?= form_close() ?>

        <!-- Danger zone -->
        <div class="sp-card">
          <h3>Delete</h3>
          <?= form_open($delete_url) ?>
            <input type="hidden" name="delete_id" value="<?= (int) ($post['id'] ?? 0) ?>">
            <button type="submit" class="button button--default"
                    onclick="return confirm('Delete this carousel?')">Delete Carousel</button>
          <?= form_close() ?>
        </div>

      </div><!-- /.sp-carousel-editor-col -->
    </div><!-- /.sp-carousel-layout -->

  <?php endif; /* mode === edit */ ?>


  <?php /* ============================================================
     GENERATE MODE — new carousel + recent list
  ============================================================ */ ?>
  <?php if ($mode === 'generate'): ?>

  <div class="sp-card" id="sp-carousel-generator">
    <h2>Generate Instagram Carousel</h2>
    <p class="sp-muted">
      Pick a visual template, enter a topic, and OpenAI generates structured 6-slide content.
      The carousel renders live in the editor so you can tweak every slide before downloading or publishing.
    </p>

    <div class="sp-field">
      <label for="sp-carousel-topic">Topic</label>
      <input type="text" id="sp-carousel-topic"
             placeholder="e.g. AI tools for developers, React performance tips, Git workflow"
             autocomplete="off">
      <p class="sp-muted">Be specific — this drives the slide content and structure.</p>
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
        <label for="sp-carousel-template-select">Visual Template</label>
        <select id="sp-carousel-template-select">
          <?php foreach ($templates as $tpl): ?>
            <option value="<?= $h($tpl['folder']) ?>">
              <?= $h($tpl['name'] ?? $tpl['folder']) ?><?= $tpl['description'] ? ' — ' . $h($tpl['description']) : '' ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <!-- Template picker cards -->
    <?php if ($templates): ?>
    <div class="sp-field">
      <label>Template</label>
      <div class="sp-template-grid" id="sp-template-grid">
        <?php foreach ($templates as $idx => $tpl): ?>
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
      <button type="button" id="sp-carousel-generate-btn" class="button button--primary">
        Generate Carousel
      </button>
      <span id="sp-carousel-status" class="sp-muted"></span>
    </div>
  </div>

  <!-- Recent carousels -->
  <?php if ($rows): ?>
  <div class="sp-card">
    <h2>Recent Carousels</h2>
    <table class="sp-table">
      <thead>
        <tr>
          <th>Topic</th><th>Template</th><th>Style</th><th>Date</th><th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $row): ?>
          <?php $item_url = str_replace('__ID__', (int) ($row['id'] ?? 0), $item_url_pattern) ?>
          <tr>
            <td><a href="<?= $h($item_url) ?>"><?= $h($row['topic'] ?? '') ?></a></td>
            <td><?= $h($row['carousel_template'] ?? '') ?></td>
            <td><?= $h($row['style'] ?? '') ?></td>
            <td><?= $row['created_at'] ? date('M j, Y', (int) $row['created_at']) : '—' ?></td>
            <td>
              <?= form_open($index_url) ?>
                <input type="hidden" name="delete_id" value="<?= (int) ($row['id'] ?? 0) ?>">
                <button type="submit" class="button button--small button--default"
                        onclick="return confirm('Delete this carousel?')">Delete</button>
              <?= form_close() ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>

  <?php endif; /* mode === generate */ ?>
</div><!-- /.sp-wrap -->

<?php /* ============================================================
   Styles
============================================================ */ ?>
<style>
/* Layout */
.sp-carousel-layout {
  display: grid;
  grid-template-columns: 420px 1fr;
  gap: 20px;
  align-items: start;
}
@media (max-width: 1100px) {
  .sp-carousel-layout { grid-template-columns: 1fr; }
}

/* Preview panel */
.sp-preview-card { padding: 0; overflow: hidden; }
.sp-preview-header {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 16px;
  border-bottom: 1px solid #eee;
  font-size: 13px;
}
.sp-preview-open { margin-left: auto; font-size: 12px; }
.sp-iframe-wrap {
  width: 420px;
  height: 420px;
  overflow: hidden;
  position: relative;
}
.sp-preview-iframe {
  width: 1080px;
  height: 1080px;
  border: none;
  transform-origin: top left;
  transform: scale(0.389); /* 420/1080 */
  pointer-events: none;
}
.sp-preview-actions { padding: 12px 16px; border-top: 1px solid #eee; }

/* Slide cards */
.sp-slide-card { margin-bottom: 12px; }
.sp-slide-card-header {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 14px;
}
.sp-slide-badge {
  background: #1e1e1e;
  color: #ccc;
  font-size: 10px;
  font-weight: 700;
  letter-spacing: .06em;
  padding: 3px 8px;
  border-radius: 4px;
}
.sp-regen-slide { margin-left: auto; }

/* Item rows */
.sp-item-row {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 8px;
  margin-bottom: 8px;
  padding: 10px;
  background: #f8f8f6;
  border-radius: 6px;
}
.sp-small-label { font-size: 11px; color: #888; display: block; margin-bottom: 3px; }
.sp-item-input { width: 100%; font-size: 12px; }

/* Template picker */
.sp-template-grid { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 8px; }
.sp-tpl-card {
  width: 110px;
  cursor: pointer;
  border: 2px solid #ddd;
  border-radius: 6px;
  overflow: hidden;
  text-align: center;
  transition: border-color .15s;
}
.sp-tpl-card.is-selected { border-color: #0072ff; }
.sp-tpl-card img { width: 100%; display: block; }
.sp-tpl-no-preview {
  width: 100%;
  height: 70px;
  background: #1e1e1e;
  color: #aaa;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 10px;
  padding: 4px;
  box-sizing: border-box;
}
.sp-tpl-label { display: block; font-size: 10px; padding: 4px 2px; word-break: break-all; }

/* Table */
.sp-table { width: 100%; border-collapse: collapse; }
.sp-table th, .sp-table td { padding: 8px 10px; text-align: left; border-bottom: 1px solid #eee; }
.sp-table th { font-weight: 600; }
</style>

<?php /* ============================================================
   JavaScript
============================================================ */ ?>
<script>
(function () {
  /* ── Template card picker ──────────────────────────────── */
  var grid   = document.getElementById('sp-template-grid');
  var tplSel = document.getElementById('sp-carousel-template-select');

  if (grid && tplSel) {
    grid.addEventListener('click', function (e) {
      var card = e.target.closest('.sp-tpl-card');
      if (! card) return;
      grid.querySelectorAll('.sp-tpl-card').forEach(function (c) { c.classList.remove('is-selected'); });
      card.classList.add('is-selected');
      tplSel.value = card.dataset.tpl;
    });
    tplSel.addEventListener('change', function () {
      grid.querySelectorAll('.sp-tpl-card').forEach(function (c) {
        c.classList.toggle('is-selected', c.dataset.tpl === tplSel.value);
      });
    });
  }

  /* ── Generate new carousel ─────────────────────────────── */
  var genBtn    = document.getElementById('sp-carousel-generate-btn');
  var genStatus = document.getElementById('sp-carousel-status');
  var actionUrl = <?= json_encode($action_url ?? '') ?>;
  var csrfToken = <?= json_encode($csrf_token ?? '') ?>;
  var itemPat   = <?= json_encode($item_url_pattern ?? '') ?>;

  if (genBtn) {
    genBtn.addEventListener('click', function () {
      var topic    = (document.getElementById('sp-carousel-topic') || {}).value || '';
      var style    = (document.getElementById('sp-carousel-style') || {}).value || 'base';
      var template = (tplSel || {}).value || 'minimal-list';

      if (! topic.trim()) { alert('Please enter a topic.'); return; }

      genBtn.disabled = true;
      if (genStatus) genStatus.textContent = 'Generating carousel — this takes 20–40 seconds…';

      fetch(actionUrl, {
        method: 'POST',
        body: new URLSearchParams({
          csrf_token:        csrfToken,
          XID:               csrfToken,
          carousel_mode:     '1',
          topic:             topic,
          style:             style,
          carousel_template: template,
        }),
      })
        .then(function (r) { return r.json(); })
        .then(function (json) {
          genBtn.disabled = false;
          if (! json.success) {
            if (genStatus) genStatus.textContent = 'Error: ' + (json.message || 'unknown');
            return;
          }
          // Redirect to the edit page
          window.location.href = itemPat.replace('__ID__', json.result.id);
        })
        .catch(function (err) {
          genBtn.disabled = false;
          if (genStatus) genStatus.textContent = 'Request failed: ' + err.message;
        });
    });
  }

  /* ── Live preview refresh on field edit ────────────────── */
  var slides     = [];
  var iframe     = document.getElementById('sp-preview-iframe');
  var slidesJson = document.getElementById('sp-slides-json');

  // Boot: read initial slides from the rendered fields
  function readSlidesFromDom() {
    var cards = document.querySelectorAll('.sp-slide-card');
    var result = [];

    cards.forEach(function (card) {
      var idx    = parseInt(card.dataset.index, 10);
      var obj    = {};

      card.querySelectorAll('.sp-slide-field').forEach(function (el) {
        obj[el.dataset.field] = el.value;
      });

      // Preserve non-editable keys from initial data
      var initialSlide = initialSlides[idx] || {};
      Object.keys(initialSlide).forEach(function (k) {
        if (! (k in obj)) obj[k] = initialSlide[k];
      });

      // Items
      var items = [];
      var itemRows = card.querySelectorAll('.sp-item-row');
      itemRows.forEach(function (row) {
        var item = {};
        row.querySelectorAll('.sp-item-input').forEach(function (el) {
          item[el.dataset.field] = el.value;
        });
        items.push(item);
      });
      if (itemRows.length > 0) obj['items'] = items;

      result[idx] = obj;
    });

    return result;
  }

  var initialSlides = <?= json_encode(array_values($post['slides'] ?? [])) ?>;

  function updatePreview() {
    slides = readSlidesFromDom();
    if (slidesJson) slidesJson.value = JSON.stringify(slides);

    if (! iframe) return;

    // Get current src, update the data URL with fresh JSON blob
    var src = iframe.src || '';
    if (! src) return;

    // Write updated JSON to a blob and update src
    var currentData = JSON.parse(<?= json_encode(json_encode($post['data'] ?? [])) ?>);
    currentData.slides = slides;

    var caption = (document.getElementById('sp-caption') || {}).value || '';
    var hashtags = (document.getElementById('sp-hashtags') || {}).value || '';
    currentData.caption = caption;
    currentData.hashtags = hashtags.split(/[\s,]+/).filter(Boolean).map(function(t) {
      return t.startsWith('#') ? t : '#' + t;
    });

    var blob = new Blob([JSON.stringify(currentData)], { type: 'application/json' });
    var url  = URL.createObjectURL(blob);

    var iframeSrc = iframe.src.split('?')[0];
    iframe.src = iframeSrc + '?data=' + encodeURIComponent(url);
  }

  // Debounce helper
  function debounce(fn, delay) {
    var t;
    return function () { clearTimeout(t); t = setTimeout(fn, delay); };
  }

  var debouncedUpdate = debounce(updatePreview, 600);

  // Watch all editable fields
  document.querySelectorAll('.sp-slide-field, .sp-item-input, #sp-caption, #sp-hashtags').forEach(function (el) {
    el.addEventListener('input', debouncedUpdate);
  });

  // Set initial slides JSON hidden field
  if (slidesJson) slidesJson.value = JSON.stringify(initialSlides);

  /* ── Per-slide AI regeneration ─────────────────────────── */
  document.querySelectorAll('.sp-regen-slide').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var idx      = parseInt(btn.dataset.index, 10);
      var card     = btn.closest('.sp-slide-card');
      var topic    = <?= json_encode($post['topic'] ?? '') ?>;
      var template = <?= json_encode($post['carousel_template'] ?? 'minimal-list') ?>;
      var style    = <?= json_encode($post['style'] ?? 'base') ?>;
      var slideType = (initialSlides[idx] || {}).type || 'content';

      btn.disabled    = true;
      btn.textContent = 'Regenerating…';

      fetch(actionUrl, {
        method: 'POST',
        body: new URLSearchParams({
          csrf_token:       csrfToken,
          XID:              csrfToken,
          carousel_regen:   '1',
          carousel_id:      <?= (int) ($post['id'] ?? 0) ?>,
          slide_index:      idx,
          slide_type:       slideType,
          topic:            topic,
          carousel_template: template,
          style:            style,
        }),
      })
        .then(function (r) { return r.json(); })
        .then(function (json) {
          btn.disabled    = false;
          btn.textContent = 'Regenerate with AI';

          if (! json.success || ! json.result) return;

          var newSlide = json.result;
          Object.keys(newSlide).forEach(function (field) {
            var input = card.querySelector('[data-field="' + field + '"]');
            if (input) input.value = newSlide[field];

            if (field === 'items' && Array.isArray(newSlide[field])) {
              newSlide[field].forEach(function (item, j) {
                Object.keys(item).forEach(function (itemField) {
                  var inp = card.querySelector('[data-item="' + j + '"][data-field="' + itemField + '"]');
                  if (inp) inp.value = item[itemField];
                });
              });
            }
          });

          debouncedUpdate();
        })
        .catch(function () {
          btn.disabled    = false;
          btn.textContent = 'Regenerate with AI';
        });
    });
  });

  /* ── Download slides as PNG ────────────────────────────── */
  var dlBtn    = document.getElementById('sp-download-btn');
  var dlStatus = document.getElementById('sp-download-status');

  if (dlBtn) {
    dlBtn.addEventListener('click', function () {
      var previewUrl = <?= json_encode($post['preview_url'] ?? '') ?>;
      if (! previewUrl) return;

      // Open the full-size preview in a new tab — user can use browser print/save
      dlStatus.textContent = 'Opening full-size preview to save slides…';
      window.open(previewUrl, '_blank');
      setTimeout(function () { dlStatus.textContent = ''; }, 3000);
    });
  }

}());
</script>
