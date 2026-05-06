<?php

namespace App\Filament\Admin\Pages;

use App\Actions\Security\RevokeAuthSessionAction;
use App\Actions\Security\UpdateOwnPasswordAction;
use App\Enums\AuthSessionStatus;
use App\Models\AuthSession;
use App\Models\User;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;

class SecuritySettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?int $navigationSort = 90;

    protected string $view = 'filament.admin.pages.security-settings';

    /**
     * @var array<string, mixed>
     */
    public array $passwordData = [
        'current_password' => '',
        'password' => '',
        'password_confirmation' => '',
    ];

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
    }

    public static function canAccess(): bool
    {
        return Auth::user() instanceof User;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function getHeading(): string|Htmlable|null
    {
        return __('security.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('security.navigation_label');
    }

    public static function getNavigationGroup(): string | \UnitEnum | null
    {
        return __('security.navigation_group');
    }

    /**
     * Componentes de gerência (setup/disable/regen) dos providers MFA registados no painel.
     *
     * @return array<int, mixed>
     */
    public function getTwoFactorSchemaComponents(): array
    {
        $components = [];

        foreach (Filament::getMultiFactorAuthenticationProviders() as $provider) {
            foreach ($provider->getManagementSchemaComponents() as $component) {
                $components[] = $component;
            }
        }

        return $components;
    }

    public function twoFactorForm(Schema $schema): Schema
    {
        return $schema->components($this->getTwoFactorSchemaComponents());
    }

    public function passwordForm(Schema $schema): Schema
    {
        return $schema
            ->statePath('passwordData')
            ->components([
                TextInput::make('current_password')
                    ->label(__('security.password.attributes.current_password'))
                    ->password()
                    ->revealable()
                    ->required(),
                TextInput::make('password')
                    ->label(__('security.password.attributes.password'))
                    ->password()
                    ->revealable()
                    ->required()
                    ->confirmed(),
                TextInput::make('password_confirmation')
                    ->label(__('security.password.attributes.password_confirmation'))
                    ->password()
                    ->revealable()
                    ->required(),
            ]);
    }

    public function updatePassword(): void
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            return;
        }

        app(UpdateOwnPasswordAction::class)->execute($user, $this->passwordData);

        $this->passwordData = [
            'current_password' => '',
            'password' => '',
            'password_confirmation' => '',
        ];

        Notification::make()
            ->title(__('security.password.updated'))
            ->success()
            ->send();
    }

    /**
     * @return \Illuminate\Support\Collection<int, AuthSession>
     */
    #[Computed]
    public function visibleSessions(): \Illuminate\Support\Collection
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            return collect();
        }

        $query = AuthSession::query()->with(['user', 'tenant']);

        if ($user->isSuperAdmin()) {
            // visão global para super admin
        } elseif ($user->hasRole('tenant_admin') || $user->can('manage_security')) {
            $query->where('tenant_id', $user->tenant_id);
        } else {
            $query->where('user_id', $user->getKey());
        }

        return $query
            ->where('status', AuthSessionStatus::Active->value)
            ->orderByDesc('last_activity_at')
            ->limit(50)
            ->get();
    }

    public function revokeSession(int $sessionId): void
    {
        $actor = Auth::user();
        if (! $actor instanceof User) {
            return;
        }

        try {
            app(RevokeAuthSessionAction::class)->executeById($actor, $sessionId);
        } catch (AuthorizationException $e) {
            Notification::make()
                ->title($e->getMessage())
                ->danger()
                ->send();

            return;
        } catch (\Illuminate\Validation\ValidationException $e) {
            Notification::make()
                ->title($e->getMessage())
                ->danger()
                ->send();

            return;
        }

        unset($this->visibleSessions);

        Notification::make()
            ->title(__('security.sessions.revoked'))
            ->success()
            ->send();
    }
}
