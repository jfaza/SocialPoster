<?php
ee()->load->helper('form');
$h = fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$template = $editing ?: [
    'title' => '',
    'content_type' => 'social_post',
    'platform' => 'Website',
    'generation_profile' => '',
    'length_preset' => 'medium',
    'word_count' => 600,
    'tone' => '',
    'audience' => '',
    'goal' => '',
    'research_mode' => '',
    'citation_count' => 1,
    'internal_link_count' => 1,
    'external_link_count' => 1,
    'schema_type' => 'Article',
    'image_style' => '',
    'cta_style' => '',
    'prompt_instructions' => '',
    'is_default' => 0,
];
$select = function (string $name, $current) use ($field_options, $h) {
    foreach (($field_options[$name] ?? []) as $value => $label) {
        echo '<option value="' . $h($value) . '"' . ((string) $current === (string) $value ? ' selected' : '') . '>' . $h($label) . '</option>';
    }
};
$editorActive = ! empty($editor_active);
$recommendedInstagramInstructions = (string) ($instagram_carousel_instructions ?? '');
$carouselTemplates = is_array($carousel_templates ?? null) ? $carousel_templates : [];
$carouselTemplateGuide = trim((string) ($carousel_template_guide ?? ''));
$actionUrl = (string) ($action_url ?? '');
$csrfToken = (string) ($csrf_token ?? '');
$isInstagramTemplate = ($template['platform'] ?? '') === 'Instagram';
$legacyInstagramInstructions = 'Create an Instagram carousel package with slide-by-slide copy, caption, hashtags/keywords, CTA, and a consistent branded carousel image prompt.';
$displayPromptInstructions = (string) ($template['prompt_instructions'] ?? '');
if ($isInstagramTemplate && trim($displayPromptInstructions) === $legacyInstagramInstructions) {
    $displayPromptInstructions = $recommendedInstagramInstructions;
}
$excerpt = function (string $value, int $limit = 180): string {
    $value = trim(preg_replace('/\s+/', ' ', $value) ?: '');
    if ($value === '' || strlen($value) <= $limit) {
        return $value;
    }

    return rtrim(substr($value, 0, $limit - 1)) . '...';
};
$profileDescriptions = [
    '' => [
        'title' => 'Generic / custom behavior',
        'body' => 'Use this when you want the template instructions to do most of the steering. It keeps the system flexible and avoids extra built-in behavior.',
    ],
    'website_seo_article' => [
        'title' => 'Website SEO article',
        'body' => 'Best for evergreen website content. It pushes planning and generation toward search-intent alignment, article structure, semantic keyword coverage, and publish-ready body quality.',
    ],
    'hot_tech_dev_article' => [
        'title' => 'Hot tech / dev article',
        'body' => 'Best for timely technical writing. It adds freshness pressure, favors current changes and concrete product or framework references, and helps the model avoid treating old news as current.',
    ],
    'howto_tutorial' => [
        'title' => 'How-to tutorial',
        'body' => 'Best for step-by-step teaching. It favors prerequisites, ordered actions, warnings, validation, and task clarity over broad commentary.',
    ],
    'instagram_carousel' => [
        'title' => 'Instagram carousel',
        'body' => 'Best for slide-native Instagram content. It pushes toward hook-led slide arcs, concise copy, caption direction, and one coherent square cover or visual system.',
    ],
    'facebook_short_post' => [
        'title' => 'Facebook short post',
        'body' => 'Best for short in-feed social writing. It favors one clear point, short paragraphs, human tone, and a comment-friendly CTA instead of article-style depth.',
    ],
];
?>
<div class="sp-wrap">
  <div class="sp-toolbar">
    <a class="button button--default" href="<?= $h($index_url) ?>">Create</a>
    <a class="button button--default" href="<?= $h($calendar_url) ?>">Calendar</a>
    <a class="button button--primary" href="<?= $h($new_template_url) ?>">New Content Template</a>
  </div>

  <div class="tab-wrap sp-native-tabs">
    <div class="tab-bar">
      <div class="tab-bar__tabs">
        <button type="button" class="tab-bar__tab js-tab-button <?= $editorActive ? 'active' : '' ?>" rel="t-0">Content Editor</button>
        <button type="button" class="tab-bar__tab js-tab-button <?= ! $editorActive ? 'active' : '' ?>" rel="t-1">Content Templates</button>
        <button type="button" class="tab-bar__tab js-tab-button" rel="t-2">Carousel Templates</button>
      </div>
    </div>

    <section class="sp-card tab t-0 <?= $editorActive ? 'tab-open' : '' ?>">
      <h2><?= $editing ? 'Edit Content Template' : 'New Content Template' ?></h2>
      <p class="sp-muted">Reusable generation defaults for length, tone, audience, links, citations, schema, and image style.</p>
      <?= form_open($save_url) ?>
        <input type="hidden" name="save_template" value="1">
        <div class="sp-template-editor-grid">
          <div class="sp-template-editor-main">
            <section class="sp-planning-panel sp-template-section">
              <h3>1. Purpose</h3>
              <div class="sp-field">
                <label for="sp-title">Template Name</label>
                <input id="sp-title" name="title" type="text" required value="<?= $h($template['title']) ?>" placeholder="SEO Article - Long">
              </div>

              <div class="sp-mini-grid">
                <div class="sp-field">
                  <label for="sp-type">Content Type</label>
                  <select id="sp-type" name="content_type">
                    <?php $select('content_type', $template['content_type']); ?>
                  </select>
                </div>
                <div class="sp-field">
                  <label for="sp-platform">Platform</label>
                  <select id="sp-platform" name="platform">
                    <?php $select('platform', $template['platform']); ?>
                  </select>
                </div>
                <div class="sp-field">
                  <label for="sp-generation-profile">Behavior Profile</label>
                  <select id="sp-generation-profile" name="generation_profile">
                    <?php $select('generation_profile', $template['generation_profile']); ?>
                  </select>
                  <div id="sp-profile-help" class="sp-profile-help" data-profiles='<?= $h(json_encode($profileDescriptions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>'>
                    <strong id="sp-profile-help-title"><?= $h($profileDescriptions[(string) ($template['generation_profile'] ?? '')]['title'] ?? $profileDescriptions['']['title']) ?></strong>
                    <p id="sp-profile-help-body"><?= $h($profileDescriptions[(string) ($template['generation_profile'] ?? '')]['body'] ?? $profileDescriptions['']['body']) ?></p>
                  </div>
                </div>
                <div class="sp-field">
                  <label for="sp-default">Default</label>
                  <label class="sp-checkbox-label"><input id="sp-default" name="is_default" type="checkbox" value="1" <?= ! empty($template['is_default']) ? 'checked' : '' ?>> Use as default</label>
                </div>
              </div>

              <div class="sp-field">
                <label for="sp-goal">Goal</label>
                <select id="sp-goal" name="goal">
                  <?php $select('goal', $template['goal']); ?>
                </select>
              </div>
              <div class="sp-field">
                <label for="sp-audience">Audience</label>
                <select id="sp-audience" name="audience">
                  <?php $select('audience', $template['audience']); ?>
                </select>
              </div>
              <div class="sp-field">
                <label for="sp-tone">Tone</label>
                <select id="sp-tone" name="tone">
                  <?php $select('tone', $template['tone']); ?>
                </select>
              </div>
            </section>

            <section class="sp-planning-panel sp-template-section">
              <h3>2. Behavior</h3>
              <div class="sp-mini-grid">
                <div class="sp-field">
                  <label for="sp-length">Length</label>
                  <select id="sp-length" name="length_preset">
                    <?php $select('length_preset', $template['length_preset']); ?>
                  </select>
                </div>
                <div class="sp-field">
                  <label for="sp-words">Word Count</label>
                  <select id="sp-words" name="word_count">
                    <?php $select('word_count', (int) $template['word_count']); ?>
                  </select>
                </div>
                <div class="sp-field">
                  <label for="sp-research">Research Mode</label>
                  <select id="sp-research" name="research_mode">
                    <?php $select('research_mode', $template['research_mode']); ?>
                  </select>
                </div>
              </div>

              <div class="sp-mini-grid">
                <div class="sp-field">
                  <label for="sp-citations">Citations</label>
                  <select id="sp-citations" name="citation_count">
                    <?php $select('citation_count', (int) $template['citation_count']); ?>
                  </select>
                </div>
                <div class="sp-field">
                  <label for="sp-internal">Internal Links</label>
                  <select id="sp-internal" name="internal_link_count">
                    <?php $select('internal_link_count', (int) $template['internal_link_count']); ?>
                  </select>
                </div>
                <div class="sp-field">
                  <label for="sp-external">External Links</label>
                  <select id="sp-external" name="external_link_count">
                    <?php $select('external_link_count', (int) $template['external_link_count']); ?>
                  </select>
                </div>
              </div>

              <div class="sp-mini-grid">
                <div class="sp-field">
                  <label for="sp-schema">Schema</label>
                  <select id="sp-schema" name="schema_type">
                    <?php $select('schema_type', $template['schema_type']); ?>
                  </select>
                </div>
                <div class="sp-field">
                  <label for="sp-image-style">Image Style</label>
                  <select id="sp-image-style" name="image_style">
                    <?php $select('image_style', $template['image_style']); ?>
                  </select>
                </div>
                <div class="sp-field">
                  <label for="sp-cta">CTA Style</label>
                  <select id="sp-cta" name="cta_style">
                    <?php $select('cta_style', $template['cta_style']); ?>
                  </select>
                </div>
              </div>
            </section>

            <section class="sp-planning-panel sp-template-section">
              <h3>3. Instructions</h3>
              <div class="sp-field">
                <label for="sp-instructions">Prompt Instructions</label>
                <textarea id="sp-instructions" name="prompt_instructions" rows="10"><?= $h($displayPromptInstructions) ?></textarea>
                <div id="sp-instagram-template-help" class="sp-template-help">
                  <p class="sp-muted">Instagram carousel templates generate one square cover or visual-system image plus carousel-ready slide copy. Use these stricter instructions if the output starts reading like a blog post.</p>
                  <button type="button" id="sp-use-instagram-instructions" class="button button--default">Use Instagram Carousel Instructions</button>
                </div>
              </div>
            </section>

            <div class="sp-actions">
              <button type="submit" class="button button--primary">Save Template</button>
              <?php if ($editing): ?>
                <a class="button button--default" href="<?= $h($templates_url) ?>">Cancel</a>
              <?php endif; ?>
            </div>
          </div>

          <aside class="sp-template-editor-side">
            <div class="sp-summary sp-template-section">
              <h3>What This Controls</h3>
              <div class="sp-generator-notes">
                <div><strong>Purpose</strong><span>Defines what kind of content this template is meant to produce and for whom.</span></div>
                <div><strong>Behavior</strong><span>The behavior profile acts like a lightweight built-in skill, while the surrounding settings shape detail, research, links, and structure.</span></div>
                <div><strong>Instructions</strong><span>Gives the model the sharpest steering. This is the field that most changes output quality.</span></div>
              </div>
            </div>
          </aside>
        </div>
      <?= form_close() ?>
    </section>

    <section class="sp-card tab t-1 <?= ! $editorActive ? 'tab-open' : '' ?>">
      <h2>Content Templates</h2>
      <p class="sp-muted">Reusable prompt and behavior presets for blog, social, and platform-specific content generation.</p>
      <?php foreach ($rows as $row): ?>
        <article class="sp-template">
          <div class="sp-template-head">
            <div>
              <h3><?= $h($row['title']) ?></h3>
              <p class="sp-muted">
                <span class="sp-template-badge sp-template-badge--<?= $h(strtolower(trim(preg_replace('/[^a-z0-9]+/', '-', (string) ($row['platform'] ?? 'website'))) ?: 'website')) ?>"><?= $h($row['platform']) ?></span>
                <span class="sp-template-meta"><?= $h($row['content_type']) ?> · <?= $h($row['length_preset']) ?> · <?= (int) $row['word_count'] ?> words</span>
                <?php if (! empty($row['is_default'])): ?>
                  <span class="sp-template-default">Default</span>
                <?php endif; ?>
              </p>
            </div>
          </div>
          <p><strong><?= $h($row['goal']) ?></strong></p>
          <p class="sp-muted">Audience: <?= $h($row['audience']) ?> · Tone: <?= $h($row['tone']) ?><?php if (! empty($row['generation_profile'])): ?> · Profile: <?= $h($field_options['generation_profile'][$row['generation_profile']] ?? $row['generation_profile']) ?><?php endif; ?></p>
          <p class="sp-template-excerpt"><?= $h($excerpt((string) ($row['prompt_instructions'] ?? ''))) ?></p>
          <div class="sp-actions">
            <a class="button button--primary" href="<?= $h($templates_url . '/' . (int) $row['id']) ?>#tab=t-0">Edit</a>
            <?= form_open($templates_url) ?>
              <input type="hidden" name="delete_template" value="<?= (int) $row['id'] ?>">
              <button type="submit" class="button button--danger">Delete</button>
            <?= form_close() ?>
          </div>
        </article>
      <?php endforeach; ?>
    </section>

    <section class="sp-card tab t-2">
      <h2>Carousel Templates</h2>
      <p class="sp-muted">Reusable visual systems for Instagram carousel rendering. These control layout, example data, and preview style for carousel generation.</p>

      <div class="sp-template-generator-panel">
        <h3>Build Carousel Template</h3>
        <p class="sp-muted">Use the baked-in template spec below, then give the generator a clear template name and design direction. It will create the renderer package and add it to this library.</p>
        <div class="sp-mini-grid">
          <div class="sp-field">
            <label for="sp-carousel-template-name">Template Name</label>
            <input type="text" id="sp-carousel-template-name" placeholder="e.g. glass-command-center" autocomplete="off">
          </div>
          <div class="sp-field">
            <label for="sp-carousel-template-instructions">Design Instructions</label>
            <textarea id="sp-carousel-template-instructions" rows="4" placeholder="Describe the visual system, layout style, density, tone, and what should make this template feel distinct."></textarea>
          </div>
        </div>
        <div class="sp-actions sp-single-assist-actions">
          <button type="button" id="sp-carousel-template-generate-btn" class="button button--primary">Generate Carousel Template</button>
          <span id="sp-carousel-template-generate-status" class="sp-muted"></span>
        </div>
      </div>

      <div class="sp-summary sp-template-section">
        <h3>How Carousel Templates Are Built</h3>
        <div class="sp-generator-notes">
          <div><strong>Package shape</strong><span>Each carousel template is a folder with `slides.html`, `example.json`, `schema.json`, and `meta.json`.</span></div>
          <div><strong>Data contract</strong><span>Templates should expect cover, content, and outro slides, handle empty `items` on cover/outro, and render only fields that actually have content.</span></div>
          <div><strong>Design target</strong><span>They are built for 1080×1080 Instagram slides with a modern SaaS / developer / AI-tools look, readable typography, and no external image dependencies.</span></div>
        </div>
        <?php if ($carouselTemplateGuide !== ''): ?>
          <details class="sp-template-guide-details">
            <summary>View Full Carousel Template Creation Spec</summary>
            <pre class="sp-template-guide"><?= $h($carouselTemplateGuide) ?></pre>
          </details>
        <?php endif; ?>
      </div>

      <?php if (empty($carouselTemplates)): ?>
        <p class="sp-muted">No carousel templates are available yet.</p>
      <?php else: ?>
        <?php foreach ($carouselTemplates as $tpl): ?>
          <article class="sp-template">
            <div class="sp-template-head">
              <div>
                <h3><?= $h((string) ($tpl['name'] ?? $tpl['folder'] ?? 'Carousel Template')) ?></h3>
                <p class="sp-muted">
                  <span class="sp-template-badge sp-template-badge--instagram">Instagram</span>
                  <span class="sp-template-meta">Visual template · folder: <?= $h((string) ($tpl['folder'] ?? '')) ?></span>
                </p>
              </div>
            </div>
            <?php if (! empty($tpl['description'])): ?>
              <p><?= $h((string) $tpl['description']) ?></p>
            <?php else: ?>
              <p class="sp-muted">Reusable carousel rendering template.</p>
            <?php endif; ?>
            <?php if (! empty($tpl['previews'])): ?>
              <div class="sp-template-preview-strip">
                <?php foreach (array_slice((array) $tpl['previews'], 0, 2) as $preview): ?>
                  <img src="<?= $h((string) $preview) ?>" alt="<?= $h((string) ($tpl['name'] ?? $tpl['folder'] ?? 'Carousel template preview')) ?>" loading="lazy">
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
            <p class="sp-muted">
              <?= ! empty($tpl['previews']) ? count((array) $tpl['previews']) . ' preview image' . (count((array) $tpl['previews']) === 1 ? '' : 's') : 'No preview images' ?>
              · used by Instagram carousel generation
            </p>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </section>
  </div>
