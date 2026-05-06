<x-filament-panels::page>
    <div>
        <div>
            <x-filament::section>
                <x-slot name="heading">
                    {{ __('security.sections.two_factor') }}
                </x-slot>
                <x-slot name="description">
                    {{ __('security.descriptions.two_factor') }}
                </x-slot>
                <div class="p-8">
                    <div class="max-w-3xl">
                        {{ $this->twoFactorForm }}
                    </div>
                </div>
            </x-filament::section>
        </div>

        <div aria-hidden="true" style="height: 3rem;"></div>

        <div>
            <x-filament::section>
                <x-slot name="heading">
                    {{ __('security.sections.password') }}
                </x-slot>
                <x-slot name="description">
                    {{ __('security.descriptions.password') }}
                </x-slot>
                <div class="p-8">
                    <div class="max-w-3xl">
                        <div class="rounded-xl border border-gray-200 bg-white p-8 dark:border-gray-700 dark:bg-gray-900">
                            <form wire:submit="updatePassword" class="space-y-5">
                                <div class="[&_.fi-fo-field-wrp]:!gap-y-2 [&_.fi-fo-component-ctn]:!mt-1 [&_.fi-fo-field-wrp:not(:last-child)]:!mb-5">
                                    {{ $this->passwordForm }}
                                </div>
                                <div style="margin-top: 1.25rem;">
                                    <x-filament::button type="submit">
                                        {{ __('security.actions.update_password') }}
                                    </x-filament::button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </x-filament::section>
        </div>

        <div aria-hidden="true" style="height: 3rem;"></div>

        <div>
            <x-filament::section>
                <x-slot name="heading">
                    {{ __('security.sections.sessions') }}
                </x-slot>
                <x-slot name="description">
                    {{ __('security.descriptions.sessions') }}
                </x-slot>

                @php($sessions = $this->visibleSessions)

                <div class="p-8">
                    @if ($sessions->isEmpty())
                        <div class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                            <div class="font-medium text-gray-950 dark:text-white">
                                {{ __('security.sessions.empty_heading') }}
                            </div>
                            <div class="mt-1">
                                {{ __('security.sessions.empty_description') }}
                            </div>
                        </div>
                    @else
                        <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
                            <div class="overflow-x-auto">
                                <table
                                    style="width: 100%; min-width: 48rem; border-collapse: separate; border-spacing: 0;"
                                    class="divide-y divide-gray-200 dark:divide-gray-700"
                                >
                                    <colgroup>
                                        <col>
                                        <col style="width: 10rem;">
                                        <col style="width: 12rem;">
                                        <col style="width: 8rem;">
                                        <col style="width: 12rem;">
                                    </colgroup>
                                    <thead class="bg-gray-50 dark:bg-gray-800">
                                        <tr>
                                            <th style="padding: 0.75rem 1.5rem; text-align: left; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; white-space: nowrap;" class="text-gray-500 dark:text-gray-400">
                                                {{ __('security.fields.user') }}
                                            </th>
                                            <th style="padding: 0.75rem 1.5rem; text-align: left; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; white-space: nowrap;" class="text-gray-500 dark:text-gray-400">
                                                {{ __('security.fields.ip_address') }}
                                            </th>
                                            <th style="padding: 0.75rem 1.5rem; text-align: left; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; white-space: nowrap;" class="text-gray-500 dark:text-gray-400">
                                                {{ __('security.fields.last_activity_at') }}
                                            </th>
                                            <th style="padding: 0.75rem 1.5rem; text-align: left; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; white-space: nowrap;" class="text-gray-500 dark:text-gray-400">
                                                {{ __('security.fields.status') }}
                                            </th>
                                            <th style="padding: 0.75rem 1.5rem; text-align: right; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; white-space: nowrap;" class="text-gray-500 dark:text-gray-400">
                                                {{ __('security.actions.revoke') }}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                                        @foreach ($sessions as $session)
                                            <tr>
                                                <td style="padding: 0.75rem 1.5rem; font-size: 0.875rem; font-weight: 600; max-width: 20rem;" class="text-gray-900 dark:text-gray-100">
                                                    <div style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                        {{ $session->user?->name ?? '—' }}
                                                    </div>
                                                </td>
                                                <td style="padding: 0.75rem 1.5rem; font-size: 0.875rem; white-space: nowrap;" class="text-gray-700 dark:text-gray-300">
                                                    {{ $session->ip_address ?? '—' }}
                                                </td>
                                                <td style="padding: 0.75rem 1.5rem; font-size: 0.875rem; white-space: nowrap;" class="text-gray-700 dark:text-gray-300">
                                                    {{ optional($session->last_activity_at)->diffForHumans() }}
                                                </td>
                                                <td style="padding: 0.75rem 1.5rem; font-size: 0.875rem; white-space: nowrap;" class="text-gray-700 dark:text-gray-300">
                                                    @php($status = (string) $session->status->value)
                                                    <x-filament::badge :color="$status === 'active' ? 'success' : 'gray'">
                                                        {{ __('security.status.' . $status) }}
                                                    </x-filament::badge>
                                                </td>
                                                <td style="padding: 0.75rem 1.5rem; text-align: right; white-space: nowrap;">
                                                    <x-filament::button
                                                        size="xs"
                                                        color="danger"
                                                        wire:click="revokeSession({{ $session->id }})"
                                                        wire:confirm="{{ __('security.actions.revoke_confirm_description') }}"
                                                    >
                                                        {{ __('security.actions.revoke') }}
                                                    </x-filament::button>
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
