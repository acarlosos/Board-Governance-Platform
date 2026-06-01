<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Minutes;

use App\Filament\Admin\Resources\Minutes\Pages\ManageMinutes;
use App\Models\Meeting;
use App\Models\Minute;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Tests\TestCase;

final class ManageMinutesCreateActionTest extends TestCase
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

    public function test_criar_ata_com_conteudo_vazio_mostra_erro_no_campo_content(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');

        $meeting = Meeting::factory()->create([
            'tenant_id' => $tenant->id,
            'title' => 'Reunião de Teste',
        ]);

        $this->actingAs($admin);

        Livewire::test(ManageMinutes::class)
            ->callAction('create', data: [
                'title' => 'Ata da reunião de teste',
                'meeting_id' => $meeting->id,
                'content' => '<p></p>',
            ])
            ->assertHasActionErrors(['content']);

        $this->assertDatabaseMissing('minutes', [
            'meeting_id' => $meeting->id,
            'title' => 'Ata da reunião de teste',
        ]);
    }

    public function test_criar_ata_para_reuniao_de_teste_com_sucesso(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('tenant_admin');

        $meeting = Meeting::factory()->create([
            'tenant_id' => $tenant->id,
            'title' => 'Reunião de Teste',
        ]);

        $this->actingAs($admin);

        Livewire::test(ManageMinutes::class)
            ->callAction('create', data: [
                'title' => 'Ata da reunião de teste',
                'meeting_id' => $meeting->id,
                'content' => '<p>Deliberações registadas.</p>',
            ])
            ->assertHasNoActionErrors();

        $minute = Minute::query()
            ->where('meeting_id', $meeting->id)
            ->where('title', 'Ata da reunião de teste')
            ->first();

        $this->assertNotNull($minute);
        $this->assertSame($tenant->id, $minute->tenant_id);
        $this->assertNotNull($minute->current_version_id);
    }
}
