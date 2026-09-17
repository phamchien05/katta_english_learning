<?php

namespace Tests\Feature;

use App\Livewire\Settings\Form;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

// Kiểm tra module Cài đặt (mục 14): đổi ngôn ngữ mặc định (lưu lâu dài vào tài khoản), nhập/xoá API
// key Gemini riêng (mã hoá khi lưu, không ghi đè khi để trống), và các module khác ưu tiên dùng key
// riêng của user thay vì key chung khi đã có.
class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_shows_settings_form(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('settings.index'));

        $response->assertOk();
        $response->assertSee(__('settings.section_language'));
        $response->assertSee(__('settings.section_api_key'));
    }

    public function test_save_updates_locale_and_persists_to_user(): void
    {
        $user = User::factory()->create(['locale' => 'en']);
        $this->actingAs($user);

        Livewire::test(Form::class)
            ->set('locale', 'vi')
            ->call('save');

        $this->assertSame('vi', $user->fresh()->locale);
    }

    public function test_save_sets_gemini_api_key_when_provided(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(Form::class)
            ->set('geminiApiKey', 'my-secret-gemini-key-123')
            ->call('save')
            ->assertSet('hasSavedKey', true)
            ->assertSet('geminiApiKey', ''); // xoá khỏi form sau khi lưu, không hiện lại key thật

        $this->assertSame('my-secret-gemini-key-123', $user->fresh()->gemini_api_key);
    }

    public function test_gemini_api_key_is_encrypted_at_rest(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(Form::class)->set('geminiApiKey', 'my-secret-gemini-key-123')->call('save');

        $raw = DB::table('users')->where('id', $user->id)->value('gemini_api_key');
        $this->assertStringNotContainsString('my-secret-gemini-key-123', $raw);
    }

    public function test_save_does_not_overwrite_key_when_left_blank(): void
    {
        $user = User::factory()->create(['gemini_api_key' => 'already-saved-key']);
        $this->actingAs($user);

        Livewire::test(Form::class)
            ->set('locale', 'vi')
            ->set('geminiApiKey', '')
            ->call('save');

        $this->assertSame('already-saved-key', $user->fresh()->gemini_api_key);
    }

    public function test_remove_key_clears_saved_key(): void
    {
        $user = User::factory()->create(['gemini_api_key' => 'already-saved-key']);
        $this->actingAs($user);

        Livewire::test(Form::class)
            ->call('removeKey')
            ->assertSet('hasSavedKey', false);

        $this->assertNull($user->fresh()->gemini_api_key);
    }

    public function test_locale_switch_route_persists_to_authenticated_user(): void
    {
        $user = User::factory()->create(['locale' => null]);
        $this->actingAs($user);

        $this->get('/lang/vi')->assertRedirect();

        $this->assertSame('vi', $user->fresh()->locale);
    }

    public function test_set_locale_prefers_saved_user_preference_over_app_default(): void
    {
        $user = User::factory()->create(['locale' => 'vi']);

        $response = $this->actingAs($user)->get(route('settings.index'));

        $response->assertSee(__('settings.section_language', [], 'vi'));
    }

    public function test_reading_replenish_uses_users_own_gemini_key_when_set(): void
    {
        $user = User::factory()->create(['gemini_api_key' => 'users-own-key']);
        $this->actingAs($user);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => json_encode([
                        'title' => 'X', 'content' => 'Y',
                        'questions' => [
                            ['type' => 'fill', 'question' => 'Q1?', 'options' => [], 'correct_answer' => ['a']],
                            ['type' => 'boolean', 'question' => 'Q2?', 'options' => [], 'correct_answer' => ['True']],
                            ['type' => 'mcq', 'question' => 'Q3?', 'options' => ['A', 'B'], 'correct_answer' => ['A']],
                            ['type' => 'multi', 'question' => 'Q4?', 'options' => ['A', 'B', 'C'], 'correct_answer' => ['A', 'B']],
                            ['type' => 'fill', 'question' => 'Q5?', 'options' => [], 'correct_answer' => ['b']],
                        ],
                    ])]]]],
                ],
            ], 200),
        ]);

        // Không cấu hình key chung của app - nếu request vẫn thành công nghĩa là đã dùng đúng key riêng
        config(['services.gemini.key' => null]);

        $this->postJson(route('reading.replenish'), ['topic' => 'technology', 'level' => 'B2'])
            ->assertNoContent();

        Http::assertSent(fn ($request) => str_contains((string) $request->url(), 'key=users-own-key'));
        $this->assertDatabaseHas('reading_passages', ['title' => 'X']);
    }
}
