<?php
ee()->load->helper('form');
$h = fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
?>
<div class="sp-wrap">
  <div class="sp-toolbar">
    <a class="button button--default" href="<?= $h($history_url) ?>">History</a>
    <a class="button button--default" href="<?= $h($settings_url) ?>">Settings</a>
    <a class="button button--default" href="<?= $h($templates_url) ?>">Templates</a>
    <?= form_open($calendar_url, ['class' => 'sp-inline-form']) ?>
      <input type="hidden" name="run_due" value="1">
      <button type="submit" class="button button--primary">Run Due Now</button>
    <?= form_close() ?>
  </div>

  <div class="tab-wrap sp-native-tabs">
    <div class="tab-bar">
      <div class="tab-bar__tabs">
        <button type="button" class="tab-bar__tab js-tab-button active" rel="t-0">Create Schedule</button>
        <button type="button" class="tab-bar__tab js-tab-button" rel="t-1">Schedules</button>
        <button type="button" class="tab-bar__tab js-tab-button" rel="t-2">Calendar</button>
      </div>
    </div>

    <section class="sp-card tab t-0 tab-open">
      <h2>Schedule Generation</h2>
      <p class="sp-muted">Choose a generation template, set the cadence, and SocialPoster will create each post from that template.</p>
      <?= form_open($calendar_url) ?>
        <input type="hidden" name="create_schedule" value="1">
        <div class="sp-field">
          <label for="sp-template">Generation Template</label>
          <select id="sp-template" name="template_id" required>
            <?php foreach ($template_options as $value => $label): ?>
              <option value="<?= (int) $value ?>"><?= $h($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="sp-field">
          <label for="sp-frequency">Frequency</label>
          <select id="sp-frequency" name="frequency">
            <?php foreach ($frequencies as $value => $label): ?>
              <option value="<?= $h($value) ?>" <?= $value === 'weekly' ? 'selected' : '' ?>><?= $h($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="sp-field">
          <label for="sp-title">Calendar Title</label>
          <input id="sp-title" name="title" type="text" placeholder="Optional. Defaults to template name.">
        </div>
        <div class="sp-field">
          <label for="sp-start-date">First Run Date</label>
          <input id="sp-start-date" name="start_date" type="date" value="<?= date('Y-m-d') ?>">
        </div>
        <div class="sp-field">
          <label for="sp-start-time">First Run Time</label>
          <input id="sp-start-time" name="start_time" type="time" value="09:00">
        </div>
        <div class="sp-field">
          <label for="sp-planned-topics">Planned Topics</label>
          <textarea id="sp-planned-topics" name="planned_topics" rows="8" placeholder="One topic per line. The scheduler uses the next topic each time this schedule runs."></textarea>
        </div>
        <button type="submit" class="button button--primary">Add Schedule</button>
      <?= form_close() ?>
    </section>

    <section class="sp-card tab t-1">
      <h3>Schedules</h3>
      <?php if (empty($schedules)): ?>
        <p class="sp-muted">No schedules yet.</p>
      <?php endif; ?>
      <?php foreach ($schedules as $schedule): ?>
        <div class="sp-schedule">
          <strong><?= $h($schedule['title'] ?: 'Untitled schedule') ?></strong>
          <p class="sp-muted"><?= $h(ucfirst($schedule['frequency'])) ?> · next <?= $h(date('M j, Y g:ia', (int) $schedule['next_run_at'])) ?> · <?= ! empty($schedule['is_active']) ? 'active' : 'paused' ?></p>
          <?php if (! empty($schedule['next_topic'])): ?>
            <p><strong>Next topic:</strong> <?= $h($schedule['next_topic']) ?></p>
            <p class="sp-muted"><?= (int) $schedule['topics_used'] ?> of <?= (int) $schedule['topic_count'] ?> planned topics used</p>
          <?php endif; ?>
          <?php if (! empty($schedule['template_id']) && ! empty($template_options[(int) $schedule['template_id']])): ?>
            <p class="sp-muted">Template: <?= $h($template_options[(int) $schedule['template_id']]) ?></p>
          <?php endif; ?>
          <?php if (! empty($schedule['last_error'])): ?>
            <p class="sp-danger-text"><?= $h($schedule['last_error']) ?></p>
          <?php endif; ?>
          <div class="sp-actions">
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

    <section class="sp-card tab t-2">
      <div class="sp-actions sp-calendar-head">
        <a class="button button--default" href="<?= $h($prev_url) ?>#tab=t-2">Previous</a>
        <h2 class="sp-month-title"><?= $h($month_label) ?></h2>
        <a class="button button--default" href="<?= $h($next_url) ?>#tab=t-2">Next</a>
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
                      <?php if (! empty($event['url'])): ?>
                        <a class="sp-event <?= $h($event['type']) ?> <?= empty($event['active']) ? 'inactive' : '' ?>" href="<?= $h($event['url']) ?>">
                          <?= $h($event['time']) ?> · <?= $h($event['title']) ?>
                        </a>
                      <?php else: ?>
                        <span class="sp-event <?= $h($event['type']) ?> <?= empty($event['active']) ? 'inactive' : '' ?>">
                          <?= $h($event['time']) ?> · <?= $h($event['title']) ?>
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
    </section>
  </div>
</div>
