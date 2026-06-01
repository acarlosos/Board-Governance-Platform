<?php

namespace Database\Seeders;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;

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
