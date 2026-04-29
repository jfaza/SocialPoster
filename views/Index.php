<?php
ee()->load->helper('form');
$h = fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$pageMode = ($page_mode ?? 'general') === 'instagram' ? 'instagram' : 'general';
$isInstagramPage = $pageMode === 'instagram';
$selectedTemplateId = (int) ($selected_template_id ?? 0);
$generalFieldsClass = $isInstagramPage ? 'is-hidden' : '';
$instagramBuilderClass = $isInstagramPage ? 'sp-instagram-builder' : 'sp-instagram-builder is-hidden';
?>
<div class="sp-wrap">
  <div class="sp-toolbar">
    <?php if ($isInstagramPage): ?>
      <a class="button button--default" href="<?= $h($general_url) ?>">General Generator</a>
      <a class="button button--default" href="<?= $h($instagram_settings_url) ?>">Instagram Settings</a>
    <?php else: ?>
      <a class="button button--default" href="<?= $h($general_settings_url) ?>">Settings</a>
      <a class="button button--default" href="<?= $h($instagram_generate_url) ?>">Instagram Generate</a>
    <?php endif; ?>
    <a class="button button--default" href="<?= $h($history_url) ?>"><?= $isInstagramPage ? 'Generated Posts' : 'History' ?></a>
    <a class="button button--default" href="<?= $h($templates_url) ?>">Templates</a>
  </div>

  <div class="tab-wrap sp-native-tabs">
    <div class="tab-bar">
      <div class="tab-bar__tabs">
        <button type="button" class="tab-bar__tab js-tab-button active" rel="sp-generate-tab">Generate</button>
        <?php if (! $isInstagramPage): ?>
          <button type="button" id="sp-result-tab-button" class="tab-bar__tab js-tab-button" rel="sp-result-tab">Result</button>
        <?php endif; ?>
      </div>
    </div>

    <section class="sp-card tab sp-generate-tab tab-open">
      <h2><?= $isInstagramPage ? 'Generate Instagram Post' : 'Generate Social Post' ?></h2>
      <p class="sp-muted">
        <?= $isInstagramPage
            ? 'Pick an Instagram template and build the carousel prompt, caption, and image plan for that post.'
            : 'Describe the topic, audience, tone, and any links or portfolio context to include.' ?>
      </p>
      <form id="socialposter-form" method="post" action="<?= $h($action_url) ?>">
        <input type="hidden" name="csrf_token" value="<?= $h($csrf_token) ?>">
        <input type="hidden" name="XID" value="<?= $h($csrf_token) ?>">
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

        <div class="sp-planning-panel">
          <h3><?= $isInstagramPage ? 'Content Planning' : 'Planning' ?></h3>
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
          <div class="sp-actions">
            <button type="button" id="sp-generate-title" class="button button--default">Generate Title</button>
            <button type="button" id="sp-generate-brief" class="button button--default">Generate Brief</button>
            <button type="button" id="sp-generate-images" class="button button--default"><?= $isInstagramPage ? 'Build Carousel Brief' : 'Generate Image Descriptions' ?></button>
            <span id="sp-plan-status" class="sp-muted"></span>
          </div>
        </div>

        <div id="sp-instagram-builder" class="<?= $h($instagramBuilderClass) ?>">
          <h3>Instagram Carousel Plan</h3>
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

        <div id="sp-general-fields" class="<?= $h($generalFieldsClass) ?>">
          <div class="sp-field">
            <label for="socialposter-prompt">Prompt</label>
            <textarea id="socialposter-prompt" name="prompt" rows="10" required placeholder="Example: Create a post about my new ExpressionEngine portfolio project for web developers and potential clients."></textarea>
          </div>
          <div class="sp-field">
            <label for="socialposter-image-brief">Image Brief</label>
            <textarea id="socialposter-image-brief" name="image_brief" rows="4" placeholder="Example: editorial hero image, no text, clean workspace, natural light, confident but not corporate."></textarea>
          </div>
        </div>
        <div class="sp-actions">
          <button id="socialposter-submit" type="submit" class="button button--primary">Generate</button>
          <span id="socialposter-status" class="sp-muted"></span>
        </div>
      </form>
    </section>

    <section id="socialposter-output" class="sp-card sp-output tab sp-result-tab">
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
</div>

