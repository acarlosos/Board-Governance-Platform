<?php

namespace App\Actions\Filament;

use App\Models\User;
use App\Services\Security\PasswordPolicyService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

final class PersistPanelUserAction
{
    public const ROLE_SUPER_ADMIN = 'super_admin';

    public const ROLES_ASSIGNABLE_BY_TENANT_ADMIN = [
        'tenant_admin',
        'board_member',
        'executive',
        'guest',
    ];

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $actor, array $data): User
    {
        $roles = $this->normalizeRolesInput(Arr::pull($data, 'roles', []));
        $roles = $this->stripSuperAdminFromRoles($roles);
        $data = $this->applyTenantAndSuperAdminGuards($actor, $data, null);
        $this->assertRolesAllowedForActor($actor, $roles);

        $allowedRoleNames = $this->assignableRoleNamesForValidation($actor);

        $validator = Validator::make(
            array_merge($data, ['roles' => $roles]),
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
                'password' => array_merge(['required'], app(PasswordPolicyService::class)->rules()),
                'tenant_id' => ['nullable', 'integer', 'exists:tenants,id'],
                'locale' => ['required', 'string', 'max:10'],
                'status' => ['required', 'string'],
                'is_super_admin' => ['boolean'],
                'roles' => ['array'],
                'roles.*' => ['string', Rule::in($allowedRoleNames)],
            ],
            [],
            [
                'name' => __('users.validation.attributes.name'),
                'email' => __('users.validation.attributes.email'),
                'password' => __('users.validation.attributes.password'),
                'roles' => __('users.validation.attributes.roles'),
            ],
        );

        $validator->after(function (\Illuminate\Validation\Validator $v): void {
            $roles = $v->safe()->roles ?? [];
            $isSuper = (bool) ($v->safe()->is_super_admin ?? false);
            if (count($roles) < 1 && ! $isSuper) {
                $v->errors()->add('roles', __('users.validation.roles_required_unless_super'));
            }
        });

        $validated = $validator->validate();

        $user = new User;
        $user->fill(Arr::only($validated, ['name', 'email', 'locale', 'status', 'tenant_id', 'is_super_admin']));
        $user->password = $validated['password'];
        $user->save();

        $this->syncRolesFromCheckboxAndSuperFlag(
            $actor,
            $user,
            $validated['roles'],
            (bool) ($validated['is_super_admin'] ?? false),
        );

        return $user->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $actor, User $user, array $data): User
    {
        $rolesProvided = array_key_exists('roles', $data);
        $rolesRaw = Arr::pull($data, 'roles');
        $roles = $rolesProvided ? $this->stripSuperAdminFromRoles($this->normalizeRolesInput($rolesRaw)) : null;
        $data = $this->applyTenantAndSuperAdminGuards($actor, $data, $user);

        if (array_key_exists('password', $data) && $data['password'] === '') {
            unset($data['password']);
        }

        if ($roles !== null) {
            $this->assertRolesAllowedForActor($actor, $roles);
        }

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => array_merge(['sometimes'], app(PasswordPolicyService::class)->rules()),
            'tenant_id' => ['nullable', 'integer', 'exists:tenants,id'],
            'locale' => ['required', 'string', 'max:10'],
            'status' => ['required', 'string'],
            'is_super_admin' => ['boolean'],
        ];

        if ($roles !== null) {
            $allowedRoleNames = $this->assignableRoleNamesForValidation($actor);
            $rules['roles'] = ['array'];
            $rules['roles.*'] = ['string', Rule::in($allowedRoleNames)];
        }

        $validator = Validator::make(
            $roles !== null ? array_merge($data, ['roles' => $roles]) : $data,
            $rules,
            [],
            [
                'name' => __('users.validation.attributes.name'),
                'email' => __('users.validation.attributes.email'),
                'password' => __('users.validation.attributes.password'),
                'roles' => __('users.validation.attributes.roles'),
            ],
        );

        if ($roles !== null) {
            $validator->after(function (\Illuminate\Validation\Validator $v): void {
                $safe = $v->safe();
                $r = $safe->roles ?? [];
                $isSuper = (bool) ($safe->is_super_admin ?? false);
                if (count($r) < 1 && ! $isSuper) {
                    $v->errors()->add('roles', __('users.validation.roles_required_unless_super'));
                }
            });
        }

        $validated = $validator->validate();

        $fill = Arr::only($validated, ['name', 'email', 'locale', 'status', 'tenant_id', 'is_super_admin']);

        if (array_key_exists('password', $validated) && filled($validated['password'])) {
            $fill['password'] = $validated['password'];
        }

        $user->fill($fill);
        $user->save();

        if ($roles !== null) {
            $this->syncRolesFromCheckboxAndSuperFlag(
                $actor,
                $user,
                $validated['roles'],
                (bool) ($validated['is_super_admin'] ?? $user->is_super_admin),
            );
        } elseif ($actor->isSuperAdmin() && array_key_exists('is_super_admin', $validated)) {
            $baseline = $this->stripSuperAdminFromRoles($user->getRoleNames()->all());
            $this->syncRolesFromCheckboxAndSuperFlag(
                $actor,
                $user,
                $baseline,
                (bool) $validated['is_super_admin'],
            );
        }

        return $user->fresh();
    }

    /**
     * @param  list<string>  $checkboxRoles
     */
    private function syncRolesFromCheckboxAndSuperFlag(User $actor, User $user, array $checkboxRoles, bool $isPlatformSuperFlag): void
    {
        $checkboxRoles = $this->stripSuperAdminFromRoles($this->filterRolesToAllowed($actor, $checkboxRoles));
        $names = array_values(array_unique($checkboxRoles));

        if ($actor->isSuperAdmin() && $isPlatformSuperFlag) {
            $names[] = self::ROLE_SUPER_ADMIN;
        }

        $names = array_values(array_unique($names));

        $user->syncRoles($names);
    }

    /**
     * @return list<string>
     */
    private function assignableRoleNamesForValidation(User $actor): array
    {
        if ($actor->isSuperAdmin()) {
            return Role::query()
                ->where('guard_name', 'web')
                ->where('name', '!=', self::ROLE_SUPER_ADMIN)
                ->orderBy('name')
                ->pluck('name')
                ->all();
        }

        return self::ROLES_ASSIGNABLE_BY_TENANT_ADMIN;
    }

    /**
     * @param  list<string>  $roles
     * @return list<string>
     */
    private function stripSuperAdminFromRoles(array $roles): array
    {
        return array_values(array_filter($roles, fn (string $r): bool => $r !== self::ROLE_SUPER_ADMIN));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function applyTenantAndSuperAdminGuards(User $actor, array $data, ?User $existing): array
    {
        if (! $actor->isSuperAdmin()) {
            $data['tenant_id'] = $actor->tenant_id;
            $data['is_super_admin'] = false;
        }

        if (! $actor->isSuperAdmin() && isset($data['is_super_admin'])) {
            $data['is_super_admin'] = false;
        }

        if ($actor->isSuperAdmin()) {
            return $data;
        }

        if ($existing !== null && (int) $existing->tenant_id !== (int) $actor->tenant_id) {
            throw ValidationException::withMessages([
                'tenant_id' => __('users.validation.tenant_mismatch'),
            ]);
        }

        return $data;
    }

    /**
     * @param  list<string>  $roles
     */
    private function assertRolesAllowedForActor(User $actor, array $roles): void
    {
        $filtered = $this->filterRolesToAllowed($actor, $roles);

        if (count($filtered) !== count($roles)) {
            throw ValidationException::withMessages([
                'roles' => __('users.validation.roles_not_allowed'),
            ]);
        }
    }

    /**
     * @param  list<string>  $roles
     * @return list<string>
     */
    private function filterRolesToAllowed(User $actor, array $roles): array
    {
        $roles = array_values(array_unique($this->stripSuperAdminFromRoles($roles)));

        if ($actor->isSuperAdmin()) {
            return $roles;
        }

        return array_values(array_intersect($roles, self::ROLES_ASSIGNABLE_BY_TENANT_ADMIN));
    }

    /**
     * @return list<string>
     */
    private function normalizeRolesInput(mixed $roles): array
    {
        if ($roles === null) {
            return [];
        }

        if (! is_array($roles)) {
            return [];
        }

        return array_values(array_filter($roles, static fn ($r): bool => is_string($r) && $r !== ''));
    }
}
