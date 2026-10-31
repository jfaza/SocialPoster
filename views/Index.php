<?php
ee()->load->helper('form');
$h = fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$pageMode = ($page_mode ?? 'general') === 'instagram' ? 'instagram' : 'general';
$isInstagramPage = $pageMode === 'instagram';
$selectedTemplateId = (int) ($selected_template_id ?? 0);
$generalFieldsClass = $isInstagramPage ? 'is-hidden' : '';
$instagramBuilderClass = $isInstagramPage ? 'sp-instagram-builder' : 'sp-instagram-builder is-hidden';
$hotArticles = $hot_articles ?? [];
$hotArticlesMeta = $hot_articles_meta ?? [];
?>
<div class="sp-wrap">

  <section class="sp-card sp-generator-shell">
      <div class="sp-generator-intro">
      <h2><?= $isInstagramPage ? 'Generate Instagram Post' : 'Generate Social Post' ?></h2>
      <p class="sp-muted">
        <?= $isInstagramPage
            ? 'Pick an Instagram template and build the carousel prompt, caption, and image plan for that post.'
            : 'Describe the topic, audience, tone, and any links or portfolio context to include.' ?>
      </p>
      </div>
      <form id="socialposter-form" method="post" action="<?= $h($action_url) ?>">
        <input type="hidden" name="csrf_token" value="<?= $h($csrf_token) ?>">
        <input type="hidden" name="XID" value="<?= $h($csrf_token) ?>">
        <div class="sp-generator-grid">
          <div class="sp-generator-main">
            <div class="sp-planning-panel sp-panel-section">
              <h3>1. Choose Direction</h3>
              <div class="sp-field">
                <label for="socialposter-template">Template</label>
                <select id="socialposter-template" name="template_id">
                  <?php foreach ($template_options as $value => $label): ?>
                    <option value="<?= (int) $value ?>" <?= (int) $value === $selectedTemplateId ? 'selected' : '' ?>><?= $h($label) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="sp-field">
                <label for="socialposter-starter">Starter</label>
                <select id="socialposter-starter"></select>
              </div>
              <div class="sp-actions sp-starter-actions">
                <button type="button" id="socialposter-apply-starter" class="button button--default">Use Starter</button>
                <span id="socialposter-starter-note" class="sp-muted"></span>
              </div>

              <?php if (! $isInstagramPage): ?>
              <div class="sp-field">
                <label>Topic Source</label>
                <div class="sp-source-mode" role="tablist" aria-label="Topic source">
                  <button type="button" id="sp-source-manual" class="sp-source-mode__button is-active">Write from Scratch</button>
                  <button type="button" id="sp-source-hot" class="sp-source-mode__button">Use Hot Article</button>
                </div>
              </div>
              <?php endif; ?>
            </div>

            <?php if (! $isInstagramPage): ?>
            <section id="sp-hot-articles-panel" class="sp-panel-section is-hidden">
              <div class="sp-hot-toolbar">
                <div>
                  <strong>Hot Articles</strong>
                  <p id="sp-hot-meta" class="sp-muted"></p>
                </div>
                <div class="sp-actions">
                  <a class="button button--default" href="<?= $h($news_sources_url) ?>">Manage Sources</a>
                  <button type="button" id="sp-hot-refresh-btn" class="button button--default">Refresh Hot Articles</button>
                </div>
              </div>
              <div id="sp-hot-status" class="sp-muted">Pick a current article when you want the post anchored to live tech or developer news.</div>
              <div id="sp-hot-article-grid" class="sp-article-grid sp-hot-article-grid"></div>
            </section>
            <?php endif; ?>

            <div class="sp-planning-panel sp-panel-section">
              <h3>2. Prepare the Post</h3>
              <div class="sp-field">
                <label for="sp-plan-title">Title</label>
                <input id="sp-plan-title" name="title" type="text" placeholder="<?= $isInstagramPage ? 'Working title for this carousel' : 'Working title for this post' ?>">
              </div>
              <div class="sp-field">
                <label for="sp-plan-brief">Brief</label>
                <textarea id="sp-plan-brief" name="brief" rows="4" placeholder="<?= $isInstagramPage ? 'Short carousel brief: audience, angle, promise, and constraints.' : 'Short content brief: goal, audience, key angle, and constraints.' ?>"></textarea>
              </div>
              <div class="sp-field">
                <label for="sp-plan-keywords"><?= $isInstagramPage ? 'Keywords / Hashtag Themes' : 'Keywords' ?></label>
                <input id="sp-plan-keywords" name="keywords" type="text" placeholder="<?= $isInstagramPage ? 'SEO, AI visibility, website clarity, content strategy' : 'SEO, AI visibility, website clarity' ?>">
              </div>
              <?php if (! $isInstagramPage): ?>
              <div class="sp-field">
                <label for="socialposter-image-brief">Image Brief</label>
                <textarea id="socialposter-image-brief" name="image_brief" rows="4" placeholder="Example: editorial hero image, no text, clean workspace, natural light, confident but not corporate."></textarea>
              </div>
              <?php endif; ?>
              <div class="sp-actions">
                <button type="button" id="sp-generate-plan" class="button button--default"><?= $isInstagramPage ? 'Build Carousel Plan' : 'Generate Plan' ?></button>
                <span id="sp-plan-status" class="sp-muted"></span>
              </div>
            </div>

            <div id="sp-instagram-builder" class="<?= $h($instagramBuilderClass) ?> sp-panel-section">
              <h3>3. Shape the Carousel</h3>
              <div class="sp-field">
                <label for="sp-ig-topic">Topic</label>
                <input id="sp-ig-topic" type="text" placeholder="Example: Why traditional SEO is not enough anymore">
              </div>
              <div class="sp-mini-grid">
                <div class="sp-field">
                  <label for="sp-ig-audience">Audience</label>
                  <input id="sp-ig-audience" type="text" value="business owners, founders, and marketing teams">
                </div>
                <div class="sp-field">
                  <label for="sp-ig-slide-count">Slides</label>
                  <select id="sp-ig-slide-count">
                    <?php foreach ([6, 7, 8] as $slideCount): ?>
                      <option value="<?= (int) $slideCount ?>" <?= $slideCount === 7 ? 'selected' : '' ?>><?= (int) $slideCount ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="sp-field">
                  <label for="sp-ig-cta">CTA</label>
                  <input id="sp-ig-cta" type="text" value="soft CTA to work with Javid Fazaeli">
                </div>
              </div>
              <div class="sp-field">
                <label for="sp-ig-outline">Slide Outline</label>
                <textarea id="sp-ig-outline" rows="7" placeholder="Slide 1: Hook&#10;Slide 2: Problem&#10;Slide 3: Framework&#10;Slide 4: Example&#10;Slide 5: CTA"></textarea>
              </div>
              <div class="sp-field">
                <label for="sp-ig-caption">Caption Direction</label>
                <textarea id="sp-ig-caption" rows="4" placeholder="Write a concise caption that expands the core idea, invites comments, and avoids hype."></textarea>
              </div>
              <div class="sp-field">
                <label for="sp-ig-hashtags">Hashtag Direction</label>
                <input id="sp-ig-hashtags" type="text" value="#SEO, #AIO, #ContentStrategy, #WebDesign, #DigitalMarketing">
              </div>

              <h3>Cover Visual Direction</h3>
              <p class="sp-muted">The current Instagram generator creates one square cover image, so keep this focused on the carousel cover or reusable visual system.</p>
              <div class="sp-field">
                <label for="sp-ig-cover-visual">Cover Image Brief</label>
                <textarea id="sp-ig-cover-visual" rows="4" placeholder="Square Instagram carousel cover, editorial and premium, no logos, no readable text, visually aligned with the slide outline."></textarea>
              </div>
            </div>

            <div id="sp-general-fields" class="<?= $h($generalFieldsClass) ?> sp-panel-section">
              <h3>3. Your Notes and Experience</h3>
              <div class="sp-field">
                <label for="sp-author-notes">What do you know about this that others don't?</label>
                <p class="sp-muted">Recommended. Two or three sentences of your own view, a real example from your work, a mistake you've seen, or what you'd tell a client. The article is built around these and never invents experience beyond them.</p>
                <textarea id="sp-author-notes" rows="5" placeholder="Example: On a recent ExpressionEngine project, moving image processing to a queue cut page saves from 8 seconds to under 1. Most teams I talk to optimize the database first, but here it barely mattered."></textarea>
              </div>

              <h3>4. Optional Request Override</h3>
              <div class="sp-field">
                <label for="socialposter-prompt">Additional Instructions</label>
                <p class="sp-muted">Leave this blank to generate from the plan above. Use it only when you want to add extra context, constraints, or a custom angle.</p>
                <textarea id="socialposter-prompt" name="prompt" rows="10" placeholder="Optional: add extra context, constraints, links, or a more specific angle for this post."></textarea>
              </div>
            </div>

            <div class="sp-actions sp-generator-submit">
              <button id="socialposter-submit" type="submit" class="button button--primary">Generate</button>
              <span id="socialposter-status" class="sp-muted"></span>
            </div>
          </div>

          <aside class="sp-generator-side">
            <div class="sp-summary sp-panel-section">
              <h3>Workflow</h3>
              <p class="sp-muted"><?= $isInstagramPage ? 'Choose a template, build a plan, refine the carousel structure, then generate.' : 'Choose a template, optionally anchor it to a hot article, prepare the post, and generate. Add extra instructions only when you need a custom angle.' ?></p>
              <div class="sp-generator-notes">
                <div><strong>Template</strong><span>The template sets the structure and output style.</span></div>
                <div><strong>Prepare</strong><span>Set the title, brief, keywords, and image brief before generating.</span></div>
                <div><strong>Override</strong><span>Additional instructions are optional and only needed for extra context or constraints.</span></div>
                <div><strong>Generate</strong><span>The final request is assembled from the plan, with any optional override added in.</span></div>
              </div>
            </div>
          </aside>
        </div>
      </form>
  </section>

    <section id="socialposter-output" class="sp-card sp-output is-hidden">
      <div class="sp-output-head">
        <div>
          <h2 id="sp-title">Generated Post</h2>
          <p id="sp-result-meta" class="sp-muted"></p>
        </div>
        <div id="sp-result-actions" class="sp-actions is-hidden">
          <a id="sp-edit-link" class="button button--default" href="<?= $h($history_url) ?>">Edit</a>
          <a class="button button--primary" href="<?= $h($publish_url) ?>">Publish</a>
        </div>
      </div>
      <p id="sp-message" class="sp-error"></p>
      <div class="sp-output-grid">
        <div>
          <img id="sp-image" class="sp-preview is-hidden" alt="">
          <h3>Image Brief</h3>
          <textarea id="sp-image-brief" rows="4" readonly></textarea>
        </div>
        <div id="sp-general-summary-fields" class="sp-summary">
          <h3>Category</h3>
          <p id="sp-category"></p>
          <h3>SEO Keywords</h3>
          <p id="sp-keywords"></p>
          <h3>Hashtags</h3>
          <p id="sp-hashtags"></p>
          <h3>Links</h3>
          <p><strong>External:</strong> <span id="sp-external"></span></p>
          <p><strong>Internal:</strong> <span id="sp-internal"></span></p>
        </div>
      </div>
      <div id="sp-instagram-result-fields" class="is-hidden">
        <p class="sp-muted">Instagram publishing uses one caption field on the final post or carousel container. Child carousel images do not have their own captions.</p>
        <h3>Carousel Slide Copy</h3>
        <textarea id="sp-instagram-slides" rows="8" readonly></textarea>
        <h3>Instagram Caption</h3>
        <textarea id="sp-instagram-caption" rows="6" readonly></textarea>
        <div id="sp-slide-images-section" class="is-hidden">
          <h3>Slide Images</h3>
          <p class="sp-muted" id="sp-slide-image-status"></p>
          <div id="sp-slide-image-grid" class="sp-carousel-image-grid"></div>
        </div>
      </div>
      <div id="sp-general-result-fields">
        <h3>Post Text</h3>
        <textarea id="sp-post" rows="8" readonly></textarea>
      </div>
      <div id="sp-general-extra-fields">
        <h3>Intro Text</h3>
        <textarea id="sp-intro" rows="4" readonly></textarea>
        <h3>Table of Contents</h3>
        <ul id="sp-toc" class="sp-list"></ul>
        <h3>Recommended Topics</h3>
        <ul id="sp-topics" class="sp-list"></ul>
      </div>
    </section>
