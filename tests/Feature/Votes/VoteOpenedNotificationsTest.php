<?php

namespace Tests\Feature\Votes;

use App\Actions\Votes\OpenVoteAction;
use App\Actions\Votes\PersistVoteOptionAction;
use App\Enums\MeetingParticipantStatus;
use App\Enums\VoteStatus;
use App\Models\Meeting;
use App\Models\MeetingParticipant;
use App\Models\NotificationCenter;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vote;
use Database\Seeders\NotificationTemplatesSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoteOpenedNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(NotificationTemplatesSeeder::class);
    }

    public function test_abrir_votacao_notifica_participantes_activos_exceto_quem_abriu(): void
    {
        $tenant = Tenant::factory()->create();
        $opener = User::factory()->create(['tenant_id' => $tenant->id, 'locale' => 'pt_BR']);
        $opener->assignRole('tenant_admin');

        $participantA = User::factory()->create(['tenant_id' => $tenant->id]);
        $participantB = User::factory()->create(['tenant_id' => $tenant->id]);

        $meeting = Meeting::factory()->create(['tenant_id' => $tenant->id]);

        foreach ([$opener, $participantA, $participantB] as $user) {
            MeetingParticipant::factory()->create([
                'tenant_id' => $tenant->id,
                'meeting_id' => $meeting->id,
                'user_id' => $user->id,
                'status' => MeetingParticipantStatus::Confirmed,
            ]);
        }

        $vote = Vote::factory()->create([
            'tenant_id' => $tenant->id,
            'meeting_id' => $meeting->id,
            'title' => 'Aprovar orçamento',
            'status' => VoteStatus::Draft,
        ]);

        $this->actingAs($opener);
        app(PersistVoteOptionAction::class)->create($opener, $vote, ['title' => 'Sim']);
        app(PersistVoteOptionAction::class)->create($opener, $vote, ['title' => 'Não']);

        app(OpenVoteAction::class)->open($opener, $vote);

        $notifications = NotificationCenter::query()
            ->where('tenant_id', $tenant->id)
            ->where('related_type', Vote::class)
            ->where('related_id', $vote->id)
            ->get();

        $this->assertCount(2, $notifications);
        $this->assertEqualsCanonicalizing(
            [$participantA->id, $participantB->id],
            $notifications->pluck('user_id')->all()
        );
        $this->assertStringContainsString('Aprovar orçamento', (string) $notifications->first()->title);
    }

    public function test_participante_declined_nao_recebe_notificacao(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');

        $declined = User::factory()->create(['tenant_id' => $tenant->id]);
        $confirmed = User::factory()->create(['tenant_id' => $tenant->id]);

        $meeting = Meeting::factory()->create(['tenant_id' => $tenant->id]);

        MeetingParticipant::factory()->create([
            'tenant_id' => $tenant->id,
            'meeting_id' => $meeting->id,
            'user_id' => $admin->id,
            'status' => MeetingParticipantStatus::Confirmed,
        ]);
        MeetingParticipant::factory()->create([
            'tenant_id' => $tenant->id,
            'meeting_id' => $meeting->id,
            'user_id' => $declined->id,
            'status' => MeetingParticipantStatus::Declined,
        ]);
        MeetingParticipant::factory()->create([
            'tenant_id' => $tenant->id,
            'meeting_id' => $meeting->id,
            'user_id' => $confirmed->id,
            'status' => MeetingParticipantStatus::Confirmed,
        ]);

        $vote = Vote::factory()->create([
            'tenant_id' => $tenant->id,
            'meeting_id' => $meeting->id,
            'status' => VoteStatus::Draft,
        ]);

        $this->actingAs($admin);
        app(PersistVoteOptionAction::class)->create($admin, $vote, ['title' => 'A']);
        app(PersistVoteOptionAction::class)->create($admin, $vote, ['title' => 'B']);

        app(OpenVoteAction::class)->open($admin, $vote);

        $this->assertSame(
            1,
            NotificationCenter::query()
                ->where('related_id', $vote->id)
                ->where('user_id', $confirmed->id)
                ->count()
        );
        $this->assertSame(
            0,
            NotificationCenter::query()
                ->where('related_id', $vote->id)
                ->where('user_id', $declined->id)
                ->count()
        );
    }

    public function test_notificacoes_ficam_no_tenant_da_votacao(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $adminA = User::factory()->create(['tenant_id' => $tenantA->id]);
        $adminA->assignRole('tenant_admin');

        $participantA = User::factory()->create(['tenant_id' => $tenantA->id]);
        $userB = User::factory()->create(['tenant_id' => $tenantB->id]);

        $meeting = Meeting::factory()->create(['tenant_id' => $tenantA->id]);

        MeetingParticipant::factory()->create([
            'tenant_id' => $tenantA->id,
            'meeting_id' => $meeting->id,
            'user_id' => $adminA->id,
            'status' => MeetingParticipantStatus::Confirmed,
        ]);
        MeetingParticipant::factory()->create([
            'tenant_id' => $tenantA->id,
            'meeting_id' => $meeting->id,
            'user_id' => $participantA->id,
            'status' => MeetingParticipantStatus::Confirmed,
        ]);

        $vote = Vote::factory()->create([
            'tenant_id' => $tenantA->id,
            'meeting_id' => $meeting->id,
            'status' => VoteStatus::Draft,
        ]);

        $this->actingAs($adminA);
        app(PersistVoteOptionAction::class)->create($adminA, $vote, ['title' => 'A']);
        app(PersistVoteOptionAction::class)->create($adminA, $vote, ['title' => 'B']);
        app(OpenVoteAction::class)->open($adminA, $vote);

        $this->assertSame(
            1,
            NotificationCenter::query()->where('tenant_id', $tenantA->id)->where('related_id', $vote->id)->count()
        );
        $this->assertSame(
            0,
            NotificationCenter::query()->where('tenant_id', $tenantB->id)->where('user_id', $userB->id)->count()
        );
    }
}
