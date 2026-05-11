@php
    /** @var \App\Services\Dashboard\Executive\Snapshot\ExecutiveDashboardSnapshot|null $snapshot */
    /** @var string|null $reportsUrl */
@endphp

<x-filament-widgets::widget>
    <x-filament::section :heading="__('dashboard.executive.operations.heading')">
        @if ($snapshot === null)
            <p class="bgp-dashboard__empty">{{ __('dashboard.executive.operations.empty') }}</p>
        @else
            <dl class="bgp-dashboard__operations">
                <div class="bgp-dashboard__operations-card">
                    <dt>{{ __('dashboard.executive.operations.minutes_pending_review') }}</dt>
                    <dd>{{ $snapshot->operations->minutesPendingReview }}</dd>
                </div>
                <div class="bgp-dashboard__operations-card">
                    <dt>{{ __('dashboard.executive.operations.meetings_this_month') }}</dt>
                    <dd>{{ $snapshot->operations->meetingsThisMonth }}</dd>
                </div>
                <div class="bgp-dashboard__operations-card">
                    <dt>{{ __('dashboard.executive.operations.notifications_unread') }}</dt>
                    <dd>{{ $snapshot->operations->notificationsUnread }}</dd>
                </div>
            </dl>
        @endif

        @if ($reportsUrl !== null)
            <div class="bgp-dashboard__operations-footer">
                <a class="bgp-dashboard__operations-cta" href="{{ $reportsUrl }}">
                    {{ __('dashboard.executive.operations.cta_reports') }}
                </a>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
