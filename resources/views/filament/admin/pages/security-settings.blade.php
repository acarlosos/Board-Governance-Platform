<x-filament-panels::page>
    <div class="bgp-stack">
        <div>
            <x-filament::section>
                <x-slot name="heading">
                    {{ __('security.sections.two_factor') }}
                </x-slot>
                <x-slot name="description">
                    {{ __('security.descriptions.two_factor') }}
                </x-slot>
                <div class="bgp-content">
                    <div class="max-w-3xl">
                        {{ $this->twoFactorForm }}
                    </div>
                </div>
            </x-filament::section>
        </div>

        <div>
            <x-filament::section>
                <x-slot name="heading">
                    {{ __('security.sections.password') }}
                </x-slot>
                <x-slot name="description">
                    {{ __('security.descriptions.password') }}
                </x-slot>
                <div class="bgp-content">
                    <div class="max-w-3xl">
                        <div class="bgp-form-card">
                            <form wire:submit="updatePassword" class="space-y-5">
                                <div class="[&_.fi-fo-field-wrp]:!gap-y-2 [&_.fi-fo-component-ctn]:!mt-1 [&_.fi-fo-field-wrp:not(:last-child)]:!mb-5">
                                    {{ $this->passwordForm }}
                                </div>
                                <div class="bgp-button-spacer">
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

        <div>
            <x-filament::section>
                <x-slot name="heading">
                    {{ __('security.sections.sessions') }}
                </x-slot>
                <x-slot name="description">
                    {{ __('security.descriptions.sessions') }}
                </x-slot>

                @php($sessions = $this->visibleSessions)

                <div class="bgp-content">
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
                        <div class="bgp-table-shell">
                            <div class="bgp-table-scroll">
                                <table class="bgp-table">
                                    <colgroup>
                                        <col>
                                        <col width="160">
                                        <col width="192">
                                        <col width="128">
                                        <col width="192">
                                    </colgroup>
                                    <thead class="bgp-thead">
                                        <tr class="bgp-tr">
                                            <th class="bgp-th">
                                                {{ __('security.fields.user') }}
                                            </th>
                                            <th class="bgp-th">
                                                {{ __('security.fields.ip_address') }}
                                            </th>
                                            <th class="bgp-th">
                                                {{ __('security.fields.last_activity_at') }}
                                            </th>
                                            <th class="bgp-th">
                                                {{ __('security.fields.status') }}
                                            </th>
                                            <th class="bgp-th bgp-th--right">
                                                {{ __('security.actions.revoke') }}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($sessions as $session)
                                            <tr class="bgp-tr">
                                                <td class="bgp-td bgp-td--strong">
                                                    <div class="bgp-ellipsis">
                                                        {{ $session->user?->name ?? '—' }}
                                                    </div>
                                                </td>
                                                <td class="bgp-td">
                                                    {{ $session->ip_address ?? '—' }}
                                                </td>
                                                <td class="bgp-td">
                                                    {{ optional($session->last_activity_at)->diffForHumans() }}
                                                </td>
                                                <td class="bgp-td">
                                                    @php($status = (string) $session->status->value)
                                                    <x-filament::badge :color="$status === 'active' ? 'success' : 'gray'">
                                                        {{ __('security.status.' . $status) }}
                                                    </x-filament::badge>
                                                </td>
                                                <td class="bgp-td bgp-td--right">
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
