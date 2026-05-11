@php
    /** @var \App\Services\Dashboard\Executive\Snapshot\ExecutiveDashboardSnapshot|null $snapshot */

    $priorities = $snapshot?->priorities ?? [];
    $activity = $snapshot?->activity ?? [];
@endphp

<x-filament-widgets::widget>
    <x-filament::section :heading="__('dashboard.executive.priorities.heading')">
        @if (count($priorities) === 0)
            <p class="bgp-dashboard__empty">{{ __('dashboard.executive.priorities.empty') }}</p>
        @else
            <ul class="bgp-dashboard__priorities">
                @foreach ($priorities as $item)
                    <li class="bgp-dashboard__priorities-item bgp-dashboard__priorities-item--{{ $item->urgency->value }}">
                        <div class="bgp-dashboard__priorities-item-title">
                            <span class="bgp-dashboard__priorities-item-type">
                                {{ __('dashboard.executive.priorities.resource.' . $item->resourceType) }}
                            </span>
                            <span class="bgp-dashboard__priorities-item-label">
                                {{ $item->title }}
                            </span>
                        </div>
                        <div class="bgp-dashboard__priorities-item-meta">
                            <span class="bgp-dashboard__priorities-item-urgency">
                                {{ __('dashboard.executive.priorities.urgency.' . $item->urgency->value) }}
                            </span>
                            @if ($item->dueAt !== null)
                                <time
                                    class="bgp-dashboard__priorities-item-due"
                                    datetime="{{ $item->dueAt->toIso8601String() }}"
                                >
                                    {{ $item->dueAt
                                        ->setTimezone(config('app.timezone', 'UTC'))
                                        ->isoFormat('LL') }}
                                </time>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-filament::section>

    <x-filament::section :heading="__('dashboard.executive.activity.heading')">
        @if (count($activity) === 0)
            <p class="bgp-dashboard__empty">{{ __('dashboard.executive.activity.empty') }}</p>
        @else
            <ul class="bgp-dashboard__activity">
                @foreach ($activity as $entry)
                    <li class="bgp-dashboard__activity-item">
                        <span class="bgp-dashboard__activity-resource">
                            {{ $entry->resourceType }}
                        </span>
                        <span class="bgp-dashboard__activity-summary">
                            {{ $entry->summary }}
                        </span>
                        <time
                            class="bgp-dashboard__activity-time"
                            datetime="{{ $entry->occurredAt->toIso8601String() }}"
                        >
                            {{ $entry->occurredAt
                                ->setTimezone(config('app.timezone', 'UTC'))
                                ->format('H:i') }}
                        </time>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
