<?php
$h = fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$n = fn($value) => number_format((int) $value);
$money = fn($value) => '$' . number_format((float) $value, 4);
$costs = $costs ?? ['available' => false, 'error' => '', 'total' => 0, 'line_items' => [], 'buckets' => []];
?>
<div class="sp-wrap">
  <p class="sp-toolbar">
    <a class="button button--default" href="<?= $h($history_url) ?>">History</a>
    <a class="button button--default" href="<?= $h($settings_url) ?>">Settings</a>
  </p>

  <section class="sp-card">
    <h2>Token Usage</h2>
    <p class="sp-muted">Token usage from the last 100 stored content generations.</p>

    <div class="sp-usage-grid">
      <div class="sp-usage-metric">
        <span>Requests</span>
        <strong><?= $n($summary['requests'] ?? 0) ?></strong>
      </div>
      <div class="sp-usage-metric">
        <span>Total Tokens</span>
        <strong><?= $n($summary['total_tokens'] ?? 0) ?></strong>
      </div>
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
    <h2>Actual OpenAI Costs</h2>
    <?php if (empty($costs['available'])): ?>
      <p class="sp-muted"><?= $h($costs['error'] ?: 'Actual costs are not available.') ?></p>
      <p><a class="button button--default" href="<?= $h($settings_url) ?>">Add Admin Key</a></p>
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
      <p class="sp-muted sp-usage-note">Actual billed costs are loaded from OpenAI organization costs. They include text, image generation, and other line items returned by OpenAI for the configured project or organization.</p>
    <?php endif; ?>
  </section>

  <section class="sp-card">
    <h2>Recent Generations</h2>
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
