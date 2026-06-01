@php
    use Filament\Support\Enums\Alignment;
    use Filament\Support\View\Components\BadgeComponent;
    use Illuminate\View\ComponentAttributeBag;

    $notifications = $this->getNotifications();
    $unreadNotificationsCount = $this->getUnreadNotificationsCount();
    $hasNotifications = $notifications->count() > 0;
    $isPaginated = $notifications->hasPages();
    $pollingInterval = $this->getPollingInterval();
@endphp

<div class="fi-no-database">
    <x-filament::modal
        :alignment="$hasNotifications ? null : Alignment::Center"
        close-button
        :description="$hasNotifications ? null : __('notifications.bell.empty.description')"
        :heading="$hasNotifications ? null : __('notifications.bell.empty.heading')"
        :icon="$hasNotifications ? null : \Filament\Support\Icons\Heroicon::OutlinedBellSlash"
        :icon-color="$hasNotifications ? null : 'gray'"
        id="notification-center-bell"
        slide-over
        :sticky-header="$hasNotifications"
        teleport="body"
        width="md"
        class="fi-no-database"
        :attributes="
            new \Illuminate\View\ComponentAttributeBag([
                'wire:poll.' . $pollingInterval => $pollingInterval ? '' : false,
            ])
        "
    >
        @if ($trigger = $this->getTrigger())
            <x-slot name="trigger">
                {{ $trigger->with(['unreadNotificationsCount' => $unreadNotificationsCount]) }}
            </x-slot>
        @endif

        @if ($hasNotifications)
            <x-slot name="header">
                <div>
                    <h2 class="fi-modal-heading">
                        {{ __('notifications.bell.heading') }}

                        @if ($unreadNotificationsCount)
                            <span
                                {{
                                    (new ComponentAttributeBag)->color(BadgeComponent::class, 'primary')->class([
                                        'fi-badge fi-size-xs',
                                    ])
                                }}
                            >
                                {{ $unreadNotificationsCount }}
                            </span>
                        @endif
                    </h2>

                    @if ($unreadNotificationsCount)
                        <div class="fi-ac">
                            <x-filament::link
                                tag="button"
                                type="button"
                                wire:click="markAllNotificationsAsRead"
                            >
                                {{ __('notifications.bell.mark_all_read') }}
                            </x-filament::link>
                        </div>
                    @endif
                </div>
            </x-slot>

            @foreach ($notifications as $notification)
                <button
                    type="button"
                    wire:click="openNotification({{ $notification->id }})"
                    @class([
                        'fi-no-notification-unread-ctn w-full text-start' => $notification->status === \App\Enums\NotificationStatus::Unread,
                        'fi-no-notification-read-ctn w-full text-start' => $notification->status !== \App\Enums\NotificationStatus::Unread,
                    ])
                >
                    <div class="px-4 py-3">
                        <p class="text-sm font-medium text-gray-950 dark:text-white">
                            {{ $notification->title }}
                        </p>
                        @if ($notification->body)
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                {{ $notification->body }}
                            </p>
                        @endif
                        <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                            {{ $notification->created_at?->diffForHumans() }}
                        </p>
                    </div>
                </button>
            @endforeach

            @if ($isPaginated)
                <x-slot name="footer">
                    <x-filament::pagination :paginator="$notifications" />
                </x-slot>
            @endif
        @endif
    </x-filament::modal>
</div>
