<?php

namespace Tests\Feature\Filament;

use App\Enums\NotificationStatus;
use App\Filament\Admin\Livewire\NotificationCenterBell;
use App\Filament\Admin\Resources\Votes\VoteResource;
use App\Models\Meeting;
use App\Models\MeetingParticipant;
use App\Models\NotificationCenter;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vote;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationCenterBellTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        if (method_exists($this, 'withoutVite')) {
            $this->withoutVite();
        }
    }

    public function test_board_member_ve_contagem_e_lista_de_notificacoes_proprias(): void
    {
        $tenant = Tenant::factory()->create();
        $member = User::factory()->create(['tenant_id' => $tenant->id]);
        $member->assignRole('board_member');

        $other = User::factory()->create(['tenant_id' => $tenant->id]);

        NotificationCenter::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $member->id,
            'title' => 'Votação aberta: Orçamento',
            'status' => NotificationStatus::Unread,
            'read_at' => null,
        ]);

        NotificationCenter::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $other->id,
            'title' => 'Outro utilizador',
            'status' => NotificationStatus::Unread,
            'read_at' => null,
        ]);

        Livewire::actingAs($member)
            ->test(NotificationCenterBell::class)
            ->assertSee('Votação aberta: Orçamento')
            ->assertDontSee('Outro utilizador');

        $component = Livewire::actingAs($member)->test(NotificationCenterBell::class);
        $this->assertSame(1, $component->instance()->getUnreadNotificationsCount());
    }

    public function test_marcar_todas_como_lidas_zera_contagem(): void
    {
        $tenant = Tenant::factory()->create();
        $member = User::factory()->create(['tenant_id' => $tenant->id]);
        $member->assignRole('board_member');

        NotificationCenter::factory()->count(2)->create([
            'tenant_id' => $tenant->id,
            'user_id' => $member->id,
            'status' => NotificationStatus::Unread,
            'read_at' => null,
        ]);

        $component = Livewire::actingAs($member)->test(NotificationCenterBell::class);
        $component->call('markAllNotificationsAsRead');
        $this->assertSame(0, $component->instance()->getUnreadNotificationsCount());
    }

    public function test_abrir_notificacao_de_voto_marca_como_lida_e_redireciona(): void
    {
        $tenant = Tenant::factory()->create();
        $member = User::factory()->create(['tenant_id' => $tenant->id]);
        $member->assignRole('board_member');

        $meeting = Meeting::factory()->create(['tenant_id' => $tenant->id]);
        MeetingParticipant::factory()->create([
            'tenant_id' => $tenant->id,
            'meeting_id' => $meeting->id,
            'user_id' => $member->id,
        ]);
        $vote = Vote::factory()->create([
            'tenant_id' => $tenant->id,
            'meeting_id' => $meeting->id,
        ]);

        $notification = NotificationCenter::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $member->id,
            'title' => 'Voto',
            'status' => NotificationStatus::Unread,
            'read_at' => null,
            'related_type' => Vote::class,
            'related_id' => $vote->id,
        ]);

        Livewire::actingAs($member)
            ->test(NotificationCenterBell::class)
            ->call('openNotification', $notification->id)
            ->assertRedirect(VoteResource::getUrl());

        $notification->refresh();
        $this->assertSame(NotificationStatus::Read, $notification->status);
        $this->assertNotNull($notification->read_at);
    }
}
