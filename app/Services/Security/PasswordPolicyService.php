<?php

namespace App\Services\Security;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Política de senha corporativa: mínimo 8 caracteres,
 * com letras minúsculas, maiúsculas, números e símbolos.
 */
final class PasswordPolicyService
{
    public const MIN_LENGTH = 8;

    /**
     * @return array<int, mixed>
     */
    public function rules(): array
    {
        return [
            'string',
            Password::min(self::MIN_LENGTH)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols(),
        ];
    }

    /**
     * Útil para validação manual fora do `validator` Laravel padrão.
     */
    public function validate(string $password): bool
    {
        return Validator::make(
            ['password' => $password],
            ['password' => $this->rules()],
        )->passes();
    }
}
