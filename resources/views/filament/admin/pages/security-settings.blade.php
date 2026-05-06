<x-filament-panels::page>
    <div class="space-y-10">
        <section>
            <header class="mb-4">
                <h2 class="text-lg font-semibold">{{ __('security.sections.two_factor') }}</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __('security.descriptions.two_factor') }}</p>
            </header>
            <div class="rounded-xl bg-white p-6 ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                {{ $this->twoFactorForm }}
            </div>
        </section>

        <section>
            <header class="mb-4">
                <h2 class="text-lg font-semibold">{{ __('security.sections.password') }}</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __('security.descriptions.password') }}</p>
            </header>
            <div class="rounded-xl bg-white p-6 ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <form wire:submit="updatePassword" class="space-y-4">
                    {{ $this->passwordForm }}
                    <div>
                        <x-filament::button type="submit">
                            {{ __('security.actions.update_password') }}
                        </x-filament::button>
                    </div>
                </form>
            </div>
        </section>

        <section>
            <header class="mb-4">
                <h2 class="text-lg font-semibold">{{ __('security.sections.sessions') }}</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __('security.descriptions.sessions') }}</p>
            </header>
            <div class="overflow-x-auto rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10">
                <table class="w-full divide-y divide-gray-200 text-sm dark:divide-white/10">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-2 text-start font-medium">{{ __('security.fields.user') }}</th>
                            <th class="px-4 py-2 text-start font-medium">{{ __('security.fields.ip_address') }}</th>
                            <th class="px-4 py-2 text-start font-medium">{{ __('security.fields.last_activity_at') }}</th>
                            <th class="px-4 py-2 text-start font-medium">{{ __('security.fields.status') }}</th>
                            <th class="px-4 py-2 text-end font-medium"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/10 bg-white dark:bg-gray-900">
                        @forelse ($this->visibleSessions as $session)
                            <tr>
                                <td class="px-4 py-2">{{ $session->user?->name ?? '—' }}</td>
                                <td class="px-4 py-2">{{ $session->ip_address ?? '—' }}</td>
                                <td class="px-4 py-2">{{ optional($session->last_activity_at)->diffForHumans() }}</td>
                                <td class="px-4 py-2">{{ __('security.status.' . $session->status->value) }}</td>
                                <td class="px-4 py-2 text-end">
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
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ __('security.sessions.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-filament-panels::page>
