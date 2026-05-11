@php
    /** @var \App\Services\Dashboard\Executive\Snapshot\ExecutiveDashboardSnapshot|null $snapshot */
    use App\Enums\DashboardMetricsPeriod;

    $updatedAtLabel = null;
    if ($snapshot !== null) {
        $updatedAtLabel = $snapshot->generatedAt
            ->setTimezone(config('app.timezone', 'UTC'))
            ->format('H:i');
    }
@endphp

<x-filament-widgets::widget>
    <x-filament::section
        :heading="__('dashboard.executive.hero.heading')"
        :description="$updatedAtLabel !== null
            ? __('dashboard.executive.hero.updated_at', ['time' => $updatedAtLabel])
            : null"
    >
        <div class="bgp-dashboard__hero">
            <div class="bgp-dashboard__hero-period">
                <label for="bgp-dashboard-period" class="bgp-dashboard__hero-period-label">
                    {{ __('dashboard.executive.hero.period_label') }}
                </label>
                <select
                    id="bgp-dashboard-period"
                    class="bgp-dashboard__hero-period-select"
                    wire:model.live="period"
                >
                    @foreach (DashboardMetricsPeriod::filterOptions() as $option)
                        <option value="{{ $option->value }}">
                            {{ __('dashboard.executive.hero.period.' . $option->value) }}
                        </option>
                    @endforeach
                </select>
            </div>

            @if ($snapshot !== null)
                <dl class="bgp-dashboard__hero-highlights">
                    <div class="bgp-dashboard__hero-stat">
                        <dt>{{ __('dashboard.executive.hero.tasks_overdue') }}</dt>
                        <dd>{{ $snapshot->hero->tasksOverdue }}</dd>
                    </div>
                    <div class="bgp-dashboard__hero-stat">
                        <dt>{{ __('dashboard.executive.hero.votes_open') }}</dt>
                        <dd>{{ $snapshot->hero->votesOpen }}</dd>
                    </div>
                    <div class="bgp-dashboard__hero-stat">
                        <dt>{{ __('dashboard.executive.hero.signatures_pending') }}</dt>
                        <dd>{{ $snapshot->hero->signaturesPending }}</dd>
                    </div>
                </dl>

                @if ($snapshot->hero->nextMeetingAt !== null)
                    <p class="bgp-dashboard__hero-next-meeting">
                        {{ __('dashboard.executive.hero.next_meeting_at', [
                            'date' => $snapshot->hero->nextMeetingAt
                                ->setTimezone(config('app.timezone', 'UTC'))
                                ->isoFormat('LLL'),
                        ]) }}
                    </p>
                @endif
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
