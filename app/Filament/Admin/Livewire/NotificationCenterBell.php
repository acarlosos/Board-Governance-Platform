<?php

namespace App\Filament\Admin\Livewire;

use App\Actions\Api\V1\Notifications\MarkAllNotificationsAsReadApiAction;
use App\Actions\Notifications\MarkNotificationAsReadAction;
use App\Enums\NotificationStatus;
use App\Filament\Admin\Resources\Meetings\MeetingResource;
use App\Filament\Admin\Resources\Minutes\MinuteResource;
use App\Filament\Admin\Resources\Tasks\TaskResource;
use App\Filament\Admin\Resources\Votes\VoteResource;
use App\Models\Meeting;
use App\Models\Minute;
use App\Models\NotificationCenter;
use App\Models\Task;
use App\Models\User;
use App\Models\Vote;
use Filament\Enums\DatabaseNotificationsPosition;
use Filament\Facades\Filament;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

final class NotificationCenterBell extends Component
{
    use WithPagination;

    #[Locked]
    public ?DatabaseNotificationsPosition $position = null;

    public function getUnreadNotificationsCount(): int
    {
        return $this->unreadNotificationsQuery()->count();
    }

    public function getNotifications(): Paginator
    {
        return $this->notificationsQuery()
            ->simplePaginate(50, pageName: 'notification-center-bell-page');
    }

    public function markAllNotificationsAsRead(): void
    {
        $user = $this->authUser();
        if (! $user) {
            return;
        }

        app(MarkAllNotificationsAsReadApiAction::class)->execute($user);
    }

    public function openNotification(int $id): void
    {
        $user = $this->authUser();
        if (! $user) {
            return;
        }

        $notification = $this->findNotificationForUser($user, $id);
        if (! $notification) {
            return;
        }

        if ($notification->status === NotificationStatus::Unread) {
            app(MarkNotificationAsReadAction::class)->mark($user, $notification);
        }

        $url = $this->resolveNotificationUrl($user, $notification);
        if ($url !== null) {
            $this->redirect($url, navigate: true);
        }
    }

    public function getPollingInterval(): ?string
    {
        return Filament::getDatabaseNotificationsPollingInterval();
    }

    public function getTrigger(): View
    {
        $isTopbar = ($this->position ?? Filament::getDatabaseNotificationsPosition()) === DatabaseNotificationsPosition::Topbar;

        return $isTopbar
            ? view('filament-panels::components.topbar.database-notifications-trigger')
            : view('filament-panels::components.sidebar.database-notifications-trigger');
    }

    public function placeholder(): string
    {
        return '<div>'.$this->getTrigger()?->with([
            'unreadNotificationsCount' => $this->getUnreadNotificationsCount(),
        ])->render().'</div>';
    }

    public function render(): View
    {
        return view('filament.admin.livewire.notification-center-bell');
    }

    private function authUser(): ?User
    {
        $user = Filament::auth()->user();

        return $user instanceof User ? $user : null;
    }

    private function notificationsQuery(): Builder
    {
        $user = $this->authUser();
        if (! $user) {
            return NotificationCenter::query()->whereRaw('0 = 1');
        }

        $query = NotificationCenter::query()
            ->withoutGlobalScopes()
            ->where('user_id', $user->id);

        if (! $user->isSuperAdmin()) {
            if ($user->tenant_id === null) {
                return $query->whereRaw('0 = 1');
            }

            $query->where('tenant_id', $user->tenant_id);
        }

        return $query->orderByDesc('created_at');
    }

    private function unreadNotificationsQuery(): Builder
    {
        return $this->notificationsQuery()
            ->where('status', NotificationStatus::Unread->value);
    }

    private function findNotificationForUser(User $user, int $id): ?NotificationCenter
    {
        return $this->notificationsQuery()
            ->whereKey($id)
            ->first();
    }

    private function resolveNotificationUrl(User $user, NotificationCenter $notification): ?string
    {
        $relatedType = $notification->related_type;
        $relatedId = $notification->related_id;

        if (! $relatedType || ! $relatedId) {
            return null;
        }

        return match ($relatedType) {
            Vote::class => $this->urlIfCanView($user, Vote::class, (int) $relatedId, VoteResource::getUrl()),
            Meeting::class => $this->urlIfCanView($user, Meeting::class, (int) $relatedId, MeetingResource::getUrl()),
            Minute::class => $this->urlIfCanView($user, Minute::class, (int) $relatedId, MinuteResource::getUrl()),
            Task::class => $this->urlIfCanView($user, Task::class, (int) $relatedId, TaskResource::getUrl()),
            default => null,
        };
    }

    private function urlIfCanView(User $user, string $modelClass, int $id, string $fallbackUrl): ?string
    {
        /** @var Model|null $record */
        $record = $modelClass::query()->withoutGlobalScopes()->find($id);

        if (! $record || ! $user->can('view', $record)) {
            return null;
        }

        return $fallbackUrl;
    }
}
