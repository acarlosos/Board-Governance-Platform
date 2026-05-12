<?php

namespace App\Providers;

use App\Models\Board;
use App\Models\BoardMember;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Integration;
use App\Models\Meeting;
use App\Models\MeetingAgendaItem;
use App\Models\MeetingParticipant;
use App\Models\Minute;
use App\Models\MinuteApproval;
use App\Models\MinuteVersion;
use App\Models\NotificationCenter;
use App\Models\NotificationTemplate;
use App\Models\SignatureRequest;
use App\Models\SignatureRequestSigner;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vote;
use App\Models\VoteOption;
use App\Models\VoteResponse;
use App\Observers\BoardMemberObserver;
use App\Observers\BoardObserver;
use App\Observers\DocumentObserver;
use App\Observers\DocumentVersionObserver;
use App\Observers\IntegrationObserver;
use App\Observers\MeetingAgendaItemObserver;
use App\Observers\MeetingObserver;
use App\Observers\MeetingParticipantObserver;
use App\Observers\MinuteApprovalObserver;
use App\Observers\MinuteObserver;
use App\Observers\MinuteVersionObserver;
use App\Observers\NotificationCenterObserver;
use App\Observers\NotificationTemplateObserver;
use App\Observers\SignatureRequestObserver;
use App\Observers\SignatureRequestSignerObserver;
use App\Observers\TaskCommentObserver;
use App\Observers\TaskObserver;
use App\Observers\TenantObserver;
use App\Observers\UserObserver;
use App\Observers\VoteObserver;
use App\Observers\VoteOptionObserver;
use App\Observers\VoteResponseObserver;
use App\Services\Tenancy\TenantResolver;
use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TenantResolver::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        PreventRequestsDuringMaintenance::except(['/health']);

        Tenant::observe(TenantObserver::class);
        User::observe(UserObserver::class);
        Board::observe(BoardObserver::class);
        BoardMember::observe(BoardMemberObserver::class);
        Meeting::observe(MeetingObserver::class);
        MeetingParticipant::observe(MeetingParticipantObserver::class);
        MeetingAgendaItem::observe(MeetingAgendaItemObserver::class);
        Document::observe(DocumentObserver::class);
        DocumentVersion::observe(DocumentVersionObserver::class);
        Minute::observe(MinuteObserver::class);
        MinuteVersion::observe(MinuteVersionObserver::class);
        MinuteApproval::observe(MinuteApprovalObserver::class);
        Vote::observe(VoteObserver::class);
        VoteOption::observe(VoteOptionObserver::class);
        VoteResponse::observe(VoteResponseObserver::class);
        Task::observe(TaskObserver::class);
        TaskComment::observe(TaskCommentObserver::class);
        Integration::observe(IntegrationObserver::class);
        SignatureRequest::observe(SignatureRequestObserver::class);
        SignatureRequestSigner::observe(SignatureRequestSignerObserver::class);
        NotificationTemplate::observe(NotificationTemplateObserver::class);
        NotificationCenter::observe(NotificationCenterObserver::class);
    }
}
