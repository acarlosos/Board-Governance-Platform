<?php

namespace Tests\Feature\Api\V1;

use App\Enums\NotificationStatus;
use App\Models\NotificationCenter;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class NotificationsWriteApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_post_notifications_id_read_marca_como_lida_e_idempotente(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $user->assignRole('board_member');

        $n = NotificationCenter::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'status' => NotificationStatus::Unread,
            'read_at' => null,
        ]);

        $token = $user->createToken('device', ['notifications:write']);

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->postJson('/api/v1/notifications/'.$n->id.'/read')
            ->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->postJson('/api/v1/notifications/'.$n->id.'/read')
            ->assertOk();

        $n->refresh();
        $this->assertSame(NotificationStatus::Read, $n->status);
        $this->assertNotNull($n->read_at);
    }

    public function test_usuario_comum_nao_marca_alheia_retorna_404_generico(): void
    {
        $tenant = Tenant::factory()->create();
        $u1 = User::factory()->create(['tenant_id' => $tenant->id]);
        $u1->assignRole('board_member');
        $u2 = User::factory()->create(['tenant_id' => $tenant->id]);
        $u2->assignRole('board_member');

        $other = NotificationCenter::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $u2->id,
            'status' => NotificationStatus::Unread,
            'read_at' => null,
        ]);

        $token = $u1->createToken('device', ['notifications:write']);

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->postJson('/api/v1/notifications/'.$other->id.'/read')
            ->assertStatus(404);
    }

    public function test_read_all_marca_apenas_proprias_unread_idempotente_e_rate_limit(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $user->assignRole('board_member');

        NotificationCenter::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $user->id, 'status' => NotificationStatus::Unread, 'read_at' => null]);
        NotificationCenter::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $user->id, 'status' => NotificationStatus::Unread, 'read_at' => null]);

        $otherUser = User::factory()->create(['tenant_id' => $tenant->id]);
        $otherUnread = NotificationCenter::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $otherUser->id, 'status' => NotificationStatus::Unread, 'read_at' => null]);

        $token = $user->createToken('device', ['notifications:write']);

        $res = $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->postJson('/api/v1/notifications/read-all')
            ->assertOk();

        $this->assertSame(2, (int) $res->json('data.affected'));

        $otherUnread->refresh();
        $this->assertSame(NotificationStatus::Unread, $otherUnread->status);

        $res2 = $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->postJson('/api/v1/notifications/read-all')
            ->assertOk();

        $this->assertSame(0, (int) $res2->json('data.affected'));

        for ($i = 0; $i < 3; $i++) {
            $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
                ->postJson('/api/v1/notifications/read-all')
                ->assertOk();
        }

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->postJson('/api/v1/notifications/read-all')
            ->assertStatus(429);
    }

    public function test_super_admin_conforme_policy(): void
    {
        $tenant = Tenant::factory()->create();
        $other = User::factory()->create(['tenant_id' => $tenant->id]);
        $other->assignRole('board_member');

        $n = NotificationCenter::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $other->id,
            'status' => NotificationStatus::Unread,
            'read_at' => null,
        ]);

        $super = User::factory()->create(['tenant_id' => null, 'is_super_admin' => true]);
        $super->assignRole('super_admin');

        $token = $super->createToken('device', ['notifications:write']);

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->postJson('/api/v1/notifications/'.$n->id.'/read')
            ->assertOk();
    }
}

