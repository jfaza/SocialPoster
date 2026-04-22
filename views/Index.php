<?php
ee()->load->helper('form');
$h = fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<div class="sp-wrap">
  <div class="sp-toolbar">
    <a class="button button--default" href="<?= $h($settings_url) ?>">Settings</a>
    <a class="button button--default" href="<?= $h($history_url) ?>">History</a>
    <a class="button button--default" href="<?= $h($templates_url) ?>">Templates</a>
  </div>

  <div class="sp-grid sp-grid-generate">
    <section class="sp-card">
      <h2>Generate Social Post</h2>
      <p class="sp-muted">Describe the topic, audience, tone, and any links or portfolio context to include.</p>
      <form id="socialposter-form" method="post" action="<?= $h($action_url) ?>">
        <input type="hidden" name="csrf_token" value="<?= $h($csrf_token) ?>">
        <input type="hidden" name="XID" value="<?= $h($csrf_token) ?>">
        <div class="sp-field">
          <label for="socialposter-template">Template</label>
          <select id="socialposter-template" name="template_id">
            <?php foreach ($template_options as $value => $label): ?>
              <option value="<?= (int) $value ?>"><?= $h($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="sp-field">
          <label for="socialposter-prompt">Prompt</label>
          <textarea id="socialposter-prompt" name="prompt" rows="10" required placeholder="Example: Create a post about my new ExpressionEngine portfolio project for web developers and potential clients."></textarea>
        </div>
        <div class="sp-prompt-tools" aria-label="Prompt starters">
          <button type="button" class="button button--default sp-prompt-chip" data-prompt="Create an SEO article about [topic] for [audience]. Include practical examples, related internal blog links when relevant, a matching blog category, and a soft consultation CTA.">SEO Article</button>
          <button type="button" class="button button--default sp-prompt-chip" data-prompt="Create a LinkedIn authority post about [topic] for founders and marketing teams. Use a practical hook, one clear takeaway, and a conversation-starting CTA.">LinkedIn</button>
          <button type="button" class="button button--default sp-prompt-chip" data-prompt="Create a how-to tutorial about [topic]. Include prerequisites, step-by-step guidance, common mistakes, validation checks, and related internal links when relevant.">How-To</button>
        </div>
        <div class="sp-field">
          <label for="socialposter-image-brief">Image Brief</label>
          <textarea id="socialposter-image-brief" name="image_brief" rows="4" placeholder="Example: editorial hero image, no text, clean workspace, natural light, confident but not corporate."></textarea>
        </div>
        <div class="sp-actions">
          <button id="socialposter-submit" type="submit" class="button button--primary">Generate</button>
          <span id="socialposter-status" class="sp-muted"></span>
        </div>
      </form>
    </section>

    <section id="socialposter-output" class="sp-card sp-output">
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
        <div class="sp-summary">
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
      <h3>Post Text</h3>
      <textarea id="sp-post" rows="8" readonly></textarea>
      <h3>Intro Text</h3>
      <textarea id="sp-intro" rows="4" readonly></textarea>
      <h3>Table of Contents</h3>
      <ul id="sp-toc" class="sp-list"></ul>
      <h3>Recommended Topics</h3>
      <ul id="sp-topics" class="sp-list"></ul>
    </section>
  </div>
</div>

<script>
(function () {
  const form = document.getElementById('socialposter-form');
  const status = document.getElementById('socialposter-status');
  const submit = document.getElementById('socialposter-submit');
  const output = document.getElementById('socialposter-output');
  const message = document.getElementById('sp-message');
  const prompt = document.getElementById('socialposter-prompt');
  const resultActions = document.getElementById('sp-result-actions');
  const editLink = document.getElementById('sp-edit-link');
  const historyUrl = <?= json_encode((string) $history_url) ?>;

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

  document.querySelectorAll('.sp-prompt-chip').forEach(function (button) {
    button.addEventListener('click', function () {
      prompt.value = button.dataset.prompt || '';
      prompt.focus();
    });
  });

  form.addEventListener('submit', function (event) {
    event.preventDefault();
    status.textContent = 'Generating...';
    submit.disabled = true;
    submit.textContent = 'Generating...';
    output.classList.remove('is-visible');
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
        output.classList.add('is-visible');
        if (!data.success) {
          message.textContent = data.message || 'Unable to generate post.';
          status.textContent = '';
          submit.disabled = false;
          submit.textContent = 'Generate';
          return;
        }

        const result = data.result || {};
        document.getElementById('sp-title').textContent = result.title || 'Generated social post';
        document.getElementById('sp-result-meta').textContent = [result.category || '', (result.keywords || []).slice(0, 3).join(', ')].filter(Boolean).join(' · ');
        document.getElementById('sp-post').value = result.post_text || '';
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
          editLink.href = historyUrl + '/' + result.id;
          resultActions.classList.remove('is-hidden');
        }
        status.textContent = 'Done';
        submit.disabled = false;
        submit.textContent = 'Generate';
      })
      .catch(function (error) {
        output.classList.add('is-visible');
        message.textContent = error && error.message ? error.message : 'Request failed. Check your settings and try again.';
        status.textContent = '';
        submit.disabled = false;
        submit.textContent = 'Generate';
      });
  });
})();
</script>
