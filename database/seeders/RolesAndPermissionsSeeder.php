<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public const GUARD = 'web';

    /** @var list<string> */
    public const PERMISSIONS = [
        'manage_tenants',
        'manage_users',
        'manage_boards',
        'manage_meetings',
        'manage_documents',
        'manage_votes',
        'manage_minutes',
        'view_reports',
        'manage_settings',
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $name) {
            Permission::query()->firstOrCreate(
                ['name' => $name, 'guard_name' => self::GUARD],
            );
        }

        $all = Permission::query()->where('guard_name', self::GUARD)->get();

        $superAdmin = Role::query()->firstOrCreate(
            ['name' => 'super_admin', 'guard_name' => self::GUARD],
        );
        $superAdmin->syncPermissions($all);

        $tenantAdmin = Role::query()->firstOrCreate(
            ['name' => 'tenant_admin', 'guard_name' => self::GUARD],
        );
        $tenantAdmin->syncPermissions(
            $all->where('name', '!=', 'manage_tenants')->values(),
        );

        $boardMember = Role::query()->firstOrCreate(
            ['name' => 'board_member', 'guard_name' => self::GUARD],
        );
        $boardMember->syncPermissions(
            Permission::query()->whereIn('name', ['view_reports', 'manage_meetings'])->where('guard_name', self::GUARD)->get(),
        );

        $executive = Role::query()->firstOrCreate(
            ['name' => 'executive', 'guard_name' => self::GUARD],
        );
        $executive->syncPermissions(
            Permission::query()->whereIn('name', [
                'manage_meetings',
                'manage_documents',
                'manage_votes',
                'view_reports',
            ])->where('guard_name', self::GUARD)->get(),
        );

        $guest = Role::query()->firstOrCreate(
            ['name' => 'guest', 'guard_name' => self::GUARD],
        );
        $guest->syncPermissions(
            Permission::query()->where('name', 'view_reports')->where('guard_name', self::GUARD)->get(),
        );
    }
}
