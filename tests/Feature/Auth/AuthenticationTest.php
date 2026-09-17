<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response
            ->assertOk()
            ->assertSeeVolt('pages.auth.login');
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $component = Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'password');

        $component->call('login');

        $component
            ->assertHasNoErrors()
            ->assertRedirect(route('home', absolute: false));

        $this->assertAuthenticated();
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $component = Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'wrong-password');

        $component->call('login');

        $component
            ->assertHasErrors()
            ->assertNoRedirect();

        $this->assertGuest();
    }

    public function test_navigation_menu_can_be_rendered(): void
    {
        // Sidebar/header của Katta là Blade tĩnh (không phải Livewire component), test qua trang chủ.
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->get(route('home'));

        $response
            ->assertOk()
            ->assertSee('Katta');
    }

    public function test_users_can_logout(): void
    {
        // Logout của Katta là route POST thường (xem routes/web.php), không phải action Livewire.
        $user = User::factory()->create();

        $this->actingAs($user);

        $this->post(route('logout'))->assertRedirect('/');

        $this->assertGuest();
    }
}
