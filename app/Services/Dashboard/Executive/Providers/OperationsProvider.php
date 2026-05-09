<?php

namespace App\Services\Dashboard\Executive\Providers;

use App\Enums\DashboardMetricsPeriod;
use App\Enums\MinuteStatus;
use App\Enums\NotificationStatus;
use App\Models\Meeting;
use App\Models\Minute;
use App\Models\NotificationCenter;
use App\Models\User;
use App\Services\Dashboard\Executive\Snapshot\OperationsBlock;
use App\Services\Reporting\ReportingContext;

final class OperationsProvider
{
    public function __construct() {}

    public function build(User $actor, DashboardMetricsPeriod $period): OperationsBlock
    {
        $ctx = ReportingContext::fromUser($actor);

        return new OperationsBlock(
            minutesPendingReview: $this->countMinutesPendingReview($ctx, $period),
            meetingsThisMonth: $this->countMeetingsScheduledThisCalendarMonth($ctx),
            notificationsUnread: $this->countUnreadNotifications($ctx, $period),
        );
    }

    private function countMinutesPendingReview(ReportingContext $ctx, DashboardMetricsPeriod $period): int
    {
        $pending = Minute::query()->withoutGlobalScopes();
        $ctx->restrictToTenant($pending);
        $period->applyToCreatedAt($pending);
        $pending->where('status', MinuteStatus::InReview->value);

        return $pending->count();
    }

    /**
     * Reuniões com {@see Meeting::scheduled_at} no mês corrente (calendário), alinhado a {@see DashboardMetricsService::meetingMetrics}.
     */
    private function countMeetingsScheduledThisCalendarMonth(ReportingContext $ctx): int
    {
        $thisMonthStart = now()->copy()->startOfMonth();
        $thisMonthEnd = now()->copy()->endOfMonth();

        $thisMonth = Meeting::query()->withoutGlobalScopes();
        $ctx->restrictToTenant($thisMonth);
        $thisMonth->whereNotNull($thisMonth->qualifyColumn('scheduled_at'))
            ->whereBetween($thisMonth->qualifyColumn('scheduled_at'), [$thisMonthStart, $thisMonthEnd]);

        return $thisMonth->count();
    }

    private function countUnreadNotifications(ReportingContext $ctx, DashboardMetricsPeriod $period): int
    {
        $unread = NotificationCenter::query()->withoutGlobalScopes();
        $ctx->restrictToTenant($unread);
        $period->applyToCreatedAt($unread);
        $unread->where('status', NotificationStatus::Unread->value);

        return $unread->count();
    }
}
