<?php

namespace App\Services\Dashboard\Executive\Providers;

use App\Enums\DashboardMetricsPeriod;
use App\Enums\SignatureSignerStatus;
use App\Enums\TaskStatus;
use App\Enums\VoteStatus;
use App\Models\SignatureRequestSigner;
use App\Models\Task;
use App\Models\User;
use App\Models\Vote;
use App\Services\Dashboard\Executive\Snapshot\Enums\PriorityUrgency;
use App\Services\Dashboard\Executive\Snapshot\PriorityItem;
use App\Services\Reporting\ReportingContext;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Gate;

final class PrioritiesProvider
{
    public function __construct() {}

    /**
     * @return array<int, PriorityItem>
     */
    public function build(User $actor, DashboardMetricsPeriod $period): array
    {
        unset($period);

        $ctx = ReportingContext::fromUser($actor);

        if ($ctx->isGlobalScope()) {
            return [];
        }

        $tenantId = $ctx->tenantId();

        if ($tenantId === null) {
            return [];
        }

        $prioritiesMax = (int) config('board.dashboard.priorities_max');
        $buffer = (int) ceil($prioritiesMax * 0.5);
        $fetchLimit = $prioritiesMax + $buffer;

        $isTaskManager = $actor->hasRole('tenant_admin') || $actor->can('manage_tasks');
        $isSignerManager = $actor->hasRole('tenant_admin')
            || $actor->can('manage_signatures')
            || $actor->can('manage_settings');
        $isVoteManager = $actor->hasRole('tenant_admin') || $actor->can('manage_votes');

        $candidates = array_merge(
            $this->loadTaskCandidates($ctx, $actor, $isTaskManager, $fetchLimit),
            $this->loadSignerCandidates($ctx, $actor, $isSignerManager, $fetchLimit),
            $this->loadVoteCandidates($ctx, $actor, $isVoteManager, $fetchLimit),
        );

        usort($candidates, static function (array $a, array $b): int {
            $c = $a['urgencyRank'] <=> $b['urgencyRank'];
            if ($c !== 0) {
                return $c;
            }

            return $a['sortTimestamp'] <=> $b['sortTimestamp'];
        });

        /** @var array<int, PriorityItem> $visible */
        $visible = [];

        foreach ($candidates as $row) {
            if (count($visible) >= $prioritiesMax) {
                break;
            }

            if (! Gate::forUser($actor)->allows('view', $row['model'])) {
                continue;
            }

            $visible[] = $row['dto'];
        }

        return $visible;
    }

    /**
     * @return list<array{model: object, dto: PriorityItem, urgencyRank: int, sortTimestamp: int}>
     */
    private function loadTaskCandidates(
        ReportingContext $ctx,
        User $actor,
        bool $isTaskManager,
        int $fetchLimit,
    ): array {
        $q = Task::query()->withoutGlobalScopes();
        $ctx->restrictToTenant($q);
        $q->whereNotIn('status', [TaskStatus::Completed, TaskStatus::Cancelled]);

        if (! $isTaskManager) {
            $q->where('assigned_to', $actor->id);
        }

        $q->orderBy($q->qualifyColumn('due_date'))
            ->orderBy($q->qualifyColumn('id'))
            ->limit($fetchLimit)
            ->with(['related']);

        $out = [];

        foreach ($q->get() as $task) {
            $dueAt = $task->due_date !== null
                ? CarbonImmutable::parse($task->due_date)
                : null;
            $urgency = self::urgencyFrom($dueAt);
            $dto = new PriorityItem(
                resourceType: 'task',
                id: $task->id,
                title: $task->title,
                urgency: $urgency,
                dueAt: $dueAt,
            );
            $out[] = [
                'model' => $task,
                'dto' => $dto,
                'urgencyRank' => self::urgencyRank($urgency),
                'sortTimestamp' => $dueAt?->timestamp ?? PHP_INT_MAX,
            ];
        }

        return $out;
    }

    /**
     * @return list<array{model: object, dto: PriorityItem, urgencyRank: int, sortTimestamp: int}>
     */
    private function loadSignerCandidates(
        ReportingContext $ctx,
        User $actor,
        bool $isSignerManager,
        int $fetchLimit,
    ): array {
        $q = SignatureRequestSigner::query()->withoutGlobalScopes();
        $ctx->restrictToTenant($q);
        $q->where('status', SignatureSignerStatus::Pending->value);

        if (! $isSignerManager) {
            $q->where('user_id', $actor->id);
        }

        $q->orderBy($q->qualifyColumn('id'))
            ->limit($fetchLimit)
            ->with(['request']);

        $out = [];

        foreach ($q->get() as $signer) {
            $urgency = PriorityUrgency::Normal;
            $dto = new PriorityItem(
                resourceType: 'signature_signer',
                id: $signer->id,
                title: $signer->request?->title ?? $signer->name,
                urgency: $urgency,
                dueAt: null,
            );
            $out[] = [
                'model' => $signer,
                'dto' => $dto,
                'urgencyRank' => self::urgencyRank($urgency),
                'sortTimestamp' => PHP_INT_MAX,
            ];
        }

        return $out;
    }

    /**
     * @return list<array{model: object, dto: PriorityItem, urgencyRank: int, sortTimestamp: int}>
     */
    private function loadVoteCandidates(
        ReportingContext $ctx,
        User $actor,
        bool $isVoteManager,
        int $fetchLimit,
    ): array {
        $q = Vote::query()->withoutGlobalScopes();
        $ctx->restrictToTenant($q);
        $q->where('status', VoteStatus::Open);

        if (! $isVoteManager) {
            $q->whereHas('meeting.participants', fn ($q2) => $q2->where('user_id', $actor->id));
        }

        $q->orderBy($q->qualifyColumn('ends_at'))
            ->orderBy($q->qualifyColumn('id'))
            ->limit($fetchLimit)
            ->with(['meeting']);

        $out = [];

        foreach ($q->get() as $vote) {
            $dueAt = $vote->ends_at !== null
                ? CarbonImmutable::parse($vote->ends_at)
                : null;
            $urgency = self::urgencyFrom($dueAt);
            $dto = new PriorityItem(
                resourceType: 'vote',
                id: $vote->id,
                title: $vote->title,
                urgency: $urgency,
                dueAt: $dueAt,
            );
            $out[] = [
                'model' => $vote,
                'dto' => $dto,
                'urgencyRank' => self::urgencyRank($urgency),
                'sortTimestamp' => $dueAt?->timestamp ?? PHP_INT_MAX,
            ];
        }

        return $out;
    }

    private static function urgencyRank(PriorityUrgency $u): int
    {
        return match ($u) {
            PriorityUrgency::Overdue => 0,
            PriorityUrgency::DueToday => 1,
            PriorityUrgency::DueThisWeek => 2,
            PriorityUrgency::Normal => 3,
        };
    }

    private static function urgencyFrom(?CarbonImmutable $due): PriorityUrgency
    {
        if ($due === null) {
            return PriorityUrgency::Normal;
        }

        $dueDay = $due->copy()->startOfDay();
        $today = CarbonImmutable::now()->startOfDay();

        if ($dueDay->lt($today)) {
            return PriorityUrgency::Overdue;
        }

        if ($dueDay->eq($today)) {
            return PriorityUrgency::DueToday;
        }

        $endOfWeek = $today->addDays(7);

        if ($dueDay->lte($endOfWeek)) {
            return PriorityUrgency::DueThisWeek;
        }

        return PriorityUrgency::Normal;
    }
}
