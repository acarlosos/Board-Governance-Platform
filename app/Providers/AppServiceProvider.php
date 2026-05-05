<?php

namespace App\Providers;

use App\Observers\TenantObserver;
use App\Observers\UserObserver;
use App\Observers\BoardObserver;
use App\Observers\BoardMemberObserver;
use App\Observers\MeetingObserver;
use App\Observers\MeetingParticipantObserver;
use App\Observers\MeetingAgendaItemObserver;
use App\Observers\DocumentObserver;
use App\Observers\DocumentVersionObserver;
use App\Observers\MinuteObserver;
use App\Observers\MinuteVersionObserver;
use App\Observers\MinuteApprovalObserver;
use App\Observers\VoteObserver;
use App\Observers\VoteOptionObserver;
use App\Observers\VoteResponseObserver;
use App\Observers\TaskObserver;
use App\Observers\TaskCommentObserver;
use App\Observers\IntegrationObserver;
use App\Services\Tenancy\TenantResolver;
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
        \App\Models\Tenant::observe(TenantObserver::class);
        \App\Models\User::observe(UserObserver::class);
        \App\Models\Board::observe(BoardObserver::class);
        \App\Models\BoardMember::observe(BoardMemberObserver::class);
        \App\Models\Meeting::observe(MeetingObserver::class);
        \App\Models\MeetingParticipant::observe(MeetingParticipantObserver::class);
        \App\Models\MeetingAgendaItem::observe(MeetingAgendaItemObserver::class);
        \App\Models\Document::observe(DocumentObserver::class);
        \App\Models\DocumentVersion::observe(DocumentVersionObserver::class);
        \App\Models\Minute::observe(MinuteObserver::class);
        \App\Models\MinuteVersion::observe(MinuteVersionObserver::class);
        \App\Models\MinuteApproval::observe(MinuteApprovalObserver::class);
        \App\Models\Vote::observe(VoteObserver::class);
        \App\Models\VoteOption::observe(VoteOptionObserver::class);
        \App\Models\VoteResponse::observe(VoteResponseObserver::class);
        \App\Models\Task::observe(TaskObserver::class);
        \App\Models\TaskComment::observe(TaskCommentObserver::class);
        \App\Models\Integration::observe(IntegrationObserver::class);
    }
}