</div>

<script>
(function () {
  <?php if (! $editing): ?>
  const title = document.getElementById('sp-title');
  const type = document.getElementById('sp-type');
  const platform = document.getElementById('sp-platform');
  const generationProfile = document.getElementById('sp-generation-profile');
  const length = document.getElementById('sp-length');
  const instructions = document.getElementById('sp-instructions');
  const instagramHelp = document.getElementById('sp-instagram-template-help');
  const instagramButton = document.getElementById('sp-use-instagram-instructions');
  const profileHelp = document.getElementById('sp-profile-help');
  const profileHelpTitle = document.getElementById('sp-profile-help-title');
  const profileHelpBody = document.getElementById('sp-profile-help-body');
  const instagramInstructions = <?= json_encode((string) ($instagram_carousel_instructions ?? '')) ?>;
  const profileDescriptions = profileHelp ? JSON.parse(profileHelp.dataset.profiles || '{}') : {};

  function isInstagramTemplate() {
    return platform && platform.value === 'Instagram';
  }

  function syncInstagramHelp() {
    if (instagramHelp) {
      instagramHelp.classList.toggle('is-visible', isInstagramTemplate());
    }
  }

  function syncProfileHelp() {
    if (!generationProfile || !profileHelp || !profileHelpTitle || !profileHelpBody) {
      return;
    }

    const key = generationProfile.value || '';
    const def = profileDescriptions[key] || profileDescriptions[''] || {title: 'Generic / custom behavior', body: ''};
    profileHelpTitle.textContent = def.title || 'Generic / custom behavior';
    profileHelpBody.textContent = def.body || '';
  }

  if (instagramButton && instructions) {
    instagramButton.addEventListener('click', function () {
      instructions.value = instagramInstructions;
      instructions.focus();
    });
  }

  function syncTitle() {
    if (title.dataset.touched === '1') {
      return;
    }

    title.value = type.options[type.selectedIndex].text + ' - ' + platform.value + ' - ' + length.options[length.selectedIndex].text;
  }

  title.addEventListener('input', function () {
    title.dataset.touched = title.value.trim() === '' ? '0' : '1';
  });

  [type, platform, length].forEach(function (field) {
    field.addEventListener('change', syncTitle);
  });

  if (platform) {
    platform.addEventListener('change', syncInstagramHelp);
  }
  if (generationProfile) {
    generationProfile.addEventListener('change', syncProfileHelp);
  }

  syncTitle();
  syncInstagramHelp();
  syncProfileHelp();
  <?php endif; ?>

  <?php if ($editing): ?>
  const platform = document.getElementById('sp-platform');
  const generationProfile = document.getElementById('sp-generation-profile');
  const instructions = document.getElementById('sp-instructions');
  const instagramHelp = document.getElementById('sp-instagram-template-help');
  const instagramButton = document.getElementById('sp-use-instagram-instructions');
  const profileHelp = document.getElementById('sp-profile-help');
  const profileHelpTitle = document.getElementById('sp-profile-help-title');
  const profileHelpBody = document.getElementById('sp-profile-help-body');
  const instagramInstructions = <?= json_encode((string) ($instagram_carousel_instructions ?? '')) ?>;
  const profileDescriptions = profileHelp ? JSON.parse(profileHelp.dataset.profiles || '{}') : {};

  function isInstagramTemplate() {
    return platform && platform.value === 'Instagram';
  }

  function syncInstagramHelp() {
    if (instagramHelp) {
      instagramHelp.classList.toggle('is-visible', isInstagramTemplate());
    }
  }

  function syncProfileHelp() {
    if (!generationProfile || !profileHelp || !profileHelpTitle || !profileHelpBody) {
      return;
    }

    const key = generationProfile.value || '';
    const def = profileDescriptions[key] || profileDescriptions[''] || {title: 'Generic / custom behavior', body: ''};
    profileHelpTitle.textContent = def.title || 'Generic / custom behavior';
    profileHelpBody.textContent = def.body || '';
  }

  if (instagramButton && instructions) {
    instagramButton.addEventListener('click', function () {
      instructions.value = instagramInstructions;
      instructions.focus();
    });
  }

  if (platform) {
    platform.addEventListener('change', syncInstagramHelp);
  }
  if (generationProfile) {
    generationProfile.addEventListener('change', syncProfileHelp);
  }

  syncInstagramHelp();
  syncProfileHelp();
  <?php endif; ?>

  const carouselTemplateGenerateBtn = document.getElementById('sp-carousel-template-generate-btn');
  const carouselTemplateGenerateStatus = document.getElementById('sp-carousel-template-generate-status');
  const carouselTemplateName = document.getElementById('sp-carousel-template-name');
  const carouselTemplateInstructions = document.getElementById('sp-carousel-template-instructions');
  const actionUrl = <?= json_encode($actionUrl) ?>;
  const csrfToken = <?= json_encode($csrfToken) ?>;

  if (carouselTemplateGenerateBtn && actionUrl && csrfToken) {
    carouselTemplateGenerateBtn.addEventListener('click', function () {
      const name = (carouselTemplateName && carouselTemplateName.value || '').trim();
      const instructions = (carouselTemplateInstructions && carouselTemplateInstructions.value || '').trim();

      if (!name) {
        alert('Please enter a template name.');
        return;
      }

      carouselTemplateGenerateBtn.disabled = true;
      if (carouselTemplateGenerateStatus) {
        carouselTemplateGenerateStatus.textContent = 'Generating carousel template package...';
      }

      fetch(actionUrl, {
        method: 'POST',
        body: new URLSearchParams({
          csrf_token: csrfToken,
          XID: csrfToken,
          carousel_template_mode: '1',
          template_name: name,
          template_instructions: instructions,
        }),
      })
        .then(function (response) { return response.json(); })
        .then(function (json) {
          carouselTemplateGenerateBtn.disabled = false;
          if (!json.success) {
            if (carouselTemplateGenerateStatus) {
              carouselTemplateGenerateStatus.textContent = 'Error: ' + (json.message || 'unknown');
            }
            return;
          }

          if (carouselTemplateGenerateStatus) {
            carouselTemplateGenerateStatus.textContent = 'Template generated. Reloading library...';
          }
          window.location.href = <?= json_encode($templates_url . '#tab=t-2') ?>;
        })
        .catch(function (error) {
          carouselTemplateGenerateBtn.disabled = false;
          if (carouselTemplateGenerateStatus) {
            carouselTemplateGenerateStatus.textContent = 'Request failed: ' + error.message;
          }
        });
    });
  }
})();
</script>
