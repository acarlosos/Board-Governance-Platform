<?php

namespace Database\Seeders;

use App\Enums\TenantStatus;
use App\Enums\UserStatus;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

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

        User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Administrador',
                'password' => Hash::make($password),
                'locale' => 'pt_BR',
                'tenant_id' => $tenant->id,
                'status' => UserStatus::Active,
                'is_super_admin' => false,
            ],
        );
    }
}
