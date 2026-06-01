<?php

namespace App\Services\Dashboard\Executive\Providers;

use App\Enums\DashboardMetricsPeriod;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\Dashboard\Executive\Snapshot\ActivityItem;
use App\Services\Reporting\ReportingContext;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

final class ActivityFeedProvider
{
    public function __construct() {}

    /**
     * @return array<int, ActivityItem>
     */
    public function build(User $actor, DashboardMetricsPeriod $period): array
    {
        unset($period);

        $ctx = ReportingContext::fromUser($actor);

        if ($ctx->isGlobalScope()) {
            return [];
        }

        if (! Gate::forUser($actor)->allows('viewAny', AuditLog::class)) {
            return [];
        }

        if ($ctx->tenantId() === null) {
            return [];
        }

        $activityMax = (int) config('board.dashboard.activity_max');
        $buffer = (int) ceil($activityMax * 0.5);
        $fetchLimit = $activityMax + $buffer;

        $query = AuditLog::query()->withoutGlobalScopes(); // reason: strip TenantScope; tenant via $ctx->restrictToTenant().
        $ctx->restrictToTenant($query);

        /** @var Collection<int, AuditLog> $logs */
        $logs = $query->orderByDesc('created_at')
            ->take($fetchLimit)
            ->get();

        /** @var array<int, ActivityItem> $items */
        $items = [];

        foreach ($logs as $log) {
            if (count($items) >= $activityMax) {
                break;
            }

            $occurredAt = $log->created_at !== null
                ? CarbonImmutable::parse($log->created_at)
                : CarbonImmutable::now();

            $auditable = $this->resolveAuditableSafely($log);

            $resourceKey = self::shortResourceKey($log->auditable_type);

            if ($auditable === null) {
                $items[] = new ActivityItem(
                    resourceType: $resourceKey,
                    resourceId: null,
                    summary: (string) $log->action,
                    occurredAt: $occurredAt,
                );

                continue;
            }

            if (! Gate::forUser($actor)->allows('view', $auditable)) {
                continue;
            }

            $pk = $auditable->getKey();

            $items[] = new ActivityItem(
                resourceType: $resourceKey,
                resourceId: is_numeric($pk) ? (int) $pk : null,
                summary: (string) $log->action,
                occurredAt: $occurredAt,
            );
        }

        return $items;
    }

    /**
     * Uma tentativa isolada por log; modelo inexistente => null (órfeo).
     */
    private function resolveAuditableSafely(AuditLog $log): mixed
    {
        $type = $log->auditable_type;

        $id = $log->auditable_id;

        if ($type === null || $type === '' || $id === null) {
            return null;
        }

        if (! is_string($type) || ! class_exists($type)) {
            return null;
        }

        try {
            /** @var class-string<Model> $type */
            return $type::query()->withoutGlobalScopes()->find($id); // reason: polimórfico por classe/id; Gate::view valida acesso ao auditable.
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  non-string|null|string  $class
     */
    private static function shortResourceKey(?string $class): string
    {
        if ($class === null || $class === '') {
            return 'audit';
        }

        return Str::snake(class_basename($class));
    }
}
