<?php

namespace Tests\Feature;

use App\Livewire\Translate\Practice;
use App\Models\StudySession;
use App\Models\TranslationPassage;
use App\Models\TranslationSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

// Kiểm tra module Dịch (mục 5): chọn cấp độ, random đoạn văn, nộp bài chấm điểm
class TranslatePracticeTest extends TestCase
{
    use RefreshDatabase;

    public function test_level_selection_page_shows_five_levels(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('translate.index'));

        $response->assertOk();
        foreach (['A1', 'A2', 'B1', 'B2', 'C1'] as $level) {
            $response->assertSee($level);
        }
        // Từ vựng có C2 nhưng Dịch thì không (mục 5)
        $response->assertDontSee('C2');
    }

    public function test_invalid_level_returns_404(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/translate/Z9')->assertNotFound();
    }

    public function test_practice_page_shows_a_passage_for_the_level(): void
    {
        $user = User::factory()->create();
        TranslationPassage::factory()->create(['level' => 'A1', 'direction' => 'en_vi', 'source_text' => 'Hello world example passage.']);

        $response = $this->actingAs($user)->get(route('translate.practice', 'A1'));

        $response->assertOk();
        $response->assertSee('Hello world example passage.');
    }

    public function test_submitting_translation_saves_submission_and_study_session(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        TranslationPassage::factory()->create(['level' => 'A1', 'direction' => 'en_vi']);

        $component = Livewire::test(Practice::class, ['level' => 'A1']);
        $component->set('userTranslation', 'Đây là bản dịch thử nghiệm của tôi.');
        $component->call('submitForGrading');

        $component->assertSet('graded', true);

        $this->assertDatabaseHas('translation_submissions', [
            'user_id' => $user->id,
            'user_translation' => 'Đây là bản dịch thử nghiệm của tôi.',
        ]);

        $this->assertSame(1, StudySession::where('user_id', $user->id)->where('type', 'translate')->count());
    }

    public function test_direction_switch_changes_session_and_affects_practice_passage(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        TranslationPassage::factory()->create(['level' => 'A1', 'direction' => 'en_vi', 'source_text' => 'English passage here.']);
        TranslationPassage::factory()->create(['level' => 'A1', 'direction' => 'vi_en', 'source_text' => 'Đoạn văn tiếng Việt ở đây.']);

        // Mặc định chưa đổi gì -> chiều en_vi
        $this->get(route('translate.practice', 'A1'))->assertSee('English passage here.');

        // Đổi sang vi_en
        $this->get(route('translate.direction.switch', 'vi_en'))->assertRedirect(route('translate.index'));
        $this->assertSame('vi_en', session('translate_direction'));

        $this->get(route('translate.practice', 'A1'))->assertSee('Đoạn văn tiếng Việt ở đây.');
    }

    public function test_shuffle_picks_a_different_passage(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        TranslationPassage::factory()->count(5)->create(['level' => 'A1', 'direction' => 'en_vi']);

        $component = Livewire::test(Practice::class, ['level' => 'A1']);
        $firstId = $component->get('passageId');

        $component->call('shuffle');
        $secondId = $component->get('passageId');

        $this->assertNotSame($firstId, $secondId);
    }

    public function test_picking_a_passage_avoids_ones_the_user_already_translated(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $done = TranslationPassage::factory()->create(['level' => 'A1', 'direction' => 'en_vi']);
        $fresh = TranslationPassage::factory()->create(['level' => 'A1', 'direction' => 'en_vi']);

        TranslationSubmission::create([
            'user_id' => $user->id,
            'passage_id' => $done->id,
            'user_translation' => 'bản dịch cũ',
            'ai_score' => 80,
        ]);

        // Gọi nhiều lần để chắc chắn không phải trùng hợp ngẫu nhiên
        for ($i = 0; $i < 10; $i++) {
            $component = Livewire::test(Practice::class, ['level' => 'A1']);
            $component->assertSet('passageId', $fresh->id);
        }
    }

    public function test_retry_link_loads_the_specific_passage(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $target = TranslationPassage::factory()->create(['level' => 'A1', 'direction' => 'en_vi', 'source_text' => 'Đoạn cần dịch lại.']);
        TranslationPassage::factory()->count(5)->create(['level' => 'A1', 'direction' => 'en_vi']);

        $response = $this->get(route('translate.practice', ['level' => 'A1', 'passage' => $target->id]));

        $response->assertOk();
        $response->assertSee('Đoạn cần dịch lại.');
    }

    public function test_translated_history_tab_lists_past_submissions_with_retry_link(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $passage = TranslationPassage::factory()->create(['level' => 'B1', 'direction' => 'en_vi', 'source_text' => 'Lịch sử bài dịch của tôi.']);
        TranslationSubmission::create([
            'user_id' => $user->id,
            'passage_id' => $passage->id,
            'user_translation' => 'bản dịch của tôi',
            'ai_score' => 100,
        ]);

        $response = $this->get(route('translate.index'));

        $response->assertOk();
        $response->assertSee('Lịch sử bài dịch của tôi.');
        $response->assertSee(route('translate.practice', ['level' => 'B1', 'passage' => $passage->id]), false);
    }

    public function test_replenish_endpoint_adds_a_new_passage_using_gemini(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => json_encode(['Đây là đoạn văn mới do AI sinh để bù kho.'])]]]],
                ],
            ], 200),
        ]);

        config(['services.gemini.key' => 'fake-key-for-test']);

        $countBefore = TranslationPassage::where('level', 'A1')->where('direction', 'en_vi')->count();

        $this->postJson(route('translate.replenish'), ['level' => 'A1', 'direction' => 'en_vi'])
            ->assertNoContent();

        $this->assertSame($countBefore + 1, TranslationPassage::where('level', 'A1')->where('direction', 'en_vi')->count());
        $this->assertDatabaseHas('translation_passages', ['source_text' => 'Đây là đoạn văn mới do AI sinh để bù kho.']);
    }
}
