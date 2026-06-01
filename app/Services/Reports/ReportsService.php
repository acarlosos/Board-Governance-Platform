<?php

namespace App\Services\Reports;

use App\Enums\DashboardMetricsPeriod;
use App\Models\Meeting;
use App\Models\SignatureRequest;
use App\Models\Task;
use App\Models\User;
use App\Models\Vote;
use App\Services\Reporting\ReportingContext;

final class ReportsService
{
    /**
     * Contagens por status (agregação SQL, sem coleções completas).
     *
     * @return array<string, int>
     */
    public function tasksByStatus(User $user, DashboardMetricsPeriod $period): array
    {
        $ctx = ReportingContext::fromUser($user);
        $q = Task::query()->withoutGlobalScopes(); // reason: strip TenantScope; tenant via $ctx->restrictToTenant().
        $ctx->restrictToTenant($q);
        $period->applyToCreatedAt($q);

        $rows = $q->selectRaw('status as k, COUNT(*) as c')
            ->groupBy('k')
            ->pluck('c', 'k')
            ->all();

        /** @var array<string, int> $out */
        $out = array_map(static fn ($v): int => (int) $v, $rows);

        return $out;
    }

    /**
     * Reuniões agendadas por mês (últimos 12 meses, uma agregação count por bucket).
     *
     * @return array<string, int> chave no formato Y-m
     */
    public function meetingsByMonth(User $user): array
    {
        $ctx = ReportingContext::fromUser($user);
        $buckets = [];

        for ($i = 11; $i >= 0; $i--) {
            $start = now()->copy()->startOfMonth()->subMonths($i);
            $end = $start->copy()->endOfMonth();
            $key = $start->format('Y-m');

            $q = Meeting::query()->withoutGlobalScopes(); // reason: strip TenantScope; tenant via $ctx->restrictToTenant().
            $ctx->restrictToTenant($q);
            $q->whereNotNull($q->qualifyColumn('scheduled_at'))
                ->whereBetween($q->qualifyColumn('scheduled_at'), [$start, $end]);

            $buckets[$key] = $q->count();
        }

        return $buckets;
    }

    /**
     * @return array<string, int>
     */
    public function votesByStatus(User $user, DashboardMetricsPeriod $period): array
    {
        $ctx = ReportingContext::fromUser($user);
        $q = Vote::query()->withoutGlobalScopes(); // reason: strip TenantScope; tenant via $ctx->restrictToTenant().
        $ctx->restrictToTenant($q);
        $period->applyToCreatedAt($q);

        $rows = $q->selectRaw('status as k, COUNT(*) as c')
            ->groupBy('k')
            ->pluck('c', 'k')
            ->all();

        return array_map(static fn ($v): int => (int) $v, $rows);
    }

    /**
     * @return array<string, int>
     */
    public function signaturesByStatus(User $user, DashboardMetricsPeriod $period): array
    {
        $ctx = ReportingContext::fromUser($user);
        $q = SignatureRequest::query()->withoutGlobalScopes(); // reason: strip TenantScope; tenant via $ctx->restrictToTenant().
        $ctx->restrictToTenant($q);
        $period->applyToCreatedAt($q);

        $rows = $q->selectRaw('status as k, COUNT(*) as c')
            ->groupBy('k')
            ->pluck('c', 'k')
            ->all();

        return array_map(static fn ($v): int => (int) $v, $rows);
    }

    /**
     * Dados combinados para a página de relatórios (filtro de período nas séries aplicável).
     *
     * @return array{tasks_by_status: array<string, int>, meetings_by_month: array<string, int>, votes_by_status: array<string, int>, signatures_by_status: array<string, int>}
     */
    public function summary(User $user, DashboardMetricsPeriod $period): array
    {
        return [
            'tasks_by_status' => $this->tasksByStatus($user, $period),
            'meetings_by_month' => $this->meetingsByMonth($user),
            'votes_by_status' => $this->votesByStatus($user, $period),
            'signatures_by_status' => $this->signaturesByStatus($user, $period),
        ];
    }
}
