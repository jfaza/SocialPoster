<?php
$h = fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$n = fn($value) => number_format((int) $value);
$money = fn($value) => '$' . number_format((float) $value, 4);
$costs = $costs ?? ['available' => false, 'error' => '', 'total' => 0, 'line_items' => [], 'buckets' => []];
$averages = is_array($averages ?? null) ? $averages : [];
$modelBreakdown = is_array($model_breakdown ?? null) ? $model_breakdown : [];
$sourceBreakdown = is_array($source_breakdown ?? null) ? $source_breakdown : [];
$topRows = is_array($top_rows ?? null) ? $top_rows : [];
?>
<div class="sp-wrap sp-usage-page">
  <p class="sp-toolbar">
    <a class="button button--default" href="<?= $h($history_url) ?>">History</a>
    <a class="button button--default" href="<?= $h($settings_url) ?>">Settings</a>
  </p>

  <section class="sp-card sp-usage-hero">
    <div class="sp-usage-hero__intro">
      <span class="sp-kicker">Usage Overview</span>
      <h2>Token Usage</h2>
      <p class="sp-muted">A compact view of the last 100 stored content generations, with enough structure to see what is driving usage without turning this into a finance spreadsheet.</p>
    </div>
    <div class="sp-usage-grid sp-usage-grid--hero">
      <div class="sp-usage-metric">
        <span>Requests</span>
        <strong><?= $n($summary['requests'] ?? 0) ?></strong>
      </div>
      <div class="sp-usage-metric">
        <span>Total Tokens</span>
        <strong><?= $n($summary['total_tokens'] ?? 0) ?></strong>
      </div>
      <div class="sp-usage-metric">
        <span>Avg Tokens / Request</span>
        <strong><?= $n($averages['tokens_per_request'] ?? 0) ?></strong>
      </div>
      <div class="sp-usage-metric">
        <span>Avg Input / Request</span>
        <strong><?= $n($averages['input_per_request'] ?? 0) ?></strong>
      </div>
      <div class="sp-usage-metric">
        <span>Avg Output / Request</span>
        <strong><?= $n($averages['output_per_request'] ?? 0) ?></strong>
      </div>
    </div>
  </section>

  <div class="sp-usage-layout">
    <section class="sp-card">
      <div class="sp-card-head">
        <div>
          <h3>Token Breakdown</h3>
          <p class="sp-muted">Raw token distribution across the stored generation window.</p>
        </div>
      </div>
      <div class="sp-usage-grid">
        <div class="sp-usage-metric">
          <span>Input Tokens</span>
          <strong><?= $n($summary['input_tokens'] ?? 0) ?></strong>
        </div>
        <div class="sp-usage-metric">
          <span>Output Tokens</span>
          <strong><?= $n($summary['output_tokens'] ?? 0) ?></strong>
        </div>
        <div class="sp-usage-metric">
          <span>Cached Tokens</span>
          <strong><?= $n($summary['cached_tokens'] ?? 0) ?></strong>
        </div>
        <div class="sp-usage-metric">
          <span>Reasoning Tokens</span>
          <strong><?= $n($summary['reasoning_tokens'] ?? 0) ?></strong>
        </div>
      </div>
    </section>

    <section class="sp-card">
      <div class="sp-card-head">
        <div>
          <h3>Actual OpenAI Costs</h3>
          <p class="sp-muted">Billed cost context from the OpenAI organization or project, when an admin key is available.</p>
        </div>
      </div>
      <?php if (empty($costs['available'])): ?>
        <div class="sp-usage-empty">
          <p class="sp-muted"><?= $h($costs['error'] ?: 'Actual costs are not available.') ?></p>
          <p><a class="button button--default" href="<?= $h($settings_url) ?>">Add Admin Key</a></p>
        </div>
      <?php else: ?>
        <div class="sp-usage-grid sp-cost-grid">
          <div class="sp-usage-metric sp-cost-total">
            <span>Last 30 Days</span>
            <strong><?= $money($costs['total'] ?? 0) ?></strong>
          </div>
          <?php foreach (array_slice((array) ($costs['line_items'] ?? []), 0, 5, true) as $lineItem => $amount): ?>
            <div class="sp-usage-metric">
              <span><?= $h($lineItem ?: 'Other') ?></span>
              <strong><?= $money($amount) ?></strong>
            </div>
          <?php endforeach; ?>
        </div>
        <p class="sp-muted sp-usage-note">Costs include text, image generation, and any other returned OpenAI line items. This is billing context, not only the content-generation records listed below.</p>
      <?php endif; ?>
    </section>
  </div>

  <div class="sp-usage-layout">
    <section class="sp-card">
      <div class="sp-card-head">
        <div>
          <h3>Usage by Model</h3>
          <p class="sp-muted">Which models are carrying the most token load in this stored window.</p>
        </div>
      </div>
      <?php if (empty($modelBreakdown)): ?>
        <p class="sp-muted">No model usage has been recorded yet.</p>
      <?php else: ?>
        <div class="sp-usage-list">
          <?php foreach ($modelBreakdown as $item): ?>
            <div class="sp-usage-list__item">
              <div>
                <strong><?= $h($item['label'] ?? 'Unknown') ?></strong>
                <span><?= $n($item['requests'] ?? 0) ?> request<?= (int) ($item['requests'] ?? 0) === 1 ? '' : 's' ?></span>
              </div>
              <div class="sp-usage-list__value"><?= $n($item['tokens'] ?? 0) ?> tokens</div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <section class="sp-card">
      <div class="sp-card-head">
        <div>
          <h3>Usage by Workflow</h3>
          <p class="sp-muted">A quick read on which generation sources are consuming the most tokens.</p>
        </div>
      </div>
      <?php if (empty($sourceBreakdown)): ?>
        <p class="sp-muted">No workflow usage has been recorded yet.</p>
      <?php else: ?>
        <div class="sp-usage-list">
          <?php foreach ($sourceBreakdown as $item): ?>
            <div class="sp-usage-list__item">
              <div>
                <strong><?= $h(ucfirst((string) ($item['label'] ?? 'manual'))) ?></strong>
                <span><?= $n($item['requests'] ?? 0) ?> request<?= (int) ($item['requests'] ?? 0) === 1 ? '' : 's' ?></span>
              </div>
              <div class="sp-usage-list__value"><?= $n($item['tokens'] ?? 0) ?> tokens</div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  </div>

  <section class="sp-card">
    <div class="sp-card-head">
      <div>
        <h3>Heaviest Recent Requests</h3>
        <p class="sp-muted">The five highest-token generations in the current stored window.</p>
      </div>
      <a class="button button--default" href="<?= $h($history_url) ?>">Open History</a>
    </div>
    <?php if (empty($topRows)): ?>
      <p class="sp-muted">No high-usage requests to review yet.</p>
    <?php else: ?>
      <div class="sp-usage-list sp-usage-list--rows">
        <?php foreach ($topRows as $row): ?>
          <div class="sp-usage-list__item">
            <div>
              <strong><a href="<?= $h($history_url . '/' . (int) $row['id']) ?>"><?= $h($row['title'] ?: 'Generation #' . (int) $row['id']) ?></a></strong>
              <span>
                <?= $h(date('M j, Y g:ia', (int) $row['created_at'])) ?>
                <?php if (! empty($row['model'])): ?>
                  · <?= $h($row['model']) ?>
                <?php endif; ?>
                <?php if (! empty($row['source'])): ?>
                  · <?= $h($row['source']) ?>
                <?php endif; ?>
              </span>
            </div>
            <div class="sp-usage-list__value"><?= $n($row['total_tokens'] ?? 0) ?> tokens</div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <section class="sp-card">
    <div class="sp-card-head">
      <div>
        <h3>Recent Generations</h3>
        <p class="sp-muted">Full request-by-request detail for the latest stored generations with usage data.</p>
      </div>
    </div>
    <?php if (empty($rows)): ?>
      <p class="sp-muted">No token usage has been recorded yet.</p>
    <?php else: ?>
      <div class="sp-table-wrap">
        <table class="sp-usage-table">
          <thead>
            <tr>
              <th>Generation</th>
              <th>Created</th>
              <th>Input</th>
              <th>Output</th>
              <th>Total</th>
              <th>Model</th>
              <th>Cached</th>
              <th>Reasoning</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $row): ?>
              <tr>
                <td>
                  <a href="<?= $h($history_url . '/' . (int) $row['id']) ?>">
                    <?= $h($row['title'] ?: 'Generation #' . (int) $row['id']) ?>
                  </a>
                  <?php if (! empty($row['source'])): ?>
                    <span class="sp-usage-source"><?= $h($row['source']) ?></span>
                  <?php endif; ?>
                </td>
                <td><?= $h(date('M j, Y g:ia', (int) $row['created_at'])) ?></td>
                <td><?= $n($row['input_tokens'] ?? 0) ?></td>
                <td><?= $n($row['output_tokens'] ?? 0) ?></td>
                <td><?= $n($row['total_tokens'] ?? 0) ?></td>
                <td><?= $h($row['model'] ?: 'Unknown') ?></td>
                <td><?= $n($row['cached_tokens'] ?? 0) ?></td>
                <td><?= $n($row['reasoning_tokens'] ?? 0) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>
</div>
