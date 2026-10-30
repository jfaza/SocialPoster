<?php

namespace JavidFazaeli\SocialPoster\ControlPanel\Routes;

use ExpressionEngine\Service\Addon\Controllers\Mcp\AbstractRoute;

class Usage extends AbstractRoute
{
    use LoadsStyle;
    use BuildsRouteContext;

    /**
     * @var string
     */
    protected $route_path = 'usage';

    /**
     * @var string
     */
    protected $cp_page_title = 'SocialPoster Token Usage';

    /**
     * @param false $id
     * @return AbstractRoute
     */
    public function process($id = false)
    {
        $this->addBreadcrumb('index', 'SocialPoster');
        $this->addBreadcrumb('usage', 'Token Usage');
        $this->loadStyle();

        $usage = ee('socialposter:generator')->tokenUsage(100);
        $coreUrls = $this->coreRouteContext();
        $rows = is_array($usage['rows'] ?? null) ? $usage['rows'] : [];
        $summary = is_array($usage['summary'] ?? null) ? $usage['summary'] : [];

        $requests = max(0, (int) ($summary['requests'] ?? 0));
        $averages = [
            'tokens_per_request' => $requests > 0 ? (int) round(((int) ($summary['total_tokens'] ?? 0)) / $requests) : 0,
            'input_per_request' => $requests > 0 ? (int) round(((int) ($summary['input_tokens'] ?? 0)) / $requests) : 0,
            'output_per_request' => $requests > 0 ? (int) round(((int) ($summary['output_tokens'] ?? 0)) / $requests) : 0,
        ];

        $modelBreakdown = [];
        $sourceBreakdown = [];
        $topRows = $rows;

        usort($topRows, static function (array $left, array $right): int {
            return ((int) ($right['total_tokens'] ?? 0)) <=> ((int) ($left['total_tokens'] ?? 0));
        });
        $topRows = array_slice($topRows, 0, 5);

        foreach ($rows as $row) {
            $totalTokens = max(0, (int) ($row['total_tokens'] ?? 0));
            $model = trim((string) ($row['model'] ?? '')) ?: 'Unknown';
            $source = trim((string) ($row['source'] ?? '')) ?: 'manual';

            if (! isset($modelBreakdown[$model])) {
                $modelBreakdown[$model] = ['label' => $model, 'requests' => 0, 'tokens' => 0];
            }
            $modelBreakdown[$model]['requests']++;
            $modelBreakdown[$model]['tokens'] += $totalTokens;

            if (! isset($sourceBreakdown[$source])) {
                $sourceBreakdown[$source] = ['label' => $source, 'requests' => 0, 'tokens' => 0];
            }
            $sourceBreakdown[$source]['requests']++;
            $sourceBreakdown[$source]['tokens'] += $totalTokens;
        }

        uasort($modelBreakdown, static function (array $left, array $right): int {
            return $right['tokens'] <=> $left['tokens'];
        });
        uasort($sourceBreakdown, static function (array $left, array $right): int {
            return $right['tokens'] <=> $left['tokens'];
        });

        $this->setBody('Usage', [
            'summary' => $summary,
            'rows' => $rows,
            'costs' => $usage['costs'],
            'averages' => $averages,
            'model_breakdown' => array_slice(array_values($modelBreakdown), 0, 6),
            'source_breakdown' => array_slice(array_values($sourceBreakdown), 0, 6),
            'top_rows' => $topRows,
            'index_url' => $coreUrls['index_url'],
            'history_url' => $coreUrls['history_url'],
            'settings_url' => $coreUrls['settings_url'],
            'templates_url' => $coreUrls['templates_url'],
        ]);

        return $this;
    }
}
