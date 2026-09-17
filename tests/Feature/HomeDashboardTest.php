<?php

namespace Tests\Feature;

use App\Models\StudySession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Kiểm tra nội dung thật của Trang chủ: banner, lưới tính năng, streak, biểu đồ, hoạt động gần đây
class HomeDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_shows_expected_sections_for_new_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertOk();
        $response->assertSee(__('home.subtitle'));
        $response->assertSee(__('home.features_title'));
        $response->assertSee(__('home.journey_title'));
        $response->assertSee(__('home.recent_activity_empty'));
        $response->assertSee(__('home.pill_streak', ['n' => 0]));
    }

    public function test_streak_counts_consecutive_days_including_today(): void
    {
        $user = User::factory()->create();

        // Học liên tục hôm nay, hôm qua, hôm kia -> streak = 3
        StudySession::factory()->create(['user_id' => $user->id, 'completed_at' => now()]);
        StudySession::factory()->create(['user_id' => $user->id, 'completed_at' => now()->subDay()]);
        StudySession::factory()->create(['user_id' => $user->id, 'completed_at' => now()->subDays(2)]);
        // Ngày cách đây 5 hôm - không liên tục, không được tính vào streak
        StudySession::factory()->create(['user_id' => $user->id, 'completed_at' => now()->subDays(5)]);

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertOk();
        $response->assertSee(__('home.pill_streak', ['n' => 3]));
    }

    public function test_recent_activity_list_shows_when_sessions_exist(): void
    {
        $user = User::factory()->create();

        StudySession::factory()->create([
            'user_id' => $user->id,
            'type' => 'vocabulary',
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertOk();
        $response->assertDontSee(__('home.recent_activity_empty'));
        $response->assertSee('Vocabulary');
    }
}