</div>

<script>
(function () {
  const form = document.getElementById('socialposter-form');
  const status = document.getElementById('socialposter-status');
  const submit = document.getElementById('socialposter-submit');
  const output = document.getElementById('socialposter-output');
  const message = document.getElementById('sp-message');
  const prompt = document.getElementById('socialposter-prompt');
  const authorNotes = document.getElementById('sp-author-notes');
  const imageBrief = document.getElementById('socialposter-image-brief');
  const template = document.getElementById('socialposter-template');
  const starter = document.getElementById('socialposter-starter');
  const starterButton = document.getElementById('socialposter-apply-starter');
  const starterNote = document.getElementById('socialposter-starter-note');
  const isInstagramPage = <?= json_encode($isInstagramPage) ?>;
  const templateMeta = <?= json_encode($template_meta ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
  const generalFields = document.getElementById('sp-general-fields');
  const instagramBuilder = document.getElementById('sp-instagram-builder');
  const planTitle = document.getElementById('sp-plan-title');
  const planBrief = document.getElementById('sp-plan-brief');
  const planKeywords = document.getElementById('sp-plan-keywords');
  const planStatus = document.getElementById('sp-plan-status');
  const resultActions = document.getElementById('sp-result-actions');
  const editLink = document.getElementById('sp-edit-link');
  const historyUrl = <?= json_encode((string) $history_url) ?>;
  const historyItemUrlPattern = <?= json_encode((string) ($history_item_url_pattern ?? '')) ?>;
  const starters = <?= json_encode($template_starters ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
  const instagramResultFields = document.getElementById('sp-instagram-result-fields');
  const generalResultFields = document.getElementById('sp-general-result-fields');
  const generalSummaryFields = document.getElementById('sp-general-summary-fields');
  const generalExtraFields = document.getElementById('sp-general-extra-fields');
  const slideImagesSection = document.getElementById('sp-slide-images-section');
  const slideImageStatus = document.getElementById('sp-slide-image-status');
  const slideImageGrid = document.getElementById('sp-slide-image-grid');
  const csrfToken = <?= json_encode((string) $csrf_token) ?>;
  const newsSourcesUrl = <?= json_encode((string) ($news_sources_url ?? '')) ?>;
  const hotGrid = document.getElementById('sp-hot-article-grid');
  const hotRefreshBtn = document.getElementById('sp-hot-refresh-btn');
  const hotStatus = document.getElementById('sp-hot-status');
  const hotMeta = document.getElementById('sp-hot-meta');
  const hotPanel = document.getElementById('sp-hot-articles-panel');
  const sourceManualButton = document.getElementById('sp-source-manual');
  const sourceHotButton = document.getElementById('sp-source-hot');
  const initialHotArticles = <?= json_encode($hotArticles, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
  const initialHotMeta = <?= json_encode($hotArticlesMeta, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
  let promptTouched = false;
  let imageBriefTouched = false;
  let sourceMode = 'manual';

  function isInstagramTemplate() {
    const meta = templateMeta[template.value] || {};
    const platform = (meta.platform || '').toLowerCase();
    const title = (meta.title || '').toLowerCase();
    return platform === 'instagram' || title.indexOf('instagram carousel') !== -1;
  }

  function syncTemplateForm() {
    if (isInstagramPage) {
      generalFields.classList.add('is-hidden');
      instagramBuilder.classList.remove('is-hidden');
      starter.parentElement.classList.add('is-hidden');
      starterButton.parentElement.classList.add('is-hidden');
      return;
    }

    const instagram = isInstagramTemplate();
    generalFields.classList.toggle('is-hidden', instagram);
    instagramBuilder.classList.toggle('is-hidden', !instagram);
    starter.parentElement.classList.toggle('is-hidden', instagram);
    starterButton.parentElement.classList.toggle('is-hidden', instagram);
  }

  function syncSourceMode(nextMode) {
    if (!hotPanel || isInstagramPage) {
      return;
    }

    sourceMode = nextMode === 'hot' ? 'hot' : 'manual';
    hotPanel.classList.toggle('is-hidden', sourceMode !== 'hot');
    if (sourceManualButton) {
      sourceManualButton.classList.toggle('is-active', sourceMode === 'manual');
    }
    if (sourceHotButton) {
      sourceHotButton.classList.toggle('is-active', sourceMode === 'hot');
    }
  }

  function carouselImagePlan() {
    const coverVisual = document.getElementById('sp-ig-cover-visual');
    if (!coverVisual) {
      return '';
    }

    return coverVisual.value.trim();
  }

  function applyImagePlan(images) {
    const imageItems = images || [];
    if (isInstagramTemplate()) {
      const coverField = document.getElementById('sp-ig-cover-visual');
      if (coverField && imageItems.length) {
        const descriptions = imageItems.map(function (image) {
          return image && image.description ? image.description.trim() : '';
        }).filter(Boolean);
        if (descriptions.length) {
          coverField.value = descriptions[0];
        }
      }
      return;
    }

    imageBrief.value = imageItems.map(function (image) {
      return image && image.description ? image.description : '';
    }).filter(Boolean).join("\n\n");
    imageBriefTouched = imageBrief.value.trim() !== '';
  }

  function planningPayload(mode) {
    const data = new FormData();
    data.append('csrf_token', <?= json_encode((string) $csrf_token) ?>);
    data.append('XID', <?= json_encode((string) $csrf_token) ?>);
    data.append('plan_mode', mode);
    data.append('template_id', template.value || '0');
    data.append('title', planTitle.value || '');
    data.append('brief', planBrief.value || '');
    data.append('keywords', planKeywords.value || '');
    data.append('prompt', (isInstagramPage || isInstagramTemplate()) ? buildInstagramPrompt() : (prompt.value || ''));
    data.append('image_plan', carouselImagePlan() || imageBrief.value || '');
    return data;
  }

  function runPlanner(mode) {
    planStatus.textContent = 'Working...';

    fetch(form.action, {
      method: 'POST',
      body: planningPayload(mode),
      credentials: 'same-origin',
      headers: {'X-Requested-With': 'XMLHttpRequest'}
    })
      .then(function (response) {
        return response.text().then(function (body) {
          const data = body ? JSON.parse(body) : null;
          if (!response.ok || !data || !data.success) {
            throw new Error((data && data.message) || 'Planning request failed.');
          }
          return data.result || {};
        });
      })
      .then(function (result) {
        if (mode === 'creative' || mode === 'title' || !planTitle.value.trim()) {
          planTitle.value = result.title || planTitle.value;
        }
        if (mode === 'creative' || mode === 'brief' || !planBrief.value.trim()) {
          planBrief.value = result.brief || planBrief.value;
        }
        if (result.keywords && result.keywords.length) {
          planKeywords.value = result.keywords.join(', ');
        }
        if ((mode === 'images' || mode === 'creative') && isInstagramPage) {
          const topicField = document.getElementById('sp-ig-topic');
          const outlineField = document.getElementById('sp-ig-outline');
          const captionField = document.getElementById('sp-ig-caption');
          const hashtagField = document.getElementById('sp-ig-hashtags');

          if (topicField && result.topic) {
            topicField.value = result.topic;
          }
          if (outlineField && result.slide_outline) {
            outlineField.value = result.slide_outline;
          }
          if (captionField && result.caption_direction) {
            captionField.value = result.caption_direction;
          }
          if (hashtagField && result.hashtag_direction) {
            hashtagField.value = result.hashtag_direction;
          }
        }
        if (mode === 'images' || mode === 'creative') {
          applyImagePlan(result.images || []);
        }
        planStatus.textContent = mode === 'creative'
          ? (isInstagramPage ? 'Carousel plan ready' : 'Plan ready')
          : 'Done';
      })
      .catch(function (error) {
        planStatus.textContent = error && error.message ? error.message : 'Planning failed.';
      });
  }

  function buildInstagramPrompt() {
    const topic = document.getElementById('sp-ig-topic').value.trim();
    const audience = document.getElementById('sp-ig-audience').value.trim();
    const slideCount = document.getElementById('sp-ig-slide-count').value;
    const cta = document.getElementById('sp-ig-cta').value.trim();
    const outline = document.getElementById('sp-ig-outline').value.trim();
    const caption = document.getElementById('sp-ig-caption').value.trim();
    const hashtags = document.getElementById('sp-ig-hashtags').value.trim();
    const images = carouselImagePlan();

    return [
      'Create a native Instagram carousel package.',
      '',
      'Working title: ' + (planTitle.value.trim() || '[generate a strong title]'),
      'Brief: ' + (planBrief.value.trim() || '[use the topic and outline to create a concise brief]'),
      'Keywords: ' + (planKeywords.value.trim() || '[generate relevant keywords]'),
      '',
      'Topic: ' + (topic || '[topic]'),
      'Audience: ' + (audience || '[audience]'),
      'Slide count: ' + slideCount,
      'CTA: ' + (cta || 'soft conversation starter'),
      '',
      'Slide outline:',
      outline || 'Create a strong hook, concise teaching slides, practical checklist, and CTA slide.',
      '',
      'Caption direction:',
      caption || 'Write an Instagram caption that supports the carousel and invites replies.',
      '',
      'Hashtag direction:',
      hashtags || 'Use relevant professional hashtags.',
      '',
      'Cover visual direction:',
      images || 'Square Instagram carousel cover, premium editorial feel, no logos, no readable text.',
      '',
      'Return slide-by-slide copy, Instagram caption, hashtags, and one cover visual direction that matches the outline. Do not write a blog article.'
    ].join("\n");
  }

  function setList(id, items) {
    const el = document.getElementById(id);
    el.innerHTML = '';
    (items || []).forEach(function (item) {
      const li = document.createElement('li');
      li.textContent = item;
      el.appendChild(li);
    });
  }

  function esc(value) {
    return String(value || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function formatPublishedDate(value) {
    if (!value) {
      return '';
    }

    try {
      return new Date(value).toLocaleDateString();
    } catch (error) {
      return '';
    }
  }

  function updateHotMeta(meta, count) {
    if (!hotMeta) {
      return;
    }

    meta = meta || {};
    if (!meta.exists) {
      hotMeta.textContent = 'Cache table is not available yet. Run the add-on update, then refresh.';
      return;
    }
    if (!count) {
      hotMeta.textContent = 'No cached articles yet.';
      return;
    }

    const age = meta.age_seconds;
    const ageText = typeof age === 'number'
      ? (age < 90 ? 'just now' : Math.round(age / 60) + ' min ago')
      : 'unknown age';
    hotMeta.textContent = count + ' cached articles · refreshed ' + ageText + (meta.is_stale ? ' · stale' : '');
  }

  function hotArticleContext(article) {
    const lines = [
      'Use this current source context for a timely post. Treat it as the latest available article context and avoid older unrelated model or product references.',
      'Article title: ' + (article.title || ''),
      article.source_name ? 'Source: ' + article.source_name : '',
      article.published_at ? 'Published: ' + article.published_at : '',
      article.url ? 'URL: ' + article.url : '',
      article.excerpt ? 'Excerpt: ' + article.excerpt : '',
      'Write for developers and technical buyers. Explain what changed, why it matters, practical implications, risks, and next steps.'
    ];

    return lines.filter(Boolean).join("\n");
  }

  function applyHotArticle(article) {
    if (!article) {
      return;
    }

    if (!planTitle.value.trim()) {
      planTitle.value = article.title || '';
    }

    const briefParts = [
      article.source_name ? 'Source: ' + article.source_name : '',
      article.excerpt || '',
      article.url ? 'Reference: ' + article.url : ''
    ].filter(Boolean);
    planBrief.value = briefParts.join("\n");

    const context = hotArticleContext(article);
    prompt.value = context;
    promptTouched = true;

    if (!imageBrief.value.trim()) {
      imageBrief.value = 'Editorial technology article image, realistic technical workspace, no readable text, directly related to: ' + (article.title || 'the topic');
      imageBriefTouched = true;
    }

    if (hotStatus) {
      hotStatus.textContent = 'Loaded current article context into the generator. You can edit it before generating.';
    }

    prompt.focus();
  }

  function renderHotArticles(articles, meta) {
    if (!hotGrid) {
      return;
    }

    hotGrid.innerHTML = '';
    if (!articles || !articles.length) {
      hotGrid.innerHTML = '<p class="sp-muted">No cached hot articles yet. Refresh to build the cache.</p>';
      updateHotMeta(meta, 0);
      return;
    }

    articles.forEach(function (article) {
      const card = document.createElement('div');
      card.className = 'sp-article-card';
      const date = formatPublishedDate(article.published_at);
      const excerpt = article.excerpt ? article.excerpt.substring(0, 180) + (article.excerpt.length > 180 ? '…' : '') : '';
      const thumb = article.image_url
        ? '<img src="' + esc(article.image_url) + '" alt="" loading="lazy" class="sp-article-thumb">'
        : '<div class="sp-article-no-thumb"></div>';

      card.innerHTML =
        '<div class="sp-article-card-inner">' +
          thumb +
          '<div class="sp-article-meta">' +
            '<div class="sp-article-title">' + esc(article.title) + '</div>' +
            (excerpt ? '<div class="sp-article-excerpt">' + esc(excerpt) + '</div>' : '') +
            '<div class="sp-article-footer">' +
              (article.source_name ? '<span class="sp-hot-source">' + esc(article.source_name) + '</span>' : '') +
              (date ? '<span class="sp-article-date sp-muted">' + esc(date) + '</span>' : '') +
              (article.hot_score ? '<span class="sp-hot-score">' + esc(Math.round(article.hot_score)) + '</span>' : '') +
            '</div>' +
            '<div class="sp-actions" style="margin-top:12px;">' +
              (article.url ? '<a class="button button--default" href="' + esc(article.url) + '" target="_blank" rel="noopener">Open Article</a>' : '') +
              '<button type="button" class="button button--primary sp-hot-use-btn">Use This Topic</button>' +
            '</div>' +
          '</div>' +
        '</div>';

      const useButton = card.querySelector('.sp-hot-use-btn');
      if (useButton) {
        useButton.addEventListener('click', function () {
          applyHotArticle(article);
        });
      }
      card.addEventListener('dblclick', function () {
        applyHotArticle(article);
      });
      hotGrid.appendChild(card);
    });

    updateHotMeta(meta, articles.length);
  }

  function setInternalLink(url, title) {
    const el = document.getElementById('sp-internal');
    el.innerHTML = '';
    if (!url) {
      return;
    }

    const a = document.createElement('a');
    a.href = url;
    a.textContent = title || url;
    a.target = '_blank';
    a.rel = 'noopener';
    el.appendChild(a);
  }

  function splitInstagramPostText(text) {
    const value = (text || '').trim();
    if (!value) {
      return {slides: '', caption: ''};
    }

    const parts = value.split(/\n\s*(?:#{1,6}\s*)?Instagram Caption:?\s*\n/i);
    if (parts.length < 2) {
      return {slides: value, caption: ''};
    }

    return {
      slides: (parts.shift() || '').trim(),
      caption: parts.join("\n\n").trim()
    };
  }

  function historyItemUrl(id) {
    if (historyItemUrlPattern) {
      return historyItemUrlPattern.replace('__ID__', String(id));
    }

    return historyUrl + '/' + String(id);
  }

  function buildGeneralPrompt() {
    const sections = [];
    const title = planTitle.value.trim();
    const brief = planBrief.value.trim();
    const keywords = planKeywords.value.trim();
    const extra = prompt.value.trim();
    const notes = authorNotes ? authorNotes.value.trim() : '';

    if (title) {
      sections.push('Working title: ' + title);
    }
    if (brief) {
      sections.push('Brief: ' + brief);
    }
    if (keywords) {
      sections.push('Keywords: ' + keywords);
    }
    if (notes) {
      sections.push('Author notes and experience:\n' + notes);
    }
    if (extra) {
      sections.push('User prompt:\n' + extra);
    }

    return sections.join("\n\n").trim();
  }

  function showResult() {
    if (!output) {
      return;
    }
    output.classList.remove('is-hidden');
    output.scrollIntoView({behavior: 'smooth', block: 'start'});
  }

  function currentStarter() {
    const list = starters[template.value] || [];
    return list[parseInt(starter.value || '0', 10)] || null;
  }

  function applyStarter(force) {
    const selected = currentStarter();
    if (!selected) {
      return;
    }

    if (force || !promptTouched || prompt.value.trim() === '') {
      prompt.value = selected.prompt || '';
      promptTouched = false;
    }

    if (force || !imageBriefTouched || imageBrief.value.trim() === '') {
      imageBrief.value = selected.image_brief || '';
      imageBriefTouched = false;
    }
  }

  function refreshStarters() {
    const list = starters[template.value] || [];
    starter.innerHTML = '';
    list.forEach(function (item, index) {
      const option = document.createElement('option');
      option.value = String(index);
      option.textContent = item.label || ('Starter ' + (index + 1));
      starter.appendChild(option);
    });

    starter.disabled = list.length === 0;
    starterButton.disabled = list.length === 0;
    starterNote.textContent = list.length ? 'Prompt and image brief will load from the selected starter.' : 'No starters for this template yet.';
    applyStarter(false);
    syncTemplateForm();
  }

  prompt.addEventListener('input', function () {
    promptTouched = true;
  });

  imageBrief.addEventListener('input', function () {
    imageBriefTouched = true;
  });

  template.addEventListener('change', refreshStarters);
  starter.addEventListener('change', function () {
    applyStarter(false);
  });

  starterButton.addEventListener('click', function () {
    applyStarter(true);
    prompt.focus();
  });

  if (sourceManualButton) {
    sourceManualButton.addEventListener('click', function () {
      syncSourceMode('manual');
    });
  }
  if (sourceHotButton) {
    sourceHotButton.addEventListener('click', function () {
      syncSourceMode('hot');
    });
  }

  syncSourceMode('manual');

  document.getElementById('sp-generate-plan').addEventListener('click', function () {
    runPlanner('creative');
  });

  function showSlideImageProgress(count) {
    if (!slideImageGrid || !slideImagesSection) { return; }
    slideImageGrid.innerHTML = '';
    for (var i = 0; i < count; i++) {
      var slot = document.createElement('div');
      slot.className = 'sp-carousel-image-slot';
      slot.id = 'sp-slide-slot-' + i;
      slot.innerHTML = '<strong>Slide ' + (i + 1) + '</strong><span>Generating…</span>';
      slideImageGrid.appendChild(slot);
    }
    slideImagesSection.classList.remove('is-hidden');
    if (slideImageStatus) { slideImageStatus.textContent = 'Starting slide image generation…'; }
  }

  function updateSlideImageSlot(index, url, slideNumber) {
    var slot = document.getElementById('sp-slide-slot-' + index);
    if (!slot) { return; }
    var num = slideNumber || (index + 1);
    slot.className = 'sp-carousel-image-slot is-active';
    slot.innerHTML = '<strong>Slide ' + num + '</strong><img src="' + url + '" alt="Slide ' + num + '">';
  }

  function generateSlideImagesSequential(generationId, slides, index) {
    if (index >= slides.length) {
      if (slideImageStatus) { slideImageStatus.textContent = 'All ' + slides.length + ' slide images generated.'; }
      return;
    }
    if (slideImageStatus) {
      slideImageStatus.textContent = 'Generating slide image ' + (index + 1) + ' of ' + slides.length + '…';
    }
    var data = new FormData();
    data.append('csrf_token', csrfToken);
    data.append('XID', csrfToken);
    data.append('slide_image_mode', '1');
    data.append('generation_id', String(generationId));
    data.append('slide_index', String(index));
    data.append('slide_data', JSON.stringify(slides[index]));
    fetch(form.action, {
      method: 'POST',
      body: data,
      credentials: 'same-origin',
      headers: {'X-Requested-With': 'XMLHttpRequest'}
    })
      .then(function (response) { return response.json(); })
      .then(function (resp) {
        if (resp.success && resp.result && resp.result.image_url) {
          updateSlideImageSlot(index, resp.result.image_url, resp.result.slide_number);
        }
        generateSlideImagesSequential(generationId, slides, index + 1);
      })
      .catch(function () {
        generateSlideImagesSequential(generationId, slides, index + 1);
      });
  }

  refreshStarters();

  if (hotRefreshBtn) {
    hotRefreshBtn.addEventListener('click', function () {
      hotRefreshBtn.disabled = true;
      if (hotStatus) {
        hotStatus.textContent = 'Refreshing feeds and rebuilding hot article cache...';
      }

      fetch(form.action, {
        method: 'POST',
        body: new URLSearchParams({
          csrf_token: csrfToken,
          XID: csrfToken,
          hot_news: '1',
          refresh: '1'
        }),
        credentials: 'same-origin',
        headers: {'X-Requested-With': 'XMLHttpRequest'}
      })
        .then(function (response) { return response.json(); })
        .then(function (json) {
          hotRefreshBtn.disabled = false;
          if (!json.success) {
            if (hotStatus) {
              hotStatus.textContent = 'Error: ' + (json.message || 'unknown');
            }
            return;
          }

          if (hotStatus) {
            hotStatus.textContent = json.errors && json.errors.length
              ? 'Refreshed with ' + json.errors.length + ' source warning(s).'
              : 'Hot articles refreshed.';
          }
          renderHotArticles(json.articles || [], json.meta || {});
        })
        .catch(function (error) {
          hotRefreshBtn.disabled = false;
          if (hotStatus) {
            hotStatus.textContent = 'Failed: ' + error.message;
          }
        });
    });
  }

  renderHotArticles(initialHotArticles || [], initialHotMeta || {});

  form.addEventListener('submit', function (event) {
    event.preventDefault();
    const formData = new FormData(form);

    if (isInstagramPage || isInstagramTemplate()) {
      formData.set('prompt', buildInstagramPrompt());
      formData.set('image_brief', carouselImagePlan() || 'Square Instagram carousel cover / visual system, no logos, no readable text.');
    } else {
      const assembledPrompt = buildGeneralPrompt();
      if (!assembledPrompt) {
        status.textContent = 'Add a brief, title, keywords, or optional instructions first.';
        return;
      }
      formData.set('prompt', assembledPrompt);
    }

    status.textContent = 'Generating...';
    submit.disabled = true;
    submit.textContent = 'Generating...';
    resultActions.classList.add('is-hidden');
    message.textContent = '';

    fetch(form.action, {
      method: 'POST',
      body: formData,
      credentials: 'same-origin',
      headers: {'X-Requested-With': 'XMLHttpRequest'}
    })
      .then(function (response) {
        return response.text().then(function (body) {
          let data = null;
          try {
            data = body ? JSON.parse(body) : null;
          } catch (error) {
            data = null;
          }

          if (!response.ok || !data) {
            const fallback = response.status === 403
              ? 'Request blocked. Refresh the page and try again.'
              : 'Request failed. Check your settings and try again.';
            throw new Error((data && data.message) || fallback);
          }

          return data;
        });
      })
      .then(function (data) {
        showResult();
        if (!data.success) {
          message.textContent = data.message || 'Unable to generate post.';
          status.textContent = '';
          submit.disabled = false;
          submit.textContent = 'Generate';
          return;
        }

        const result = data.result || {};
        const instagramResult = splitInstagramPostText(result.post_text || '');
        const showingInstagramResult = isInstagramPage || isInstagramTemplate();
        document.getElementById('sp-title').textContent = result.title || 'Generated social post';
        document.getElementById('sp-result-meta').textContent = [result.category || '', (result.keywords || []).slice(0, 3).join(', ')].filter(Boolean).join(' · ');
        document.getElementById('sp-post').value = result.post_text || '';
        document.getElementById('sp-instagram-slides').value = instagramResult.slides;
        document.getElementById('sp-instagram-caption').value = instagramResult.caption;
        if (instagramResultFields && generalResultFields) {
          instagramResultFields.classList.toggle('is-hidden', !showingInstagramResult);
          generalResultFields.classList.toggle('is-hidden', showingInstagramResult);
        }
        if (generalSummaryFields) {
          generalSummaryFields.classList.toggle('is-hidden', showingInstagramResult);
        }
        if (generalExtraFields) {
          generalExtraFields.classList.toggle('is-hidden', showingInstagramResult);
        }
        document.getElementById('sp-intro').value = result.intro_text || '';
        document.getElementById('sp-keywords').textContent = (result.keywords || []).join(', ');
        document.getElementById('sp-category').textContent = result.category || '';
        document.getElementById('sp-hashtags').textContent = (result.hashtags || []).join(' ');
        document.getElementById('sp-external').textContent = result.external_link || '';
        setInternalLink(result.internal_link || '', result.internal_link_title || '');
        document.getElementById('sp-image-brief').value = result.image_brief || '';
        setList('sp-toc', result.table_of_contents);
        setList('sp-topics', result.recommended_topics);

        const img = document.getElementById('sp-image');
        if (result.image_url) {
          img.src = result.image_url;
          img.alt = result.title || 'Generated social post image';
          img.classList.remove('is-hidden');
        }
        if (result.id) {
          editLink.href = historyItemUrl(result.id);
          resultActions.classList.remove('is-hidden');
        }
        status.textContent = 'Done';
        submit.disabled = false;
        submit.textContent = 'Generate';

        var instagramSlides = result.instagram_slides || [];
        if ((isInstagramPage || isInstagramTemplate()) && instagramSlides.length > 0 && result.id) {
          showSlideImageProgress(instagramSlides.length);
          generateSlideImagesSequential(result.id, instagramSlides, 0);
        }
      })
      .catch(function (error) {
        showResult();
        message.textContent = error && error.message ? error.message : 'Request failed. Check your settings and try again.';
        status.textContent = '';
        submit.disabled = false;
        submit.textContent = 'Generate';
      });
  });
})();
</script>
