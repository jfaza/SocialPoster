<?php
ee()->load->helper('form');
$h    = fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$mode = $mode ?? 'generate';
$rows = $rows ?? [];
$post = $post ?? [];
$news_sources = $news_sources ?? [];
$news_sources_url = $news_sources_url ?? '';
$templates_url = $templates_url ?? '';

$styleOptions = [
    'base'      => 'Base — practical and useful',
    'viral'     => 'Viral — bold hooks, shareability',
    'technical' => 'Technical — deep dives for builders',
];

$assistToneOptions = [
    'practical, polished, conversational' => 'Practical + polished',
    'bold, punchy, contrarian' => 'Bold + punchy',
    'warm, reflective, first-person' => 'Warm + reflective',
    'technical, precise, builder-focused' => 'Technical + precise',
];

$assistGoalOptions = [
    'educate and invite engagement' => 'Educate + engage',
    'explain the business impact and invite a DM' => 'Business impact + DM',
    'teach one practical takeaway and ask a question' => 'Practical takeaway',
    'position Javid Fazaeli as a helpful technical partner' => 'Position expertise',
];

$designDensityOptions = [
    'airy and minimal' => 'Airy + minimal',
    'balanced and readable' => 'Balanced + readable',
    'dense and information-rich' => 'Dense + information-rich',
];

$designMoodOptions = [
    'calm and polished' => 'Calm + polished',
    'bold and high-contrast' => 'Bold + high-contrast',
    'technical and precise' => 'Technical + precise',
    'editorial and premium' => 'Editorial + premium',
];

$designLayoutOptions = [
    'clean centered composition' => 'Clean centered composition',
    'modular card layout' => 'Modular card layout',
    'editorial split layout' => 'Editorial split layout',
    'structured grid system' => 'Structured grid system',
];

