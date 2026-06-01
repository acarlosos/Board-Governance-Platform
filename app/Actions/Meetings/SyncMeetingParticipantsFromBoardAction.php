<?php

namespace App\Actions\Meetings;

use App\Enums\BoardMemberStatus;
use App\Enums\MeetingParticipantStatus;
use App\Models\BoardMember;
use App\Models\Meeting;
use App\Models\User;
use App\Support\Meetings\BoardMemberToMeetingParticipantRole;

final class SyncMeetingParticipantsFromBoardAction
{
    public function __construct(
        private readonly PersistMeetingParticipantAction $persistMeetingParticipant,
    ) {}

    public function sync(User $actor, Meeting $meeting): Meeting
    {
        $members = BoardMember::query()
            ->where('tenant_id', $meeting->tenant_id)
            ->where('board_id', $meeting->board_id)
            ->where('status', BoardMemberStatus::Active)
            ->get();

        foreach ($members as $member) {
            $this->persistMeetingParticipant->create($actor, $meeting, [
                'user_id' => $member->user_id,
                'role' => BoardMemberToMeetingParticipantRole::map($member->role)->value,
                'status' => MeetingParticipantStatus::Invited->value,
            ]);
        }

        return $meeting->fresh();
    }
}
