<?php

namespace App\Actions\Votes;

use App\Actions\Notifications\CreateNotificationAction;
use App\Enums\NotificationChannel;
use App\Enums\VoteStatus;
use App\Models\MeetingParticipant;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Validation\ValidationException;

final class NotifyVoteOpenedParticipantsAction
{
    public function notify(User $actor, Vote $vote): void
    {
        if ($vote->status !== VoteStatus::Open) {
            return;
        }

        $tenantId = (int) $vote->tenant_id;
        $recipientIds = MeetingParticipant::query()
            ->where('tenant_id', $tenantId)
            ->where('meeting_id', $vote->meeting_id)
            ->active()
            ->where('user_id', '!=', $actor->id)
            ->distinct()
            ->pluck('user_id');

        if ($recipientIds->isEmpty()) {
            return;
        }

        $templateData = [
            'vote_title' => $vote->title,
        ];

        $create = app(CreateNotificationAction::class);

        foreach ($recipientIds as $userId) {
            $recipient = User::query()
                ->withoutGlobalScopes()
                ->whereKey($userId)
                ->where('tenant_id', $tenantId)
                ->first();

            if (! $recipient) {
                continue;
            }

            try {
                $create->create($actor, [
                    'tenant_id' => $tenantId,
                    'user_id' => (int) $recipient->id,
                    'title' => __('votes.notifications.opened_for_participant', ['title' => $vote->title], $this->localeFor($recipient)),
                    'channel' => NotificationChannel::Database->value,
                    'template_key' => 'vote_opened',
                    'locale' => $this->localeFor($recipient),
                    'related_type' => Vote::class,
                    'related_id' => $vote->id,
                ], $templateData);
            } catch (ValidationException) {
                continue;
            }
        }
    }

    private function localeFor(User $recipient): string
    {
        $locale = $recipient->locale;

        return is_string($locale) && $locale !== '' ? $locale : 'pt_BR';
    }
}