$defaultAudience = 'developers, founders, and AI-curious business owners';
$defaultCarouselTemplate = ! empty($templates[0]['folder']) ? (string) $templates[0]['folder'] : 'minimal-list';
?>
<div class="sp-wrap">
  <header class="sp-channel-head">
    <div>
      <p class="sp-channel-kicker">Instagram</p>
      <h1><?= $mode === 'edit' ? 'Carousel Editor' : 'Carousels' ?></h1>
      <p class="sp-muted"><?= $mode === 'edit' ? 'Refine slide structure, caption, and visuals for a saved carousel.' : 'Generate and manage Instagram carousel drafts from one place.' ?></p>
    </div>
    <nav class="sp-channel-nav">
      <a class="button button--default" href="<?= $h($instagram_create_url) ?>">Create</a>
      <a class="button button--default" href="<?= $h($instagram_history_url) ?>">History</a>
      <a class="button button--default" href="<?= $h($publish_url) ?>">Publish</a>
      <a class="button button--default" href="<?= $h($instagram_url) ?>">Settings</a>
    </nav>
  </header>

  <?php /* ============================================================
     EDIT MODE — view + edit a specific carousel
  ============================================================ */ ?>
  <?php if ($mode === 'edit' && $post): ?>
    <?php
      $slides      = $post['slides'] ?? [];
      $previewUrl  = $post['preview_url'] ?? '';
      $tpl         = $post['carousel_template'] ?? 'minimal-list';
      $slideCount  = max(3, min(12, count($slides) ?: 6));
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
            <h2>Edit Carousel</h2>

            <div class="sp-carousel-assist-panel">
              <h3>Optional AI Assist</h3>
              <p class="sp-muted">You can write the topic and instructions yourself, or use one news source to suggest a timely angle. News is optional for the overall workflow, but required when using this assist button.</p>

              <?php if (empty($news_sources)): ?>
                <div class="sp-single-assist-empty">
                  <p class="sp-muted">No active news sources are configured yet.</p>
                  <a class="button button--default" href="<?= $h($news_sources_url) ?>">Manage News Sources</a>
                </div>
              <?php else: ?>
                <div class="sp-mini-grid sp-mini-grid--3">
                  <div class="sp-field">
                    <label for="sp-carousel-edit-source">News Source</label>
                    <select id="sp-carousel-edit-source">
                      <option value="">No source selected</option>
                      <?php foreach ($news_sources as $src): ?>
                        <option value="<?= (int) $src['id'] ?>"><?= $h($src['name']) ?><?= ! empty($src['category']) ? ' - ' . $h($src['category']) : '' ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="sp-field">
                    <label for="sp-carousel-edit-audience">Audience</label>
                    <input type="text" id="sp-carousel-edit-audience" value="<?= $h($defaultAudience) ?>" placeholder="Who this carousel is for">
                  </div>
                  <div class="sp-field">
                    <label for="sp-carousel-edit-tone">Tone</label>
                    <input type="text" id="sp-carousel-edit-tone" value="practical, polished, conversational" placeholder="Use a preset or write your own tone">
                    <select id="sp-carousel-edit-tone-preset" class="sp-combo-select">
                      <option value="">Choose a tone preset</option>
                      <?php foreach ($assistToneOptions as $val => $label): ?>
                        <option value="<?= $h($val) ?>"><?= $h($label) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
                <div class="sp-mini-grid">
                  <div class="sp-field">
                    <label for="sp-carousel-edit-goal">Goal</label>
                    <input type="text" id="sp-carousel-edit-goal" value="educate and invite engagement" placeholder="Use a preset or write your own goal">
                    <select id="sp-carousel-edit-goal-preset" class="sp-combo-select">
                      <option value="">Choose a goal preset</option>
                      <?php foreach ($assistGoalOptions as $val => $label): ?>
                        <option value="<?= $h($val) ?>"><?= $h($label) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="sp-field">
                    <label for="sp-carousel-edit-slides">Slides</label>
                    <input type="number" id="sp-carousel-edit-slides" min="3" max="12" value="<?= (int) $slideCount ?>">
                  </div>
                </div>
                <div class="sp-actions sp-single-assist-actions">
                  <button type="button" id="sp-carousel-edit-assist-btn" class="button button--default">Generate Topic + Instructions</button>
                  <span id="sp-carousel-edit-assist-status" class="sp-muted"></span>
                </div>
                <p id="sp-carousel-edit-source-context" class="sp-muted sp-single-source-context"></p>
              <?php endif; ?>
            </div>

            <div class="sp-field sp-generated-field">
              <label for="sp-carousel-edit-topic">Topic</label>
              <input type="text" id="sp-carousel-edit-topic" name="topic"
                     value="<?= $h($post['topic'] ?? '') ?>" autocomplete="off">
              <p class="sp-muted">This is the main driver. You can write the topic directly and skip AI Assist completely.</p>
            </div>

            <div class="sp-mini-grid">
              <div class="sp-field">
                <label for="sp-carousel-edit-style">Style</label>
                <select id="sp-carousel-edit-style">
                  <?php foreach ($styleOptions as $val => $label): ?>
                    <option value="<?= $h($val) ?>"<?= (($post['style'] ?? '') === $val) ? ' selected' : '' ?>><?= $h($label) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="sp-field">
                <label for="sp-carousel-edit-template-select">Visual Template</label>
                <select id="sp-carousel-edit-template-select">
                  <?php foreach ($templates as $tplOption): ?>
                    <option value="<?= $h($tplOption['folder']) ?>"<?= (($post['carousel_template'] ?? '') === ($tplOption['folder'] ?? '')) ? ' selected' : '' ?>>
                      <?= $h($tplOption['name'] ?? $tplOption['folder']) ?><?= ! empty($tplOption['description']) ? ' — ' . $h($tplOption['description']) : '' ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <div class="sp-field sp-generated-field">
              <label for="sp-carousel-edit-content-prompt">Content Instructions</label>
              <textarea id="sp-carousel-edit-content-prompt" rows="4" placeholder="Hook, slide arc, proof points, caption angle, CTA..."></textarea>
              <p class="sp-muted">Optional. Add your own direction here instead of relying on presets.</p>
            </div>

            <div class="sp-field sp-generated-field">
              <label for="sp-carousel-edit-design-prompt">Design Instructions</label>
              <textarea id="sp-carousel-edit-design-prompt" rows="3" placeholder="Visual system, layout density, accent treatment, imagery mood..."></textarea>
              <p class="sp-muted">Optional. Use this to steer density, layout, and visual mood beyond the chosen template.</p>
            </div>

            <div class="sp-mini-grid sp-mini-grid--3 sp-design-helper-grid">
          <div class="sp-field">
            <label for="sp-carousel-edit-design-density">Density</label>
            <select id="sp-carousel-edit-design-density">
              <option value="">No preference</option>
              <?php foreach ($designDensityOptions as $val => $label): ?>
                <option value="<?= $h($val) ?>"><?= $h($label) ?></option>
              <?php endforeach; ?>
            </select>
            <p class="sp-muted">How much information should fit on each slide. Example: airy hero slide vs compact teaching slide.</p>
          </div>
          <div class="sp-field">
            <label for="sp-carousel-edit-design-mood">Visual Mood</label>
            <select id="sp-carousel-edit-design-mood">
              <option value="">No preference</option>
              <?php foreach ($designMoodOptions as $val => $label): ?>
                <option value="<?= $h($val) ?>"><?= $h($label) ?></option>
              <?php endforeach; ?>
            </select>
            <p class="sp-muted">The overall feel. Example: calm SaaS polish, bold contrast, or technical editorial precision.</p>
          </div>
          <div class="sp-field">
            <label for="sp-carousel-edit-design-layout">Layout Character</label>
            <select id="sp-carousel-edit-design-layout">
              <option value="">No preference</option>
              <?php foreach ($designLayoutOptions as $val => $label): ?>
                <option value="<?= $h($val) ?>"><?= $h($label) ?></option>
              <?php endforeach; ?>
            </select>
            <p class="sp-muted">The structural feel of the slide. Example: centered hero, modular cards, or a more editorial split layout.</p>
          </div>
        </div>

            <div class="sp-actions sp-carousel-rewrite-actions">
              <button type="button" id="sp-carousel-rewrite-btn" class="button button--default">Regenerate This Carousel</button>
              <span id="sp-carousel-rewrite-status" class="sp-muted"></span>
            </div>

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
              <div class="sp-card sp-slide-card <?= $i === 0 ? 'is-open' : 'is-collapsed' ?>" data-index="<?= $i ?>">
                <div class="sp-slide-card-header">
                  <span class="sp-slide-badge"><?= $h(strtoupper($slideType)) ?></span>
                  <span class="sp-muted"><?= $h($slideNum) ?></span>
                  <button type="button" class="sp-slide-toggle button button--small button--default"
                          aria-expanded="<?= $i === 0 ? 'true' : 'false' ?>"
                          aria-controls="sp-slide-body-<?= $i ?>"
                          data-index="<?= $i ?>">
                    <?= $i === 0 ? 'Collapse' : 'Edit' ?>
                  </button>
                  <button type="button" class="sp-regen-slide button button--small button--default"
                          data-index="<?= $i ?>">
                    Regenerate with AI
                  </button>
                </div>

                <div class="sp-slide-mini-preview" data-preview-index="<?= $i ?>">
                  <div class="sp-slide-mini-top">
                    <span class="sp-slide-mini-type"><?= $h(strtoupper($slideType)) ?></span>
                    <span class="sp-slide-mini-count"><?= $h($slideNum) ?></span>
                  </div>
                  <div class="sp-slide-mini-title">
                    <?= $h($slide['title'] ?? $slide['heading'] ?? $slide['cta'] ?? 'Untitled slide') ?>
                  </div>
                  <div class="sp-slide-mini-subtitle">
                    <?= $h($slide['subtitle'] ?? $slide['description'] ?? $slide['body'] ?? '') ?>
                  </div>
                  <?php if (! empty($slide['items']) && is_array($slide['items'])): ?>
                    <div class="sp-slide-mini-items">
                      <?php foreach (array_slice($slide['items'], 0, 3) as $item): ?>
                        <span><?= $h($item['name'] ?? $item['title'] ?? $item['label'] ?? '') ?></span>
                      <?php endforeach; ?>
                    </div>
                  <?php endif; ?>
                </div>

                <div class="sp-slide-accordion-body" id="sp-slide-body-<?= $i ?>">
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
              </div>
            <?php endforeach; ?>
          </div>

          <div class="sp-actions sp-carousel-save-panel">
            <div>
              <strong>Ready to publish these edits?</strong>
              <p class="sp-muted">Save updates the caption, hashtags, and all slide field changes.</p>
            </div>
            <button type="submit" class="button button--primary" id="sp-save-btn">Save Changes</button>
            <span id="sp-save-status" class="sp-muted"></span>
          </div>
        <?= form_close() ?>

        <!-- Danger zone -->
        <div class="sp-card sp-carousel-delete-card">
          <div>
            <h3>Delete Carousel</h3>
            <p class="sp-muted">Remove this carousel and its preview data. This cannot be undone.</p>
          </div>
          <?= form_open($delete_url) ?>
            <input type="hidden" name="delete_id" value="<?= (int) ($post['id'] ?? 0) ?>">
            <button type="submit" class="button button--danger"
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
      Start with your own topic, then optionally use a visual template and extra direction. News assist is available if you want help finding a timely angle, but it is not required.
    </p>

    <div class="sp-carousel-assist-panel">
      <h3>Optional AI Assist</h3>
      <p class="sp-muted">If you want help, pick a news source and let AI suggest a timely topic and instructions. Otherwise skip this section and build the carousel from your own topic.</p>

      <?php if (empty($news_sources)): ?>
        <div class="sp-single-assist-empty">
          <p class="sp-muted">No active news sources are configured yet.</p>
          <a class="button button--default" href="<?= $h($news_sources_url) ?>">Manage News Sources</a>
        </div>
      <?php else: ?>
        <div class="sp-mini-grid sp-mini-grid--3">
          <div class="sp-field">
            <label for="sp-carousel-source">News Source</label>
            <select id="sp-carousel-source">
              <option value="">No source selected</option>
              <?php foreach ($news_sources as $src): ?>
                <option value="<?= (int) $src['id'] ?>"><?= $h($src['name']) ?><?= ! empty($src['category']) ? ' - ' . $h($src['category']) : '' ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="sp-field">
            <label for="sp-carousel-audience">Audience</label>
            <input type="text" id="sp-carousel-audience" value="<?= $h($defaultAudience) ?>" placeholder="Who this carousel is for">
          </div>
          <div class="sp-field">
            <label for="sp-carousel-tone">Tone</label>
            <input type="text" id="sp-carousel-tone" value="practical, polished, conversational" placeholder="Use a preset or write your own tone">
            <select id="sp-carousel-tone-preset" class="sp-combo-select">
              <option value="">Choose a tone preset</option>
              <?php foreach ($assistToneOptions as $val => $label): ?>
                <option value="<?= $h($val) ?>"><?= $h($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="sp-mini-grid">
          <div class="sp-field">
            <label for="sp-carousel-goal">Goal</label>
            <input type="text" id="sp-carousel-goal" value="educate and invite engagement" placeholder="Use a preset or write your own goal">
            <select id="sp-carousel-goal-preset" class="sp-combo-select">
              <option value="">Choose a goal preset</option>
              <?php foreach ($assistGoalOptions as $val => $label): ?>
                <option value="<?= $h($val) ?>"><?= $h($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="sp-field">
            <label for="sp-carousel-slides">Slides</label>
            <input type="number" id="sp-carousel-slides" min="3" max="12" value="6">
          </div>
        </div>

        <div class="sp-actions sp-single-assist-actions">
          <button type="button" id="sp-carousel-assist-btn" class="button button--default">Generate Topic + Instructions</button>
          <span id="sp-carousel-assist-status" class="sp-muted"></span>
        </div>
        <p id="sp-carousel-source-context" class="sp-muted sp-single-source-context"></p>
      <?php endif; ?>
    </div>

    <div class="sp-field sp-generated-field">
      <label for="sp-carousel-topic">Topic</label>
      <input type="text" id="sp-carousel-topic"
             placeholder="e.g. AI tools for developers, React performance tips, Git workflow"
             autocomplete="off">
      <p class="sp-muted">Be specific. This is the core input and you can use it without any news source at all.</p>
    </div>

    <section class="sp-summary sp-carousel-design-system">
      <h3>Design System</h3>
      <p class="sp-muted">Choose a reusable visual template or build the look from your own direction. Both paths use the same renderer underneath, but the controls stay much clearer.</p>

      <div class="sp-radio-group">
        <?php if ($templates): ?>
        <label class="sp-radio">
          <input type="radio" name="sp-carousel-visual-mode" value="template" checked>
          <span>Use Visual Template</span>
        </label>
        <?php endif; ?>
        <label class="sp-radio">
          <input type="radio" name="sp-carousel-visual-mode" value="custom"<?= empty($templates) ? ' checked' : '' ?>>
          <span>Build From Direction</span>
        </label>
      </div>

      <div class="sp-field">
        <label for="sp-carousel-style">Style</label>
        <select id="sp-carousel-style">
          <?php foreach ($styleOptions as $val => $label): ?>
            <option value="<?= $h($val) ?>"><?= $h($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <?php if ($templates): ?>
      <div id="sp-carousel-template-mode-panel">
        <input type="hidden" id="sp-carousel-template-select" value="<?= $h($defaultCarouselTemplate) ?>">
        <?php if ($templates_url !== ''): ?>
          <p class="sp-muted">Manage reusable visual templates from <a href="<?= $h($templates_url) ?>#tab=t-2">Carousel Templates</a>.</p>
        <?php endif; ?>
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

      <div id="sp-carousel-custom-mode-panel" class="<?= ! empty($templates) ? 'is-hidden' : '' ?>">
        <p class="sp-muted">Custom mode uses the base carousel renderer and your instructions instead of a saved visual template preset.</p>
        <div class="sp-field sp-generated-field">
          <label for="sp-carousel-design-prompt">Design Instructions</label>
          <textarea id="sp-carousel-design-prompt" rows="3" placeholder="Visual system, layout density, accent treatment, imagery mood..."></textarea>
          <p class="sp-muted">Describe the layout feel, density, spacing, accent treatment, color mood, and any “do / avoid” visual guidance.</p>
        </div>
        <div class="sp-mini-grid sp-mini-grid--3 sp-design-helper-grid">
          <div class="sp-field">
            <label for="sp-carousel-design-density">Density</label>
            <select id="sp-carousel-design-density">
              <option value="">No preference</option>
              <?php foreach ($designDensityOptions as $val => $label): ?>
                <option value="<?= $h($val) ?>"><?= $h($label) ?></option>
              <?php endforeach; ?>
            </select>
            <p class="sp-muted">How much information should fit on each slide. Example: airy hero slide vs compact teaching slide.</p>
          </div>
          <div class="sp-field">
            <label for="sp-carousel-design-mood">Visual Mood</label>
            <select id="sp-carousel-design-mood">
              <option value="">No preference</option>
              <?php foreach ($designMoodOptions as $val => $label): ?>
                <option value="<?= $h($val) ?>"><?= $h($label) ?></option>
              <?php endforeach; ?>
            </select>
            <p class="sp-muted">The overall feel. Example: calm SaaS polish, bold contrast, or technical editorial precision.</p>
          </div>
          <div class="sp-field">
            <label for="sp-carousel-design-layout">Layout Character</label>
            <select id="sp-carousel-design-layout">
              <option value="">No preference</option>
              <?php foreach ($designLayoutOptions as $val => $label): ?>
                <option value="<?= $h($val) ?>"><?= $h($label) ?></option>
              <?php endforeach; ?>
            </select>
            <p class="sp-muted">The structural feel of the slide. Example: centered hero, modular cards, or a more editorial split layout.</p>
          </div>
        </div>
      </div>
    </section>

    <div class="sp-field sp-generated-field">
      <label for="sp-carousel-content-prompt">Content Instructions</label>
      <textarea id="sp-carousel-content-prompt" rows="4" placeholder="Hook, slide arc, proof points, caption angle, CTA..."></textarea>
      <p class="sp-muted">Optional. Write your own content direction here instead of relying on AI Assist.</p>
    </div>

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
   JavaScript
============================================================ */ ?>
<script>
(function () {
  /* ── Template card picker ──────────────────────────────── */
  var grid   = document.getElementById('sp-template-grid');
  var tplSel = document.getElementById('sp-carousel-template-select');
  var visualModeInputs = document.querySelectorAll('input[name="sp-carousel-visual-mode"]');
  var templateModePanel = document.getElementById('sp-carousel-template-mode-panel');
  var customModePanel = document.getElementById('sp-carousel-custom-mode-panel');

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
  var carouselId = <?= (int) ($post['id'] ?? 0) ?>;
  var currentTemplate = <?= json_encode($post['carousel_template'] ?? 'minimal-list') ?>;
  var currentStyle = <?= json_encode($post['style'] ?? 'base') ?>;

  function fieldValue(id, fallback) {
    var el = document.getElementById(id);
    return el ? el.value : (fallback || '');
  }

  function setFieldValue(id, value) {
    var el = document.getElementById(id);
    if (el && value !== undefined && value !== null) el.value = value;
  }

  function wireComboInput(inputId, presetId) {
    var input = document.getElementById(inputId);
    var preset = document.getElementById(presetId);
    if (!input || !preset) return;

    preset.addEventListener('change', function () {
      if (!preset.value) return;
      input.value = preset.value;
      input.focus();
    });
  }

  function composedDesignPrompt(prefix) {
    var base = fieldValue(prefix + '-design-prompt', '').trim();
    var density = fieldValue(prefix + '-design-density', '').trim();
    var mood = fieldValue(prefix + '-design-mood', '').trim();
    var layout = fieldValue(prefix + '-design-layout', '').trim();
    var helperLines = [];

    if (density) helperLines.push('Density: ' + density);
    if (mood) helperLines.push('Visual mood: ' + mood);
    if (layout) helperLines.push('Layout character: ' + layout);

    if (!helperLines.length) {
      return base;
    }

    return [base, helperLines.join("\n")].filter(function (part) {
      return part && part.trim() !== '';
    }).join("\n\n");
  }

  function selectedTemplate() {
    if (currentVisualMode() === 'custom') {
      // Custom direction still uses the base renderer under the hood.
      return 'minimal-list';
    }

    return (tplSel || {}).value || currentTemplate || 'minimal-list';
  }

  function selectedStyle(prefix) {
    return fieldValue(prefix + '-style', currentStyle || 'base') || 'base';
  }

  function currentVisualMode() {
    var active = Array.prototype.find.call(visualModeInputs || [], function (input) {
      return input.checked;
    });

    return active ? active.value : 'template';
  }

  function syncVisualMode() {
    var mode = currentVisualMode();
    if (templateModePanel) {
      templateModePanel.classList.toggle('is-hidden', mode !== 'template');
    }
    if (customModePanel) {
      customModePanel.classList.toggle('is-hidden', mode !== 'custom');
    }
  }

  wireComboInput('sp-carousel-tone', 'sp-carousel-tone-preset');
  wireComboInput('sp-carousel-goal', 'sp-carousel-goal-preset');
  wireComboInput('sp-carousel-edit-tone', 'sp-carousel-edit-tone-preset');
  wireComboInput('sp-carousel-edit-goal', 'sp-carousel-edit-goal-preset');
  Array.prototype.forEach.call(visualModeInputs || [], function (input) {
    input.addEventListener('change', syncVisualMode);
  });
  syncVisualMode();

  function runCarouselAssist(config) {
    var btn = document.getElementById(config.buttonId);
    var status = document.getElementById(config.statusId);
    var sourceId = fieldValue(config.sourceId, '');
    var assistTemplate = config.templateId ? fieldValue(config.templateId, 'minimal-list') : currentTemplate;

    if (config.templateId === 'sp-carousel-template-select' && currentVisualMode() === 'custom') {
      assistTemplate = 'minimal-list';
    }

    if (! btn) return;
    if (! sourceId) {
      alert('News is optional overall, but AI Assist needs a news source to suggest a timely angle.');
      return;
    }

    btn.disabled = true;
    if (status) status.textContent = 'Reading source and planning carousel...';

    fetch(actionUrl, {
      method: 'POST',
      body: new URLSearchParams({
        csrf_token:        csrfToken,
        XID:               csrfToken,
        carousel_assist_mode: '1',
        source_id:         sourceId,
        tone:              fieldValue(config.toneId, 'practical, polished, conversational'),
        goal:              fieldValue(config.goalId, 'educate and invite engagement'),
        audience:          fieldValue(config.audienceId, 'developers, founders, and AI-curious business owners'),
        style:             config.styleId ? fieldValue(config.styleId, 'base') : currentStyle,
        carousel_template: assistTemplate,
        slides:            fieldValue(config.slidesId, '6'),
      }),
    })
      .then(function (r) { return r.json(); })
      .then(function (json) {
        btn.disabled = false;
        if (! json.success) {
          if (status) status.textContent = 'Error: ' + (json.message || 'unknown');
          return;
        }

        var result = json.result || {};
        setFieldValue(config.topicId, result.topic || '');
        setFieldValue(config.audienceId, result.audience || fieldValue(config.audienceId, ''));
        setFieldValue(config.contentId, result.content_prompt || '');
        setFieldValue(config.designId, result.design_prompt || '');

        var sourceContext = document.getElementById(config.contextId);
        if (sourceContext && result.source_context) {
          sourceContext.textContent = 'Based on: ' + result.source_context;
        }
        if (status) status.textContent = 'Ready to generate.';
      })
      .catch(function (err) {
        btn.disabled = false;
        if (status) status.textContent = 'Request failed: ' + err.message;
      });
  }

  var assistBtn = document.getElementById('sp-carousel-assist-btn');
  if (assistBtn) {
    assistBtn.addEventListener('click', function () {
      runCarouselAssist({
        buttonId: 'sp-carousel-assist-btn',
        statusId: 'sp-carousel-assist-status',
        sourceId: 'sp-carousel-source',
        toneId: 'sp-carousel-tone',
        goalId: 'sp-carousel-goal',
        audienceId: 'sp-carousel-audience',
        styleId: 'sp-carousel-style',
        templateId: 'sp-carousel-template-select',
        slidesId: 'sp-carousel-slides',
        topicId: 'sp-carousel-topic',
        contentId: 'sp-carousel-content-prompt',
        designId: 'sp-carousel-design-prompt',
        contextId: 'sp-carousel-source-context',
      });
    });
  }

  var editAssistBtn = document.getElementById('sp-carousel-edit-assist-btn');
  if (editAssistBtn) {
    editAssistBtn.addEventListener('click', function () {
      runCarouselAssist({
        buttonId: 'sp-carousel-edit-assist-btn',
        statusId: 'sp-carousel-edit-assist-status',
        sourceId: 'sp-carousel-edit-source',
        toneId: 'sp-carousel-edit-tone',
        goalId: 'sp-carousel-edit-goal',
        audienceId: 'sp-carousel-edit-audience',
        styleId: 'sp-carousel-edit-style',
        templateId: 'sp-carousel-edit-template-select',
        slidesId: 'sp-carousel-edit-slides',
        topicId: 'sp-carousel-edit-topic',
        contentId: 'sp-carousel-edit-content-prompt',
        designId: 'sp-carousel-edit-design-prompt',
        contextId: 'sp-carousel-edit-source-context',
      });
    });
  }

  if (genBtn) {
    genBtn.addEventListener('click', function () {
      var topic    = fieldValue('sp-carousel-topic', '');
      var style    = selectedStyle('sp-carousel');
      var template = selectedTemplate();
      var audience = fieldValue('sp-carousel-audience', '');
      var slidesCount = fieldValue('sp-carousel-slides', '6');
      var contentPrompt = fieldValue('sp-carousel-content-prompt', '');
      var designPrompt = composedDesignPrompt('sp-carousel');

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
          audience:          audience,
          slides:            slidesCount,
          content_prompt:    contentPrompt,
          design_prompt:     designPrompt,
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

  var rewriteBtn = document.getElementById('sp-carousel-rewrite-btn');
  var rewriteStatus = document.getElementById('sp-carousel-rewrite-status');
  if (rewriteBtn) {
    rewriteBtn.addEventListener('click', function () {
      var topic = fieldValue('sp-carousel-edit-topic', '');
      if (! topic.trim()) {
        alert('Please enter a topic.');
        return;
      }
      if (! confirm('Regenerate all slides for this carousel? Current slide edits will be replaced.')) {
        return;
      }

      rewriteBtn.disabled = true;
      if (rewriteStatus) rewriteStatus.textContent = 'Regenerating carousel...';

      fetch(actionUrl, {
        method: 'POST',
        body: new URLSearchParams({
          csrf_token:        csrfToken,
          XID:               csrfToken,
          carousel_rewrite_mode: '1',
          carousel_id:       carouselId,
          topic:             topic,
          style:             fieldValue('sp-carousel-edit-style', currentStyle),
          carousel_template: fieldValue('sp-carousel-edit-template-select', currentTemplate),
          audience:          fieldValue('sp-carousel-edit-audience', ''),
          slides:            fieldValue('sp-carousel-edit-slides', '6'),
          content_prompt:    fieldValue('sp-carousel-edit-content-prompt', ''),
          design_prompt:     composedDesignPrompt('sp-carousel-edit'),
        }),
      })
        .then(function (r) { return r.json(); })
        .then(function (json) {
          rewriteBtn.disabled = false;
          if (! json.success) {
            if (rewriteStatus) rewriteStatus.textContent = 'Error: ' + (json.message || 'unknown');
            return;
          }
          if (rewriteStatus) rewriteStatus.textContent = 'Updated. Reloading...';
          window.location.reload();
        })
        .catch(function (err) {
          rewriteBtn.disabled = false;
          if (rewriteStatus) rewriteStatus.textContent = 'Request failed: ' + err.message;
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

    updateSlideMiniPreviews(slides);

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

  function firstText(obj, keys, fallback) {
    for (var i = 0; i < keys.length; i++) {
      if (obj && obj[keys[i]]) return obj[keys[i]];
    }
    return fallback || '';
  }

  function updateSlideMiniPreviews(currentSlides) {
    document.querySelectorAll('.sp-slide-mini-preview').forEach(function (preview) {
      var idx = parseInt(preview.dataset.previewIndex, 10);
      var slide = currentSlides[idx] || initialSlides[idx] || {};
      var title = firstText(slide, ['title', 'heading', 'cta', 'name'], 'Untitled slide');
      var subtitle = firstText(slide, ['subtitle', 'description', 'body', 'summary'], '');
      var items = Array.isArray(slide.items) ? slide.items : [];

      var titleEl = preview.querySelector('.sp-slide-mini-title');
      var subtitleEl = preview.querySelector('.sp-slide-mini-subtitle');
      var itemsEl = preview.querySelector('.sp-slide-mini-items');

      if (titleEl) titleEl.textContent = title;
      if (subtitleEl) subtitleEl.textContent = subtitle;

      if (itemsEl) {
        itemsEl.innerHTML = items.slice(0, 3).map(function (item) {
          var text = firstText(item, ['name', 'title', 'label'], '');
          return text ? '<span>' + text.replace(/[&<>"']/g, function (ch) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[ch];
          }) + '</span>' : '';
        }).join('');
      }
    });
  }

  var debouncedUpdate = debounce(updatePreview, 600);

  // Watch all editable fields
  document.querySelectorAll('.sp-slide-field, .sp-item-input, #sp-caption, #sp-hashtags').forEach(function (el) {
    el.addEventListener('input', debouncedUpdate);
  });

  // Set initial slides JSON hidden field
  if (slidesJson) slidesJson.value = JSON.stringify(initialSlides);
  updateSlideMiniPreviews(initialSlides);

  function setSlideOpen(card, shouldOpen) {
    if (! card) return;
    var toggle = card.querySelector('.sp-slide-toggle');
    card.classList.toggle('is-open', shouldOpen);
    card.classList.toggle('is-collapsed', ! shouldOpen);
    if (toggle) {
      toggle.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
      toggle.textContent = shouldOpen ? 'Collapse' : 'Edit';
    }
  }

  function openOnlySlide(card) {
    document.querySelectorAll('.sp-slide-card').forEach(function (otherCard) {
      setSlideOpen(otherCard, otherCard === card);
    });
  }

  document.querySelectorAll('.sp-slide-toggle').forEach(function (toggle) {
    toggle.addEventListener('click', function () {
      var card = toggle.closest('.sp-slide-card');
      if (card.classList.contains('is-open')) {
        setSlideOpen(card, false);
      } else {
        openOnlySlide(card);
      }
    });
  });

  /* ── Per-slide AI regeneration ─────────────────────────── */
  document.querySelectorAll('.sp-regen-slide').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var idx      = parseInt(btn.dataset.index, 10);
      var card     = btn.closest('.sp-slide-card');
      var topic    = fieldValue('sp-carousel-edit-topic', <?= json_encode($post['topic'] ?? '') ?>);
      var template = currentTemplate;
      var style    = currentStyle;
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
          openOnlySlide(card);

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
