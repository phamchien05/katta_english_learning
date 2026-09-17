<?php

namespace Tests\Feature;

use App\Livewire\Vocabulary\Quiz;
use App\Models\StudySession;
use App\Models\User;
use App\Models\Vocabulary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

// Kiểm tra module Từ vựng (mục 4): chọn cấp độ, làm bài kiểm tra, chấm điểm, xem kết quả
class VocabularyQuizTest extends TestCase
{
    use RefreshDatabase;

    public function test_level_selection_page_shows_all_six_levels(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('vocabulary.index'));

        $response->assertOk();
        foreach (['A1', 'A2', 'B1', 'B2', 'C1', 'C2'] as $level) {
            $response->assertSee($level);
        }
    }

    public function test_invalid_level_returns_404(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/vocabulary/Z9')->assertNotFound();
    }

    public function test_quiz_scores_correct_and_incorrect_answers_then_saves_study_session(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Seed đúng 3 từ cấp A1 để bài test gọn (thay vì 50)
        $v1 = Vocabulary::factory()->create(['level' => 'A1', 'word' => 'cat', 'meaning_vi' => 'con mèo']);
        $v2 = Vocabulary::factory()->create(['level' => 'A1', 'word' => 'dog', 'meaning_vi' => 'con chó']);
        $v3 = Vocabulary::factory()->create(['level' => 'A1', 'word' => 'book', 'meaning_vi' => 'quyển sách/cuốn sách']);

        $component = Livewire::test(Quiz::class, ['level' => 'A1']);

        // Thứ tự câu hỏi random (inRandomOrder) nên tra theo từ hiện tại thay vì giả định vị trí cố định.
        // Trả lời đúng "cat" và "book", sai "dog" -> kỳ vọng 2/3 đúng.
        $answers = [
            'cat' => 'con mèo',
            'dog' => 'sai rồi',
            'book' => 'cuốn sách', // 1 trong các đáp án chấp nhận, phân cách bởi "/"
        ];

        for ($i = 0; $i < 3; $i++) {
            $word = $component->get('currentVocabulary')->word;
            $component->set('currentInput', $answers[$word])->call('submitAnswer');
        }

        $component->assertSet('answeredCount', 3);
        $component->assertSet('allAnswered', true);

        $component->call('finishTest');

        $component->assertSet('finished', true);
        $component->assertSet('score', 2);

        $this->assertDatabaseHas('study_sessions', [
            'user_id' => $user->id,
            'type' => 'vocabulary',
        ]);
        $this->assertSame(1, StudySession::where('user_id', $user->id)->count());
    }

    public function test_finish_test_is_ignored_until_all_questions_answered(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Vocabulary::factory()->count(2)->create(['level' => 'A1']);

        $component = Livewire::test(Quiz::class, ['level' => 'A1']);
        $component->call('finishTest');

        $component->assertSet('finished', false);
    }
}
