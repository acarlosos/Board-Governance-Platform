<x-filament-panels::page>
    <div class="fi-section-content-ctn bgp-stack">
        <div>
        <x-filament::section>
            <x-slot name="heading">
                {{ __('reports.fields.period') }}
            </x-slot>
            <x-slot name="description">
                {{ __('reports.helpers.period') }}
            </x-slot>
            <div class="px-8 py-6">
                <div class="max-w-md">
                    <select
                        id="reports-period"
                        wire:model.live="period"
                        class="fi-input block w-full rounded-lg bg-white px-3 py-2 text-sm text-gray-950 outline-none ring-1 ring-gray-950/10 dark:bg-gray-850 dark:text-white dark:ring-white/15"
                    >
                        @foreach (\App\Enums\DashboardMetricsPeriod::filterOptions() as $option)
                            <option value="{{ $option->value }}">{{ __('reports.periods.' . $option->value) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </x-filament::section>
        </div>

        @php($summary = $this->reportSummary)
        @php($hasAnyRows = ! empty($summary['tasks_by_status']) || ! empty($summary['meetings_by_month']) || ! empty($summary['votes_by_status']) || ! empty($summary['signatures_by_status']))

        @if (! $hasAnyRows)
            <div>
            <x-filament::section :heading="__('reports.empty.heading')">
                <div class="p-8">
                    <div class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                        {{ __('reports.empty.description') }}
                    </div>
                </div>
            </x-filament::section>
            </div>
        @endif

        <div>
        <x-filament::section :heading="__('reports.sections.tasks_by_status')">
            <div class="bgp-content">
                @php($rows = $summary['tasks_by_status'] ?? [])

                @if (empty($rows))
                    <div class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                        {{ __('reports.empty.no_rows') }}
                    </div>
                @else
                    <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-800">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                            {{ __('tasks.fields.status') }}
                                        </th>
                                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                            {{ __('reports.fields.quantity') }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                                    @foreach ($rows as $status => $count)
                                        <tr>
                                            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                                                {{ __('tasks.status.' . $status) }}
                                            </td>
                                            <td class="px-6 py-4 text-right text-sm tabular-nums text-gray-700 dark:text-gray-300">
                                                {{ $count }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        </x-filament::section>
        </div>

        <div>
        <x-filament::section :heading="__('reports.sections.meetings_by_month')">
            <div class="bgp-content">
                @php($rows = $summary['meetings_by_month'] ?? [])

                @if (empty($rows))
                    <div class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                        {{ __('reports.empty.no_rows') }}
                    </div>
                @else
                    <div class="bgp-table-shell">
                        <div class="bgp-table-scroll">
                            <table class="bgp-table bgp-table--compact">
                                <colgroup>
                                    <col>
                                    <col width="160">
                                </colgroup>
                                <thead class="bgp-thead">
                                    <tr class="bgp-tr">
                                        <th class="bgp-th">
                                            {{ __('reports.fields.month') }}
                                        </th>
                                        <th class="bgp-th bgp-th--right">
                                            {{ __('meetings.navigation_label') }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($rows as $ym => $count)
                                        @php($parsed = \Illuminate\Support\Carbon::createFromFormat('Y-m', (string) $ym))
                                        @php($label = $parsed ? $parsed->translatedFormat('M/Y') : (string) $ym)

                                        <tr class="bgp-tr">
                                            <td class="bgp-td">
                                                {{ $label }}
                                            </td>
                                            <td class="bgp-td bgp-td--right">
                                                {{ $count }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        </x-filament::section>
        </div>

        <div>
        <x-filament::section :heading="__('reports.sections.votes_by_status')">
            <div class="bgp-content">
                @php($rows = $summary['votes_by_status'] ?? [])

                @if (empty($rows))
                    <div class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                        {{ __('reports.empty.no_rows') }}
                    </div>
                @else
                    <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-800">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                            {{ __('votes.fields.status') }}
                                        </th>
                                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                            {{ __('reports.fields.quantity') }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                                    @foreach ($rows as $status => $count)
                                        <tr>
                                            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                                                {{ __('votes.status.' . $status) }}
                                            </td>
                                            <td class="px-6 py-4 text-right text-sm tabular-nums text-gray-700 dark:text-gray-300">
                                                {{ $count }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        </x-filament::section>
        </div>

        <div>
        <x-filament::section :heading="__('reports.sections.signatures_by_status')">
            <div class="bgp-content">
                @php($rows = $summary['signatures_by_status'] ?? [])

                @if (empty($rows))
                    <div class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                        {{ __('reports.empty.no_rows') }}
                    </div>
                @else
                    <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-800">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                            {{ __('signatures.fields.status') }}
                                        </th>
                                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                            {{ __('reports.fields.quantity') }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                                    @foreach ($rows as $status => $count)
                                        <tr>
                                            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                                                {{ __('signatures.status.' . $status) }}
                                            </td>
                                            <td class="px-6 py-4 text-right text-sm tabular-nums text-gray-700 dark:text-gray-300">
                                                {{ $count }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
