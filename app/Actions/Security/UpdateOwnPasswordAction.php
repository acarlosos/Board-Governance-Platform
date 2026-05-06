<?php

namespace App\Actions\Security;

use App\Models\User;
use App\Services\Security\PasswordPolicyService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class UpdateOwnPasswordAction
{
    public const RATE_LIMIT_MAX_ATTEMPTS = 5;

    public const RATE_LIMIT_DECAY_SECONDS = 60;

    public function __construct(
        private readonly PasswordPolicyService $policy,
    ) {}

    /**
     * @param  array{current_password?: string|null, password?: string|null, password_confirmation?: string|null}  $data
     */
    public function execute(User $user, array $data): void
    {
        $key = 'password-update:' . $user->getKey();

        if (RateLimiter::tooManyAttempts($key, self::RATE_LIMIT_MAX_ATTEMPTS)) {
            throw ValidationException::withMessages([
                'password' => __('security.password.rate_limited'),
            ]);
        }

        RateLimiter::hit($key, self::RATE_LIMIT_DECAY_SECONDS);

        $rules = [
            'current_password' => ['required', 'string'],
            'password' => array_merge(['required', 'confirmed', 'different:current_password'], $this->policy->rules()),
        ];

        $validator = Validator::make($data, $rules, [], [
            'current_password' => __('security.password.attributes.current_password'),
            'password' => __('security.password.attributes.password'),
        ]);

        $validator->after(function (\Illuminate\Validation\Validator $v) use ($data, $user): void {
            $current = (string) ($data['current_password'] ?? '');
            if ($current !== '' && ! Hash::check($current, $user->password)) {
                $v->errors()->add('current_password', __('security.password.invalid_current'));
            }
        });

        $validated = $validator->validate();

        $user->password = $validated['password'];
        $user->save();

        RateLimiter::clear($key);
    }
}
