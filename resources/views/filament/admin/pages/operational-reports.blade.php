<x-filament-panels::page>
    <div class="fi-section-content-ctn space-y-10">
        <div class="max-w-md">
            <label for="reports-period" class="mb-2 block text-sm font-medium text-gray-950 dark:text-white">
                {{ __('reports.fields.period') }}
            </label>
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

        @php($summary = $this->reportSummary)

        <div>
            <h2 class="mb-4 text-lg font-semibold">{{ __('reports.sections.tasks_by_status') }}</h2>
            <div class="overflow-x-auto rounded-lg ring-1 ring-gray-950/10 dark:ring-white/15">
                <table class="w-full divide-y divide-gray-200 text-sm dark:divide-white/10">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-2 text-start font-medium">{{ __('tasks.fields.status') }}</th>
                            <th class="px-4 py-2 text-end font-medium">{{ __('reports.fields.quantity') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                        @forelse ($summary['tasks_by_status'] ?? [] as $status => $count)
                            <tr>
                                <td class="px-4 py-2">{{ __('tasks.status.' . $status) }}</td>
                                <td class="px-4 py-2 text-end">{{ $count }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ __('reports.empty.no_rows') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div>
            <h2 class="mb-4 text-lg font-semibold">{{ __('reports.sections.meetings_by_month') }}</h2>
            <div class="overflow-x-auto rounded-lg ring-1 ring-gray-950/10 dark:ring-white/15">
                <table class="w-full divide-y divide-gray-200 text-sm dark:divide-white/10">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-2 text-start font-medium">{{ __('reports.fields.month') }}</th>
                            <th class="px-4 py-2 text-end font-medium">{{ __('meetings.navigation_label') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                        @foreach ($summary['meetings_by_month'] ?? [] as $ym => $count)
                            <tr>
                                <td class="px-4 py-2">{{ $ym }}</td>
                                <td class="px-4 py-2 text-end">{{ $count }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div>
            <h2 class="mb-4 text-lg font-semibold">{{ __('reports.sections.votes_by_status') }}</h2>
            <div class="overflow-x-auto rounded-lg ring-1 ring-gray-950/10 dark:ring-white/15">
                <table class="w-full divide-y divide-gray-200 text-sm dark:divide-white/10">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-2 text-start font-medium">{{ __('votes.fields.status') }}</th>
                            <th class="px-4 py-2 text-end font-medium">{{ __('reports.fields.quantity') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                        @forelse ($summary['votes_by_status'] ?? [] as $status => $count)
                            <tr>
                                <td class="px-4 py-2">{{ __('votes.status.' . $status) }}</td>
                                <td class="px-4 py-2 text-end">{{ $count }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ __('reports.empty.no_rows') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div>
            <h2 class="mb-4 text-lg font-semibold">{{ __('reports.sections.signatures_by_status') }}</h2>
            <div class="overflow-x-auto rounded-lg ring-1 ring-gray-950/10 dark:ring-white/15">
                <table class="w-full divide-y divide-gray-200 text-sm dark:divide-white/10">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-2 text-start font-medium">{{ __('signatures.fields.status') }}</th>
                            <th class="px-4 py-2 text-end font-medium">{{ __('reports.fields.quantity') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                        @forelse ($summary['signatures_by_status'] ?? [] as $status => $count)
                            <tr>
                                <td class="px-4 py-2">{{ __('signatures.status.' . $status) }}</td>
                                <td class="px-4 py-2 text-end">{{ $count }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ __('reports.empty.no_rows') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
