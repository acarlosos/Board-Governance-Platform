<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    public function test_guest_uses_default_app_locale_for_translations(): void
    {
        $this->assertSame('pt_BR', config('app.locale'));

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee(__('messages.welcome.heading', [], 'pt_BR'), false);
    }

    public function test_authenticated_user_spanish_locale_is_applied(): void
    {
        $user = User::factory()->create(['locale' => 'es']);

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertOk();
        $response->assertSee(__('messages.welcome.heading', [], 'es'), false);
    }

    public function test_invalid_user_locale_falls_back_to_app_default(): void
    {
        $user = User::factory()->create(['locale' => 'invalid_locale']);

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertOk();
        $response->assertSee(__('messages.welcome.heading', [], 'pt_BR'), false);
    }
}
