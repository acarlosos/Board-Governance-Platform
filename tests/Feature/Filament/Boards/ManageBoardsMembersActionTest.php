<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Boards;

use App\Enums\BoardMemberStatus;
use App\Filament\Admin\Resources\Boards\Pages\ManageBoards;
use App\Models\Board;
use App\Models\BoardMember;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

final class ManageBoardsMembersActionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        Filament::setCurrentPanel('admin');

        if (method_exists($this, 'withoutVite')) {
            $this->withoutVite();
        }
    }

    public function test_tenant_admin_abre_acao_membros_na_listagem(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');

        $board = Board::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Conselho de Teste',
        ]);

        $this->actingAs($admin);

        $this->assertTrue(Gate::forUser($admin)->allows('update', $board));
        $this->assertTrue(Gate::forUser($admin)->allows('create', BoardMember::class));

        Livewire::test(ManageBoards::class)
            ->assertTableActionVisible('members', $board)
            ->callTableAction('members', $board)
            ->assertSuccessful();
    }

    public function test_board_member_sem_manage_boards_nao_ve_acao_membros(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $user->assignRole('board_member');

        $board = Board::factory()->create(['tenant_id' => $tenant->id]);

        BoardMember::factory()->create([
            'tenant_id' => $tenant->id,
            'board_id' => $board->id,
            'user_id' => $user->id,
            'status' => BoardMemberStatus::Active,
        ]);

        $this->actingAs($user);

        Livewire::test(ManageBoards::class)
            ->assertTableActionHidden('members', $board);
    }
}
