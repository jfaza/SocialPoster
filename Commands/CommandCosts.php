<?php

namespace JavidFazaeli\SocialPoster\Commands;

use ExpressionEngine\Cli\Cli;

class CommandCosts extends Cli
{
    /**
     * name of command
     * @var string
     */
    public $name = 'Costs';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'socialposter:costs';

    /**
     * Public description of command
     * @var string
     */
    public $description = 'Fetch actual OpenAI billed costs for SocialPoster.';

    /**
     * Summary of command functionality
     * @var string
     */
    public $summary = 'Uses the saved OpenAI Admin API key to fetch organization costs.';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php socialposter:costs';

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        $generator = ee('socialposter:generator');
        $settings = $generator->getSettings();
        $usage = $generator->tokenUsage(1);
        $costs = $usage['costs'] ?? [];

        if (empty($costs['available'])) {
            $this->error('Could not fetch OpenAI costs: ' . ($costs['error'] ?? 'Unknown error.'));
            $this->info('Saved admin key: ' . $this->mask((string) ($settings['admin_api_key'] ?? '')));
            $this->info('Saved project ID: ' . $this->mask((string) ($settings['openai_project_id'] ?? '')));
            return;
        }

        $currency = strtoupper((string) ($costs['currency'] ?? 'usd'));
        $this->info('OpenAI costs for the last 30 days: ' . number_format((float) ($costs['total'] ?? 0), 6) . ' ' . $currency);

        if (! empty($costs['line_items'])) {
            $this->info('Line items:');
            foreach ($costs['line_items'] as $item => $amount) {
                $this->info('- ' . $item . ': ' . number_format((float) $amount, 6) . ' ' . $currency);
            }
        }
    }

    private function mask(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '(empty)';
        }

        if (strlen($value) <= 12) {
            return substr($value, 0, 4) . '...';
        }

        return substr($value, 0, 8) . '...' . substr($value, -4);
    }
}
