<?php

namespace App\Services\Dashboard;

use App\Enums\DashboardMetricsPeriod;
use App\Enums\MeetingStatus;
use App\Enums\MinuteStatus;
use App\Enums\NotificationStatus;
use App\Enums\SignatureRequestStatus;
use App\Enums\TaskStatus;
use App\Enums\VoteStatus;
use App\Models\Meeting;
use App\Models\Minute;
use App\Models\NotificationCenter;
use App\Models\SignatureRequest;
use App\Models\Task;
use App\Models\User;
use App\Models\Vote;
use App\Services\Dashboard\Executive\Cache\ExecutiveDashboardCacheKeys;
use App\Services\Dashboard\Executive\Observability\ExecutiveDashboardObservability;
use App\Services\Reporting\ReportingContext;
use Illuminate\Support\Facades\Cache;

final class DashboardMetricsService
{
    private const CACHE_TTL_SECONDS = 90;

    public function __construct(
        private readonly ExecutiveDashboardObservability $observability,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getMetrics(User $user, DashboardMetricsPeriod $period = DashboardMetricsPeriod::ThisMonth): array
    {
        $ctx = ReportingContext::fromUser($user);

        $key = ExecutiveDashboardCacheKeys::l1Key($ctx->cacheSegment(), $period);

        if (Cache::has($key)) {
            $this->observability->recordL1Hit();
        } else {
            $this->observability->recordL1Miss();
        }

        return Cache::remember($key, now()->addSeconds(self::CACHE_TTL_SECONDS), fn (): array => $this->computeMetrics($ctx, $period));
    }

    /**
     * Esposto para testes (métricas sem passar pelo cache).
     *
     * @return array<string, mixed>
     */
    public function getUncachedMetrics(User $user, DashboardMetricsPeriod $period): array
    {
        return $this->computeMetrics(ReportingContext::fromUser($user), $period);
    }

    /**
     * @return array<string, mixed>
     */
    private function computeMetrics(ReportingContext $ctx, DashboardMetricsPeriod $period): array
    {
        return [
            'tasks' => $this->taskMetrics($ctx, $period),
            'meetings' => $this->meetingMetrics($ctx, $period),
            'minutes' => $this->minuteMetrics($ctx, $period),
            'votes' => $this->voteMetrics($ctx, $period),
            'signatures' => $this->signatureMetrics($ctx, $period),
            'notifications' => $this->notificationMetrics($ctx, $period),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function taskMetrics(ReportingContext $ctx, DashboardMetricsPeriod $period): array
    {
        $total = Task::query()->withoutGlobalScopes(); // reason: strip TenantScope; tenant via $ctx->restrictToTenant().
        $ctx->restrictToTenant($total);
        $period->applyToCreatedAt($total);

        $open = Task::query()->withoutGlobalScopes();
        $ctx->restrictToTenant($open);
        $period->applyToCreatedAt($open);
        $open->whereIn('status', [TaskStatus::Pending->value, TaskStatus::InProgress->value]);

        $completed = Task::query()->withoutGlobalScopes();
        $ctx->restrictToTenant($completed);
        $period->applyToCreatedAt($completed);
        $completed->where('status', TaskStatus::Completed->value);

        $overdue = Task::query()->withoutGlobalScopes();
        $ctx->restrictToTenant($overdue);
        $overdue->whereIn('status', [TaskStatus::Pending->value, TaskStatus::InProgress->value])
            ->whereNotNull('due_date')
            ->where($overdue->qualifyColumn('due_date'), '<', now());

        return [
            'total_tasks' => $total->count(),
            'tasks_open' => $open->count(),
            'tasks_completed' => $completed->count(),
            'tasks_overdue' => $overdue->count(),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function meetingMetrics(ReportingContext $ctx, DashboardMetricsPeriod $period): array
    {
        $total = Meeting::query()->withoutGlobalScopes(); // reason: strip TenantScope; tenant via $ctx->restrictToTenant().
        $ctx->restrictToTenant($total);
        $period->applyToCreatedAt($total);

        $thisMonthStart = now()->copy()->startOfMonth();
        $thisMonthEnd = now()->copy()->endOfMonth();

        $thisMonth = Meeting::query()->withoutGlobalScopes();
        $ctx->restrictToTenant($thisMonth);
        $thisMonth->whereNotNull($thisMonth->qualifyColumn('scheduled_at'))
            ->whereBetween($thisMonth->qualifyColumn('scheduled_at'), [$thisMonthStart, $thisMonthEnd]);

        $completed = Meeting::query()->withoutGlobalScopes();
        $ctx->restrictToTenant($completed);
        $period->applyToCreatedAt($completed);
        $completed->where('status', MeetingStatus::Completed->value);

        return [
            'total_meetings' => $total->count(),
            'meetings_this_month' => $thisMonth->count(),
            'meetings_completed' => $completed->count(),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function minuteMetrics(ReportingContext $ctx, DashboardMetricsPeriod $period): array
    {
        $total = Minute::query()->withoutGlobalScopes(); // reason: strip TenantScope; tenant via $ctx->restrictToTenant().
        $ctx->restrictToTenant($total);
        $period->applyToCreatedAt($total);

        $pending = Minute::query()->withoutGlobalScopes();
        $ctx->restrictToTenant($pending);
        $period->applyToCreatedAt($pending);
        $pending->where('status', MinuteStatus::InReview->value);

        $approved = Minute::query()->withoutGlobalScopes();
        $ctx->restrictToTenant($approved);
        $period->applyToCreatedAt($approved);
        $approved->where('status', MinuteStatus::Approved->value);

        return [
            'total_minutes' => $total->count(),
            'minutes_pending_review' => $pending->count(),
            'minutes_approved' => $approved->count(),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function voteMetrics(ReportingContext $ctx, DashboardMetricsPeriod $period): array
    {
        $total = Vote::query()->withoutGlobalScopes(); // reason: strip TenantScope; tenant via $ctx->restrictToTenant().
        $ctx->restrictToTenant($total);
        $period->applyToCreatedAt($total);

        $open = Vote::query()->withoutGlobalScopes();
        $ctx->restrictToTenant($open);
        $period->applyToCreatedAt($open);
        $open->where('status', VoteStatus::Open->value);

        $closed = Vote::query()->withoutGlobalScopes();
        $ctx->restrictToTenant($closed);
        $period->applyToCreatedAt($closed);
        $closed->where('status', VoteStatus::Closed->value);

        return [
            'total_votes' => $total->count(),
            'votes_open' => $open->count(),
            'votes_closed' => $closed->count(),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function signatureMetrics(ReportingContext $ctx, DashboardMetricsPeriod $period): array
    {
        $total = SignatureRequest::query()->withoutGlobalScopes(); // reason: strip TenantScope; tenant via $ctx->restrictToTenant().
        $ctx->restrictToTenant($total);
        $period->applyToCreatedAt($total);

        $pending = SignatureRequest::query()->withoutGlobalScopes();
        $ctx->restrictToTenant($pending);
        $period->applyToCreatedAt($pending);
        $pending->whereIn('status', [
            SignatureRequestStatus::Draft->value,
            SignatureRequestStatus::Sent->value,
            SignatureRequestStatus::Failed->value,
        ]);

        $done = SignatureRequest::query()->withoutGlobalScopes();
        $ctx->restrictToTenant($done);
        $period->applyToCreatedAt($done);
        $done->where('status', SignatureRequestStatus::Completed->value);

        return [
            'total_signature_requests' => $total->count(),
            'signatures_pending' => $pending->count(),
            'signatures_completed' => $done->count(),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function notificationMetrics(ReportingContext $ctx, DashboardMetricsPeriod $period): array
    {
        $total = NotificationCenter::query()->withoutGlobalScopes(); // reason: strip TenantScope; tenant via $ctx->restrictToTenant().
        $ctx->restrictToTenant($total);
        $period->applyToCreatedAt($total);

        $unread = NotificationCenter::query()->withoutGlobalScopes();
        $ctx->restrictToTenant($unread);
        $period->applyToCreatedAt($unread);
        $unread->where('status', NotificationStatus::Unread->value);

        return [
            'total_notifications' => $total->count(),
            'unread_notifications' => $unread->count(),
        ];
    }
}