<script>
(function () {
  const form = document.getElementById('socialposter-form');
  const status = document.getElementById('socialposter-status');
  const submit = document.getElementById('socialposter-submit');
  const output = document.getElementById('socialposter-output');
  const generateTab = document.querySelector('.sp-generate-tab');
  const resultTabButton = document.getElementById('sp-result-tab-button');
  const message = document.getElementById('sp-message');
  const prompt = document.getElementById('socialposter-prompt');
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
  let promptTouched = false;
  let imageBriefTouched = false;

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
        if (mode === 'title' || !planTitle.value.trim()) {
          planTitle.value = result.title || planTitle.value;
        }
        if (mode === 'brief' || !planBrief.value.trim()) {
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
        planStatus.textContent = mode === 'creative' ? 'Carousel brief ready' : 'Done';
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

  function openResultTab() {
    if (isInstagramPage) {
      output.classList.add('tab-open');
      output.scrollIntoView({behavior: 'smooth', block: 'start'});
      return;
    }

    document.querySelectorAll('.sp-native-tabs .tab').forEach(function (tab) {
      tab.classList.remove('tab-open');
    });
    document.querySelectorAll('.sp-native-tabs .js-tab-button').forEach(function (button) {
      button.classList.remove('active');
    });
    output.classList.add('tab-open');
    if (resultTabButton) {
      resultTabButton.classList.add('active');
    }
  }

  function openGenerateTab() {
    document.querySelectorAll('.sp-native-tabs .tab').forEach(function (tab) {
      tab.classList.remove('tab-open');
    });
    document.querySelectorAll('.sp-native-tabs .js-tab-button').forEach(function (button) {
      button.classList.remove('active');
    });
    if (generateTab) {
      generateTab.classList.add('tab-open');
    }
    const generateButton = document.querySelector('[rel="sp-generate-tab"]');
    if (generateButton) {
      generateButton.classList.add('active');
    }
  }

  document.querySelectorAll('.sp-native-tabs .js-tab-button').forEach(function (button) {
    button.addEventListener('click', function () {
      const targetClass = button.getAttribute('rel');
      document.querySelectorAll('.sp-native-tabs .tab').forEach(function (tab) {
        tab.classList.toggle('tab-open', tab.classList.contains(targetClass));
      });
      document.querySelectorAll('.sp-native-tabs .js-tab-button').forEach(function (tabButton) {
        tabButton.classList.toggle('active', tabButton === button);
      });
    });
  });

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

  document.getElementById('sp-generate-title').addEventListener('click', function () {
    runPlanner('title');
  });
  document.getElementById('sp-generate-brief').addEventListener('click', function () {
    runPlanner('brief');
  });
  document.getElementById('sp-generate-images').addEventListener('click', function () {
    runPlanner(isInstagramPage ? 'creative' : 'images');
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

  form.addEventListener('submit', function (event) {
    event.preventDefault();
    if (isInstagramPage || isInstagramTemplate()) {
      prompt.value = buildInstagramPrompt();
      imageBrief.value = carouselImagePlan() || 'Square Instagram carousel cover / visual system, no logos, no readable text.';
    } else {
      const planningLines = [];
      if (planTitle.value.trim()) {
        planningLines.push('Working title: ' + planTitle.value.trim());
      }
      if (planBrief.value.trim()) {
        planningLines.push('Brief: ' + planBrief.value.trim());
      }
      if (planKeywords.value.trim()) {
        planningLines.push('Keywords: ' + planKeywords.value.trim());
      }
      if (planningLines.length) {
        prompt.value = planningLines.join("\n") + "\n\nUser prompt:\n" + prompt.value;
      }
    }
    status.textContent = 'Generating...';
    submit.disabled = true;
    submit.textContent = 'Generating...';
    openGenerateTab();
    resultActions.classList.add('is-hidden');
    message.textContent = '';

    fetch(form.action, {
      method: 'POST',
      body: new FormData(form),
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
        openResultTab();
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
        openResultTab();
        message.textContent = error && error.message ? error.message : 'Request failed. Check your settings and try again.';
        status.textContent = '';
        submit.disabled = false;
        submit.textContent = 'Generate';
      });
  });
})();
</script>
