<?php

namespace App\Services\Dashboard\Executive\Providers;

use App\Enums\DashboardMetricsPeriod;
use App\Models\User;
use App\Services\Dashboard\DashboardMetricsService;
use App\Services\Dashboard\Executive\Snapshot\KpiStrip;

/**
 * Único consumidor de {@see DashboardMetricsService} nesta camada executiva.
 * Delega em {@see DashboardMetricsService::getMetrics} (único método público de leitura agregada).
 */
final class KpiStripProvider
{
    public function __construct(
        private readonly DashboardMetricsService $metrics,
    ) {}

    public function build(User $actor, DashboardMetricsPeriod $period): KpiStrip
    {
        $raw = $this->metrics->getMetrics($actor, $period);

        /** @var array<string, int> $tasks */
        $tasks = $raw['tasks'];
        /** @var array<string, int> $meetings */
        $meetings = $raw['meetings'];
        /** @var array<string, int> $votes */
        $votes = $raw['votes'];
        /** @var array<string, int> $signatures */
        $signatures = $raw['signatures'];

        return new KpiStrip(
            tasks: $tasks,
            meetings: $meetings,
            votes: $votes,
            signatures: $signatures,
        );
    }
}
