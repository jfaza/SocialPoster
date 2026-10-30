<?php
ee()->load->helper('form');
$spView = require __DIR__ . '/_view_helpers.php';
$h = fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
$editingSchedule = $editing_schedule ?? null;
$editorActive = ! empty($editor_active);
$scheduleFilter = (string) ($schedule_filter ?? 'all');
$scheduleFilterCounts = is_array($schedule_filter_counts ?? null) ? $schedule_filter_counts : [];
$scheduleForm = $schedule_form ?? [
    'id' => 0,
    'title' => '',
    'template_id' => 0,
    'next_run_at' => time(),
    'planned_topics' => '',
    'recurrence_mode' => 'repeat',
    'recurrence_interval' => 1,
    'recurrence_unit' => 'week',
    'weekdays' => [],
];
$scheduleRunAt = (int) ($scheduleForm['next_run_at'] ?? time());
$scheduleRunAt = $scheduleRunAt > 0 ? $scheduleRunAt : time();
$calendarItemUrlPattern = (string) ($calendar_item_url_pattern ?? '');
?>
<div class="sp-wrap">
  <div class="sp-toolbar">
    <a class="button button--default" href="<?= $h($history_url) ?>">History</a>
    <a class="button button--default" href="<?= $h($templates_url) ?>">Template Library</a>
    <?= form_open($calendar_url, ['class' => 'sp-inline-form']) ?>
      <input type="hidden" name="run_due" value="1">
      <button type="submit" class="button button--primary">Run Due Now</button>
    <?= form_close() ?>
  </div>

  <div class="tab-wrap sp-native-tabs">
    <div class="tab-bar">
      <div class="tab-bar__tabs">
        <button type="button" class="tab-bar__tab js-tab-button <?= ! $editorActive ? 'active' : '' ?>" rel="t-0">Calendar</button>
        <button type="button" class="tab-bar__tab js-tab-button <?= $editorActive ? 'active' : '' ?>" rel="t-1"><?= $editingSchedule ? 'Edit Schedule' : 'Create Schedule' ?></button>
        <button type="button" class="tab-bar__tab js-tab-button" rel="t-2">Schedules</button>
      </div>
    </div>

    <section class="sp-card tab t-0 <?= ! $editorActive ? 'tab-open' : '' ?>">
      <div class="sp-actions sp-calendar-head">
        <a class="button button--default" href="<?= $h($prev_url) ?>#tab=t-0">Previous</a>
        <h2 class="sp-month-title"><?= $h($month_label) ?></h2>
        <a class="button button--default" href="<?= $h($next_url) ?>#tab=t-0">Next</a>
      </div>
      <div class="sp-kind-legend">
        <span class="sp-kind-legend-item"><span class="sp-kind-tag sp-kind-tag-b">B</span> Blog</span>
        <span class="sp-kind-legend-item"><span class="sp-kind-tag sp-kind-tag-i">I</span> Instagram</span>
        <span class="sp-kind-legend-item"><span class="sp-kind-tag sp-kind-tag-ic">IC</span> Carousel</span>
        <span class="sp-kind-legend-item"><span class="sp-kind-tag sp-kind-tag-in">IN</span> News</span>
        <span class="sp-kind-legend-item sp-kind-legend-future"><span class="sp-kind-tag sp-kind-tag-f">F</span> Facebook</span>
        <span class="sp-kind-legend-item sp-kind-legend-future"><span class="sp-kind-tag sp-kind-tag-x">X</span> X / Twitter</span>
      </div>
      <table class="sp-calendar">
        <thead>
          <tr>
            <?php foreach ($weekdays as $day): ?><th><?= $h($day) ?></th><?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($weeks as $week): ?>
            <tr>
              <?php foreach ($week as $cell): ?>
                <td>
                  <?php if (! empty($cell['day'])): ?>
                    <div class="sp-day"><?= (int) $cell['day'] ?></div>
                    <?php foreach ($cell['events'] as $event): ?>
                      <?php $kind = $event['kind'] ?? 'B'; ?>
                      <?php if (! empty($event['url'])): ?>
                        <a class="sp-event <?= $h($event['type']) ?> sp-kind-<?= $h(strtolower($kind)) ?> <?= empty($event['active']) ? 'inactive' : '' ?>" href="<?= $h($event['url']) ?>">
                          <span class="sp-kind-tag"><?= $h($kind) ?></span>
                          <?= $h($event['time']) ?> · <?= $h($event['title']) ?>
                          <?php if (! empty($event['meta'])): ?>
                            <small class="sp-event-meta"><?= $h($event['meta']) ?></small>
                          <?php endif; ?>
                        </a>
                      <?php else: ?>
                        <span class="sp-event <?= $h($event['type']) ?> sp-kind-<?= $h(strtolower($kind)) ?> <?= empty($event['active']) ? 'inactive' : '' ?>">
                          <span class="sp-kind-tag"><?= $h($kind) ?></span>
                          <?= $h($event['time']) ?> · <?= $h($event['title']) ?>
                          <?php if (! empty($event['meta'])): ?>
                            <small class="sp-event-meta"><?= $h($event['meta']) ?></small>
                          <?php endif; ?>
                        </span>
                      <?php endif; ?>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </td>
              <?php endforeach; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <div class="sp-calendar-list">
        <?php $hasCalendarEvents = false; ?>
        <?php foreach ($weeks as $week): ?>
          <?php foreach ($week as $cell): ?>
            <?php if (! empty($cell['day']) && ! empty($cell['events'])): ?>
              <?php $hasCalendarEvents = true; ?>
              <section class="sp-calendar-list-day">
                <h3><?= $h(date('D, M j', strtotime((string) $cell['date']))) ?></h3>
                <?php foreach ($cell['events'] as $event): ?>
                  <?php $kind = $event['kind'] ?? 'B'; ?>
                  <?php if (! empty($event['url'])): ?>
                    <a class="sp-event <?= $h($event['type']) ?> sp-kind-<?= $h(strtolower($kind)) ?> <?= empty($event['active']) ? 'inactive' : '' ?>" href="<?= $h($event['url']) ?>">
                      <span class="sp-kind-tag"><?= $h($kind) ?></span>
                      <strong><?= $h($event['time']) ?></strong>
                      <span>
                        <?= $h($event['title']) ?>
                        <?php if (! empty($event['meta'])): ?>
                          <small class="sp-event-meta sp-event-meta-inline"><?= $h($event['meta']) ?></small>
                        <?php endif; ?>
                      </span>
                    </a>
                  <?php else: ?>
                    <span class="sp-event <?= $h($event['type']) ?> sp-kind-<?= $h(strtolower($kind)) ?> <?= empty($event['active']) ? 'inactive' : '' ?>">
                      <span class="sp-kind-tag"><?= $h($kind) ?></span>
                      <strong><?= $h($event['time']) ?></strong>
                      <span>
                        <?= $h($event['title']) ?>
                        <?php if (! empty($event['meta'])): ?>
                          <small class="sp-event-meta sp-event-meta-inline"><?= $h($event['meta']) ?></small>
                        <?php endif; ?>
                      </span>
                    </span>
                  <?php endif; ?>
                <?php endforeach; ?>
              </section>
            <?php endif; ?>
          <?php endforeach; ?>
        <?php endforeach; ?>
        <?php if (! $hasCalendarEvents): ?>
          <p class="sp-muted">No calendar items this month.</p>
        <?php endif; ?>
      </div>
    </section>

    <section class="sp-card tab t-1 <?= $editorActive ? 'tab-open' : '' ?>">
      <h2><?= $editingSchedule ? 'Edit Schedule' : 'Schedule Generation' ?></h2>
      <p class="sp-muted">Choose a generation template, set the cadence, and SocialPoster will create each post from that template.</p>
      <?= form_open($save_url ?? $calendar_url) ?>
        <input type="hidden" name="save_schedule" value="1">
        <input type="hidden" name="schedule_id" value="<?= (int) ($scheduleForm['id'] ?? 0) ?>">
        <div class="sp-field">
          <label for="sp-template">Generation Template</label>
          <select id="sp-template" name="template_id" required>
            <?php foreach ($template_options as $value => $label): ?>
              <option value="<?= (int) $value ?>" <?= (int) ($scheduleForm['template_id'] ?? 0) === (int) $value ? 'selected' : '' ?>><?= $h($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="sp-field">
          <div class="sp-template-preview-head">
            <label>Selected Template</label>
            <a id="sp-template-edit-link" class="button button--default" href="<?= $h($templates_url) ?>">Open Template Editor</a>
          </div>
          <div id="sp-template-preview" class="sp-template-preview">
            <div class="sp-field">
              <label>Template Name</label>
              <div id="sp-preview-title" class="sp-template-value"></div>
            </div>

            <div class="sp-mini-grid">
              <div class="sp-field">
                <label>Content Type</label>
                <div id="sp-preview-content-type" class="sp-template-value"></div>
              </div>
              <div class="sp-field">
                <label>Platform</label>
                <div id="sp-preview-platform" class="sp-template-value"></div>
              </div>
              <div class="sp-field">
                <label>Length</label>
                <div id="sp-preview-length" class="sp-template-value"></div>
              </div>
            </div>

            <div class="sp-mini-grid">
              <div class="sp-field">
                <label>Word Count</label>
                <div id="sp-preview-word-count" class="sp-template-value"></div>
              </div>
              <div class="sp-field">
                <label>Citations</label>
                <div id="sp-preview-citations" class="sp-template-value"></div>
              </div>
              <div class="sp-field">
                <label>Schema</label>
                <div id="sp-preview-schema" class="sp-template-value"></div>
              </div>
            </div>

            <div class="sp-mini-grid">
              <div class="sp-field">
                <label>Internal Links</label>
                <div id="sp-preview-internal-links" class="sp-template-value"></div>
              </div>
              <div class="sp-field">
                <label>External Links</label>
                <div id="sp-preview-external-links" class="sp-template-value"></div>
              </div>
              <div class="sp-field">
                <label>Default</label>
                <div id="sp-preview-default" class="sp-template-value"></div>
              </div>
            </div>

            <div class="sp-field">
              <label>Tone</label>
              <div id="sp-preview-tone" class="sp-template-value"></div>
            </div>
            <div class="sp-field">
              <label>Audience</label>
              <div id="sp-preview-audience" class="sp-template-value"></div>
            </div>
            <div class="sp-field">
              <label>Goal</label>
              <div id="sp-preview-goal" class="sp-template-value"></div>
            </div>
            <div class="sp-field">
              <label>Research Mode</label>
              <div id="sp-preview-research" class="sp-template-value"></div>
            </div>
            <div class="sp-field">
              <label>Image Style</label>
              <div id="sp-preview-image-style" class="sp-template-value"></div>
            </div>
            <div class="sp-field">
              <label>CTA Style</label>
              <div id="sp-preview-cta-style" class="sp-template-value"></div>
            </div>
            <div class="sp-field">
              <label>Prompt Instructions</label>
              <pre id="sp-preview-instructions" class="sp-template-doc"></pre>
            </div>
          </div>
        </div>
        <div class="sp-field">
          <label for="sp-topic-starter">Topic / Instruction Starter</label>
          <select id="sp-topic-starter"></select>
        </div>
        <div class="sp-actions">
          <button type="button" id="sp-add-topic-starter" class="button button--default">Add Starter to Planned Topics</button>
          <span id="sp-topic-starter-note" class="sp-muted"></span>
        </div>
        <div class="sp-mini-grid sp-schedule-recurrence-grid">
          <div class="sp-field">
            <label for="sp-recurrence-mode">Schedule Type</label>
            <select id="sp-recurrence-mode" name="recurrence_mode">
              <?php foreach (($recurrence_modes ?? []) as $value => $label): ?>
                <option value="<?= $h($value) ?>" <?= (string) ($scheduleForm['recurrence_mode'] ?? 'repeat') === (string) $value ? 'selected' : '' ?>><?= $h($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="sp-field">
            <label for="sp-recurrence-interval">Interval</label>
            <input id="sp-recurrence-interval" name="recurrence_interval" type="number" min="1" step="1" value="<?= (int) ($scheduleForm['recurrence_interval'] ?? 1) ?>">
          </div>
          <div class="sp-field">
            <label for="sp-recurrence-unit">Unit</label>
            <select id="sp-recurrence-unit" name="recurrence_unit">
              <?php foreach (($recurrence_units ?? []) as $value => $label): ?>
                <option value="<?= $h($value) ?>" <?= (string) ($scheduleForm['recurrence_unit'] ?? 'week') === (string) $value ? 'selected' : '' ?>><?= $h($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div id="sp-weekday-field" class="sp-field">
          <label>Run On</label>
          <div class="sp-checkbox-group sp-weekday-checks">
            <?php foreach (($weekday_options ?? []) as $value => $label): ?>
              <label class="sp-checkbox-label">
                <input type="checkbox" name="weekdays[]" value="<?= (int) $value ?>" <?= in_array((int) $value, (array) ($scheduleForm['weekdays'] ?? []), true) ? 'checked' : '' ?>>
                <?= $h($label) ?>
              </label>
            <?php endforeach; ?>
          </div>
          <p class="sp-muted">Choose one or more weekdays for weekly repeats.</p>
        </div>
        <input type="hidden" id="sp-frequency" name="frequency" value="">
        <div class="sp-field">
          <label for="sp-title">Calendar Title</label>
          <input id="sp-title" name="title" type="text" value="<?= $h($scheduleForm['title'] ?? '') ?>" placeholder="Optional. Defaults to template name.">
        </div>
        <div class="sp-field">
          <label for="sp-start-date"><?= $editingSchedule ? 'Next Run Date' : 'First Run Date' ?></label>
          <input id="sp-start-date" name="start_date" type="date" value="<?= $h(date('Y-m-d', $scheduleRunAt)) ?>">
        </div>
        <div class="sp-field">
          <label for="sp-start-time"><?= $editingSchedule ? 'Next Run Time' : 'First Run Time' ?></label>
          <input id="sp-start-time" name="start_time" type="time" value="<?= $h(date('H:i', $scheduleRunAt)) ?>">
        </div>
        <div class="sp-field">
          <label for="sp-planned-topics">Planned Topics</label>
          <textarea id="sp-planned-topics" name="planned_topics" rows="8" placeholder="Optional. One topic or instruction per line. If this is blank, the scheduler uses the selected template instructions to choose a fresh topic each run."><?= $h($scheduleForm['planned_topics'] ?? '') ?></textarea>
          <p class="sp-muted">The selected template is the stored instruction set. Planned Topics is only an optional queue for forcing the next scheduled runs to use specific topics.</p>
        </div>
        <div class="sp-actions">
          <button type="submit" class="button button--primary"><?= $editingSchedule ? 'Save Schedule' : 'Add Schedule' ?></button>
          <?php if ($editingSchedule): ?>
            <a class="button button--default" href="<?= $h($calendar_url) ?>#tab=t-2">Cancel</a>
          <?php endif; ?>
        </div>
      <?= form_close() ?>

      <?php if ($editingSchedule): ?>
        <div class="sp-schedule-runs">
          <div class="sp-schedule-run__head">
            <h3>Recent Runs</h3>
            <div class="sp-actions">
              <?= form_open($calendar_url, ['class' => 'sp-inline-form']) ?>
                <input type="hidden" name="run_schedule_now" value="<?= (int) ($scheduleForm['id'] ?? 0) ?>">
                <button type="submit" class="button button--default">Run Now</button>
              <?= form_close() ?>
              <?php if (in_array((string) ($editingSchedule['last_status'] ?? ''), ['failed', 'duplicate_exhausted', 'image_failed'], true)): ?>
                <?= form_open($calendar_url, ['class' => 'sp-inline-form']) ?>
                  <input type="hidden" name="retry_failed_schedule" value="<?= (int) ($scheduleForm['id'] ?? 0) ?>">
                  <button type="submit" class="button button--default">Retry Failed</button>
                <?= form_close() ?>
              <?php endif; ?>
              <?php if (trim((string) ($scheduleForm['planned_topics'] ?? '')) !== ''): ?>
                <?= form_open($calendar_url, ['class' => 'sp-inline-form']) ?>
                  <input type="hidden" name="skip_schedule_topic" value="<?= (int) ($scheduleForm['id'] ?? 0) ?>">
                  <button type="submit" class="button button--default">Skip Topic</button>
                <?= form_close() ?>
              <?php endif; ?>
            </div>
          </div>
          <?php if (empty($recent_runs)): ?>
            <p class="sp-muted">No runs recorded for this schedule yet.</p>
          <?php else: ?>
            <div class="sp-schedule-run-list">
              <?php foreach ($recent_runs as $run): ?>
                <article class="sp-schedule-run">
                  <div class="sp-schedule-run__head">
                    <strong><?= $h(date('M j, Y g:ia', (int) ($run['started_at'] ?? 0))) ?></strong>
                    <?= $spView->statusBadge(ucwords(str_replace('_', ' ', (string) ($run['status'] ?? 'failed'))), in_array((string) ($run['status'] ?? ''), ['success', 'duplicate_retry'], true) ? 'success' : 'danger', $h) ?>
                  </div>
                  <p class="sp-muted">
                    <?= $h(ucfirst((string) ($run['topic_source'] ?? 'fresh'))) ?> topic
                    <?php if ((int) ($run['duplicate_retry_count'] ?? 0) > 0): ?>
                      · <?= (int) $run['duplicate_retry_count'] ?> duplicate <?= (int) $run['duplicate_retry_count'] === 1 ? 'retry' : 'retries' ?>
                    <?php endif; ?>
                  </p>
                  <?php if (! empty($run['final_title'])): ?>
                    <p><strong>Title:</strong> <?= $h($run['final_title']) ?></p>
                  <?php endif; ?>
                  <?php if (! empty($run['message'])): ?>
                    <p class="sp-muted"><?= $h($run['message']) ?></p>
                  <?php endif; ?>
                  <?php if (! empty($run['link'])): ?>
                    <p><a href="<?= $h($run['link']['url']) ?>"><?= $h($run['link']['label']) ?></a></p>
                  <?php endif; ?>
                </article>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </section>

    <section class="sp-card tab t-2">
      <h3>Schedules</h3>
      <div class="sp-schedule-filters">
        <?php
          $filterLabels = [
            'all' => 'All',
            'needs_attention' => 'Needs Attention',
            'healthy' => 'Healthy',
            'paused' => 'Paused',
            'completed' => 'Completed',
          ];
        ?>
        <?php foreach ($filterLabels as $filterKey => $filterLabel): ?>
          <?php
            $filterUrl = $calendar_url . '?schedule_filter=' . rawurlencode($filterKey) . '#tab=t-2';
            $filterCount = (int) ($scheduleFilterCounts[$filterKey] ?? 0);
          ?>
          <a class="sp-schedule-filter<?= $scheduleFilter === $filterKey ? ' is-active' : '' ?>" href="<?= $h($filterUrl) ?>">
            <span><?= $h($filterLabel) ?></span>
            <span class="sp-schedule-filter__count"><?= $filterCount ?></span>
          </a>
        <?php endforeach; ?>
      </div>
      <?php if (empty($schedules)): ?>
        <p class="sp-muted"><?= $scheduleFilter === 'all' ? 'No schedules yet.' : 'No schedules match this filter.' ?></p>
      <?php endif; ?>
      <?php foreach ($schedules as $schedule): ?>
        <div class="sp-schedule">
          <div class="sp-schedule__head">
            <strong><?= $h($schedule['title'] ?: 'Untitled schedule') ?></strong>
            <?php $scheduleStatus = is_array($schedule['status_display'] ?? null) ? $schedule['status_display'] : ['label' => 'Healthy', 'variant' => 'success']; ?>
            <?= $spView->statusBadge((string) ($scheduleStatus['label'] ?? 'Healthy'), (string) ($scheduleStatus['variant'] ?? 'success'), $h) ?>
          </div>
          <p class="sp-muted">
            <?= $h((string) ($schedule['frequency_label'] ?? 'Scheduled')) ?>
            <?php if ((int) ($schedule['next_run_at'] ?? 0) > 0): ?>
              · next <?= $h(date('M j, Y g:ia', (int) $schedule['next_run_at'])) ?>
            <?php else: ?>
              · no future run scheduled
            <?php endif; ?>
            · <?= ! empty($schedule['is_active']) ? 'active' : 'paused' ?>
          </p>
          <?php if (! empty($schedule['next_topic'])): ?>
            <p><strong>Next topic:</strong> <?= $h($schedule['next_topic']) ?></p>
            <p class="sp-muted"><?= (int) $schedule['topics_used'] ?> of <?= (int) $schedule['topic_count'] ?> planned topics used</p>
          <?php endif; ?>
          <?php if (! empty($schedule['template_id']) && ! empty($template_options[(int) $schedule['template_id']])): ?>
            <p class="sp-muted">Template: <?= $h($template_options[(int) $schedule['template_id']]) ?></p>
          <?php endif; ?>
          <?php if (! empty($schedule['last_run_at'])): ?>
            <p class="sp-muted">Last run: <?= $h(date('M j, Y g:ia', (int) $schedule['last_run_at'])) ?></p>
          <?php endif; ?>
          <?php if (! empty($schedule['last_message'])): ?>
            <p class="<?= ! empty($schedule['last_error']) ? 'sp-danger-text' : 'sp-muted' ?>"><?= $h($schedule['last_message']) ?></p>
          <?php endif; ?>
          <?php if (! empty($schedule['last_error']) && (string) ($schedule['last_error'] ?? '') !== (string) ($schedule['last_message'] ?? '')): ?>
            <p class="sp-danger-text"><?= $h($schedule['last_error']) ?></p>
          <?php endif; ?>
          <?php if (! empty($schedule['latest_run_link'])): ?>
            <p><a href="<?= $h($schedule['latest_run_link']['url']) ?>"><?= $h($schedule['latest_run_link']['label']) ?></a></p>
          <?php endif; ?>
          <div class="sp-actions">
            <a class="button button--primary" href="<?= $h($spView->itemUrl($calendarItemUrlPattern, (string) $calendar_url, (int) $schedule['id'])) ?>#tab=t-1">Edit</a>
            <?= form_open($calendar_url) ?>
              <input type="hidden" name="run_schedule_now" value="<?= (int) $schedule['id'] ?>">
              <button type="submit" class="button button--default">Run Now</button>
            <?= form_close() ?>
            <?php if (in_array((string) ($schedule['last_status'] ?? ''), ['failed', 'duplicate_exhausted', 'image_failed'], true)): ?>
              <?= form_open($calendar_url) ?>
                <input type="hidden" name="retry_failed_schedule" value="<?= (int) $schedule['id'] ?>">
                <button type="submit" class="button button--default">Retry Failed</button>
              <?= form_close() ?>
            <?php endif; ?>
            <?php if (! empty($schedule['next_topic'])): ?>
              <?= form_open($calendar_url) ?>
                <input type="hidden" name="skip_schedule_topic" value="<?= (int) $schedule['id'] ?>">
                <button type="submit" class="button button--default">Skip Topic</button>
              <?= form_close() ?>
            <?php endif; ?>
            <?= form_open($calendar_url) ?>
              <input type="hidden" name="toggle_schedule" value="<?= (int) $schedule['id'] ?>">
              <button type="submit" class="button button--default"><?= ! empty($schedule['is_active']) ? 'Pause' : 'Resume' ?></button>
            <?= form_close() ?>
            <?= form_open($calendar_url) ?>
              <input type="hidden" name="delete_schedule" value="<?= (int) $schedule['id'] ?>">
              <button type="submit" class="button button--danger">Delete</button>
            <?= form_close() ?>
          </div>
        </div>
      <?php endforeach; ?>
    </section>
  </div>
</div>

<script>
(function () {
  const template = document.getElementById('sp-template');
  const editLink = document.getElementById('sp-template-edit-link');
  const starter = document.getElementById('sp-topic-starter');
  const starterButton = document.getElementById('sp-add-topic-starter');
  const starterNote = document.getElementById('sp-topic-starter-note');
  const plannedTopics = document.getElementById('sp-planned-topics');
  const recurrenceMode = document.getElementById('sp-recurrence-mode');
  const recurrenceInterval = document.getElementById('sp-recurrence-interval');
  const recurrenceUnit = document.getElementById('sp-recurrence-unit');
  const weekdayField = document.getElementById('sp-weekday-field');
  const legacyFrequency = document.getElementById('sp-frequency');
  const preview = <?= json_encode($template_preview ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
  const starters = <?= json_encode($template_starters ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
  const previewFields = {
    title: document.getElementById('sp-preview-title'),
    content_type: document.getElementById('sp-preview-content-type'),
    platform: document.getElementById('sp-preview-platform'),
    length_preset: document.getElementById('sp-preview-length'),
    word_count: document.getElementById('sp-preview-word-count'),
    citation_count: document.getElementById('sp-preview-citations'),
    schema_type: document.getElementById('sp-preview-schema'),
    internal_link_count: document.getElementById('sp-preview-internal-links'),
    external_link_count: document.getElementById('sp-preview-external-links'),
    is_default: document.getElementById('sp-preview-default'),
    tone: document.getElementById('sp-preview-tone'),
    audience: document.getElementById('sp-preview-audience'),
    goal: document.getElementById('sp-preview-goal'),
    research_mode: document.getElementById('sp-preview-research'),
    image_style: document.getElementById('sp-preview-image-style'),
    cta_style: document.getElementById('sp-preview-cta-style'),
    prompt_instructions: document.getElementById('sp-preview-instructions')
  };

  function selectedStarters() {
    if (! template) return [];
    return starters[template.value] || [];
  }

  function syncPreview() {
    if (! template) return;

    const item = preview[template.value] || null;
    if (editLink) {
      editLink.href = item && item.edit_url ? item.edit_url : <?= json_encode((string) $templates_url) ?>;
    }

    Object.keys(previewFields).forEach(function (key) {
      const field = previewFields[key];
      if (! field) return;

      if (! item) {
        field.textContent = '';
        return;
      }

      field.textContent = item[key] || '';
    });
  }

  function syncTemplateHelpers() {
    syncPreview();

    if (! starter) {
      return;
    }

    const list = selectedStarters();
    starter.innerHTML = '';
    list.forEach(function (item, index) {
      const option = document.createElement('option');
      option.value = String(index);
      option.textContent = item.label || ('Starter ' + (index + 1));
      starter.appendChild(option);
    });

    starter.disabled = list.length === 0;
    if (starterButton) starterButton.disabled = list.length === 0;
    if (starterNote) {
      starterNote.textContent = list.length
        ? 'Adds the selected starter prompt as the next planned topic/instruction.'
        : 'No starters for this template yet.';
    }
  }

  function syncRecurrenceUi() {
    if (! recurrenceMode || ! recurrenceUnit) {
      return;
    }

    const once = recurrenceMode.value === 'once';
    const weekly = recurrenceUnit.value === 'week';

    if (recurrenceInterval) {
      recurrenceInterval.disabled = once;
    }

    recurrenceUnit.disabled = once;

    if (weekdayField) {
      weekdayField.classList.toggle('is-hidden', once || ! weekly);
    }

    if (! legacyFrequency) {
      return;
    }

    if (once) {
      legacyFrequency.value = 'once';
      return;
    }

    const interval = Math.max(1, parseInt((recurrenceInterval && recurrenceInterval.value) || '1', 10) || 1);
    if (recurrenceUnit.value === 'day') {
      legacyFrequency.value = interval === 1 ? 'daily' : (interval === 2 ? 'every_other_day' : (interval === 3 ? 'every_3_days' : 'daily'));
      return;
    }

    if (recurrenceUnit.value === 'month') {
      legacyFrequency.value = 'monthly';
      return;
    }

    const checkedWeekdays = Array.from(document.querySelectorAll('input[name="weekdays[]"]:checked')).map(function (el) {
      return el.value;
    });
    const weekdaySet = checkedWeekdays.join(',');

    if (interval === 1 && weekdaySet === '1,2,3,4,5') {
      legacyFrequency.value = 'weekdays';
    } else if (interval === 2) {
      legacyFrequency.value = 'biweekly';
    } else {
      legacyFrequency.value = 'weekly';
    }
  }

  if (template) {
    template.addEventListener('change', syncTemplateHelpers);
  }

  [recurrenceMode, recurrenceInterval, recurrenceUnit].forEach(function (field) {
    if (field) {
      field.addEventListener('change', syncRecurrenceUi);
      field.addEventListener('input', syncRecurrenceUi);
    }
  });

  Array.from(document.querySelectorAll('input[name="weekdays[]"]')).forEach(function (field) {
    field.addEventListener('change', syncRecurrenceUi);
  });

  if (starterButton && plannedTopics) {
    starterButton.addEventListener('click', function () {
      const list = selectedStarters();
      const item = list[parseInt(starter.value || '0', 10)];
      if (! item || ! item.prompt) return;

      const current = plannedTopics.value.trim();
      plannedTopics.value = current ? current + "\n" + item.prompt : item.prompt;
      plannedTopics.focus();
    });
  }

  syncTemplateHelpers();
  syncRecurrenceUi();
})();
</script>
