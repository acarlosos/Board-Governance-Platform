<?php

namespace Database\Seeders;

use App\Enums\TenantStatus;
use App\Enums\UserStatus;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class InitialTenantSeeder extends Seeder
{
    /**
     * Tenant e administrador inicial (credenciais via .env — ver docs/features/multitenancy.md).
     */
    public function run(): void
    {
        $tenant = Tenant::query()->firstOrCreate(
            ['slug' => 'principal'],
            [
                'name' => 'Organização Principal',
                'document' => null,
                'status' => TenantStatus::Active,
            ],
        );

        $email = env('SEED_ADMIN_EMAIL', 'admin@localhost');
        $password = env('SEED_ADMIN_PASSWORD', 'AlterarEstaSenha1!');
        $superEmail = env('SEED_SUPER_ADMIN_EMAIL', 'root@localhost');

        if (strcasecmp($email, $superEmail) === 0) {
            throw new RuntimeException(
                'SEED_ADMIN_EMAIL must differ from SEED_SUPER_ADMIN_EMAIL (same email would be overwritten by SuperAdminSeeder).',
            );
        }

        $user = User::query()->withoutGlobalScopes()->updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Administrador',
                'password' => $password,
                'locale' => 'pt_BR',
                'tenant_id' => $tenant->id,
                'status' => UserStatus::Active,
                'is_super_admin' => false,
            ],
        );

        $user->syncRoles(['tenant_admin']);
    }
}
