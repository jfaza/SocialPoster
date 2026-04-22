<?php

namespace JavidFazaeli\SocialPoster\Commands;

use ExpressionEngine\Cli\Cli;

class CommandRunSchedule extends Cli
{
    /**
     * name of command
     * @var string
     */
    public $name = 'RunSchedule';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'socialposter:run-schedule';

    /**
     * Public description of command
     * @var string
     */
    public $description = 'Generate due SocialPoster scheduled posts.';

    /**
     * Summary of command functionality
     * @var [type]
     */
    public $summary = 'Generate due SocialPoster scheduled posts.';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php socialposter:run-schedule';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'limit,l:' => 'Maximum schedules to run in this pass',
    ];

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        $limit = (int) $this->option('--limit', 5);
        $result = ee('socialposter:scheduler')->runDue(max(1, $limit));

        $this->info('SocialPoster scheduler complete.');
        $this->info($result['generated'] . ' generated, ' . $result['failed'] . ' failed.');

        foreach ($result['items'] as $item) {
            if (! empty($item['ok'])) {
                $this->info('Schedule #' . $item['schedule_id'] . ' generated post #' . $item['generation_id']);
            } else {
                $this->error('Schedule #' . $item['schedule_id'] . ' failed: ' . $item['error']);
            }
        }
    }
}
