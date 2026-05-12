<?php

namespace App\Jobs\Dashboard;

use App\Enums\DashboardMetricsPeriod;
use App\Services\Dashboard\Executive\Projection\DashboardProjectionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class RefreshTenantDashboardSnapshotJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * @var list<int>
     */
    public array $backoff = [10, 30];

    public int $timeout = 30;

    public function __construct(
        public int $tenantId,
        public DashboardMetricsPeriod $period,
    ) {}

    public function handle(DashboardProjectionService $projection): void
    {
        $projection->refreshFor($this->tenantId, $this->period);
    }
}
