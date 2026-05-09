<?php

namespace App\Services\Dashboard\Executive\Providers;

use App\Enums\DashboardMetricsPeriod;
use App\Enums\MeetingStatus;
use App\Enums\SignatureRequestStatus;
use App\Enums\TaskStatus;
use App\Enums\VoteStatus;
use App\Models\Meeting;
use App\Models\SignatureRequest;
use App\Models\Task;
use App\Models\User;
use App\Models\Vote;
use App\Services\Dashboard\Executive\Snapshot\HeroSummary;
use App\Services\Reporting\ReportingContext;
use Carbon\CarbonImmutable;

final class HeroProvider
{
    public function __construct() {}

    public function build(User $actor, DashboardMetricsPeriod $period): HeroSummary
    {
        $ctx = ReportingContext::fromUser($actor);

        $tasksOverdue = $this->countTasksOverdue($ctx);
        $votesOpen = $this->countVotesOpen($ctx, $period);
        $signaturesPending = $this->countSignaturesPending($ctx, $period);

        $next = $this->firstUpcomingMeeting($ctx);

        $nextAt = $next?->scheduled_at !== null
            ? CarbonImmutable::parse($next->scheduled_at)
            : null;

        return new HeroSummary(
            tasksOverdue: $tasksOverdue,
            votesOpen: $votesOpen,
            signaturesPending: $signaturesPending,
            nextMeetingAt: $nextAt,
            nextMeetingId: $next?->id,
        );
    }

    private function countTasksOverdue(ReportingContext $ctx): int
    {
        $overdue = Task::query()->withoutGlobalScopes();
        $ctx->restrictToTenant($overdue);
        $overdue->whereIn('status', [TaskStatus::Pending->value, TaskStatus::InProgress->value])
            ->whereNotNull($overdue->qualifyColumn('due_date'))
            ->where($overdue->qualifyColumn('due_date'), '<', now());

        return $overdue->count();
    }

    private function countVotesOpen(ReportingContext $ctx, DashboardMetricsPeriod $period): int
    {
        $open = Vote::query()->withoutGlobalScopes();
        $ctx->restrictToTenant($open);
        $period->applyToCreatedAt($open);
        $open->where('status', VoteStatus::Open->value);

        return $open->count();
    }

    private function countSignaturesPending(ReportingContext $ctx, DashboardMetricsPeriod $period): int
    {
        $pending = SignatureRequest::query()->withoutGlobalScopes();
        $ctx->restrictToTenant($pending);
        $period->applyToCreatedAt($pending);
        $pending->whereIn('status', [
            SignatureRequestStatus::Draft->value,
            SignatureRequestStatus::Sent->value,
            SignatureRequestStatus::Failed->value,
        ]);

        return $pending->count();
    }

    private function firstUpcomingMeeting(ReportingContext $ctx): ?Meeting
    {
        $q = Meeting::query()->withoutGlobalScopes();
        $ctx->restrictToTenant($q);
        $q->whereNotNull($q->qualifyColumn('scheduled_at'))
            ->where($q->qualifyColumn('scheduled_at'), '>=', now())
            ->whereIn('status', [
                MeetingStatus::Scheduled->value,
                MeetingStatus::InProgress->value,
            ])
            ->orderBy($q->qualifyColumn('scheduled_at'));

        return $q->first();
    }
}
