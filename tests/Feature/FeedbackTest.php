<?php

namespace Tests\Feature;

use App\Livewire\Feedback\NewForm;
use App\Models\Feedback;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

// Kiểm tra module Nhận xét (mục 13): gửi phản hồi (có/không kèm ảnh), tự lưu context_url từ Referer,
// validate bắt buộc, lịch sử chỉ hiện đúng của user đang đăng nhập, trạng thái mặc định "pending".
class FeedbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_shows_empty_history_for_new_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('feedback.index'));

        $response->assertOk();
        $response->assertSee(__('feedback.history_empty'));
    }

    public function test_page_captures_referer_as_context_url(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withHeaders(['referer' => 'http://katta.test/reading'])
            ->get(route('feedback.index'));

        $response->assertOk();
        $response->assertViewHas('contextUrl', 'http://katta.test/reading');
    }

    public function test_submit_creates_feedback_with_pending_status(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(NewForm::class, ['contextUrl' => 'http://katta.test/grammar'])
            ->set('category', 'bug')
            ->set('title', 'Nút nộp bài bị kẹt')
            ->set('message', 'Bấm nộp bài ở Ngữ pháp không có phản hồi gì cả.')
            ->call('submit')
            ->assertSet('submitted', true);

        $this->assertDatabaseHas('feedback', [
            'user_id' => $user->id,
            'category' => 'bug',
            'title' => 'Nút nộp bài bị kẹt',
            'status' => 'pending',
            'context_url' => 'http://katta.test/grammar',
        ]);
    }

    public function test_submit_requires_title_and_message(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(NewForm::class)
            ->set('title', '')
            ->set('message', '')
            ->call('submit')
            ->assertHasErrors(['title', 'message']);

        $this->assertSame(0, Feedback::count());
    }

    public function test_submit_stores_screenshot_on_public_disk(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(NewForm::class)
            ->set('category', 'suggestion')
            ->set('title', 'Thêm chế độ tối')
            ->set('message', 'App nên có dark mode cho đỡ chói mắt ban đêm.')
            ->set('screenshot', UploadedFile::fake()->create('screenshot.png', 100, 'image/png'))
            ->call('submit')
            ->assertSet('submitted', true);

        $feedback = Feedback::first();
        $this->assertNotNull($feedback->screenshot_path);
        Storage::disk('public')->assertExists($feedback->screenshot_path);
    }

    public function test_screenshot_must_be_an_image(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(NewForm::class)
            ->set('title', 'Test')
            ->set('message', 'Some message here.')
            ->set('screenshot', UploadedFile::fake()->create('not-an-image.pdf', 100))
            ->call('submit')
            ->assertHasErrors(['screenshot']);
    }

    public function test_another_resets_form_to_submit_more_feedback(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $component = Livewire::test(NewForm::class)
            ->set('title', 'First one')
            ->set('message', 'This is the first feedback message.')
            ->call('submit')
            ->assertSet('submitted', true);

        $component->call('another')
            ->assertSet('submitted', false)
            ->assertSet('title', '')
            ->assertSet('message', '');
    }

    public function test_history_only_shows_the_current_users_feedback(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        Feedback::create([
            'user_id' => $stranger->id, 'category' => 'bug', 'title' => 'Secret bug', 'message' => 'x', 'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->get(route('feedback.index'));

        $response->assertOk();
        $response->assertDontSee('Secret bug');
        $response->assertSee(__('feedback.history_empty'));
    }

    public function test_history_shows_own_feedback_with_status(): void
    {
        $user = User::factory()->create();
        Feedback::create([
            'user_id' => $user->id, 'category' => 'suggestion', 'title' => 'My idea',
            'message' => 'Please add this feature.', 'status' => 'resolved',
        ]);

        $response = $this->actingAs($user)->get(route('feedback.index'));

        $response->assertOk();
        $response->assertSee('My idea');
        $response->assertSee(__('feedback.status_resolved'));
    }
}
