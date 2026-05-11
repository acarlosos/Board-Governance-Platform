@php
    /** @var \App\Services\Dashboard\Executive\Snapshot\ExecutiveDashboardSnapshot|null $snapshot */

    // KpiStrip expõe apenas 4 grupos por design (D8); minutes/notifications vivem em Operations/Hero.
    $groups = [];
    if ($snapshot !== null) {
        $groups = [
            'tasks' => $snapshot->kpis->tasks,
            'meetings' => $snapshot->kpis->meetings,
            'votes' => $snapshot->kpis->votes,
            'signatures' => $snapshot->kpis->signatures,
        ];
    }
@endphp

<x-filament-widgets::widget>
    <x-filament::section :heading="__('dashboard.executive.kpis.heading')">
        @if ($snapshot === null)
            <p class="bgp-dashboard__empty">{{ __('dashboard.executive.kpis.empty') }}</p>
        @else
            <div class="bgp-dashboard__kpi-strip">
                @foreach ($groups as $group => $metrics)
                    <article class="bgp-dashboard__kpi-card">
                        <h3 class="bgp-dashboard__kpi-card-heading">
                            {{ __('dashboard.executive.kpis.' . $group . '.heading') }}
                        </h3>
                        <dl class="bgp-dashboard__kpi-card-stats">
                            @foreach ($metrics as $metricKey => $metricValue)
                                <div class="bgp-dashboard__kpi-card-stat">
                                    <dt>{{ __('dashboard.executive.kpis.' . $group . '.' . $metricKey) }}</dt>
                                    <dd>{{ $metricValue }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </article>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
