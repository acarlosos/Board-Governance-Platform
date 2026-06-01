<?php

namespace Database\Seeders;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class SuperAdminSeeder extends Seeder
{
    /**
     * Super administrador global da plataforma (sem tenant; role `super_admin`).
     *
     * Credenciais via .env — ver docs/features/auth-permissions.md.
     */
    public function run(): void
    {
        $email = env('SEED_SUPER_ADMIN_EMAIL', 'root@localhost');
        $password = env('SEED_SUPER_ADMIN_PASSWORD', 'AlterarEstaSenhaRoot1!');
        $tenantAdminEmail = env('SEED_ADMIN_EMAIL', 'admin@localhost');

        if (strcasecmp($email, $tenantAdminEmail) === 0) {
            throw new RuntimeException(
                'SEED_SUPER_ADMIN_EMAIL must differ from SEED_ADMIN_EMAIL (same email would overwrite the tenant administrator).',
            );
        }

        $user = User::query()->withoutGlobalScopes()->updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Super Administrador',
                'password' => $password,
                'locale' => 'pt_BR',
                'tenant_id' => null,
                'status' => UserStatus::Active,
                'is_super_admin' => true,
            ],
        );

        $user->syncRoles(['super_admin']);
    }
}
