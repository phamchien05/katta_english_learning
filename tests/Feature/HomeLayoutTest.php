<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Kiểm tra khung layout (sidebar + header) hoạt động đúng cho user đã đăng nhập,
// và khách chưa đăng nhập bị chuyển hướng sang trang login.
class HomeLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_authenticated_user_sees_sidebar_and_header(): void
    {
        $user = User::factory()->create(['name' => 'Nguyễn Văn A']);

        $response = $this->actingAs($user)->get('/');

        $response->assertOk();
        $response->assertSee('Katta');
        $response->assertSee(__('nav.home'));
        $response->assertSee($user->email);
    }

    public function test_all_sidebar_routes_load_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $routes = [
            'vocabulary.index', 'translate.index', 'reading.index', 'grammar.index',
            'idioms.index', 'listening.index', 'speaking.index', 'ai-chat.index',
            'progress.index', 'statistics.index', 'feedback.index', 'settings.index',
        ];

        foreach ($routes as $name) {
            $this->actingAs($user)->get(route($name))->assertOk();
        }
    }
}
