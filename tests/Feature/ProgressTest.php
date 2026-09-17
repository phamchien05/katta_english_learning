<?php

namespace Tests\Feature;

use App\Models\GrammarQuestionSet;
use App\Models\ReadingPassage;
use App\Models\ReadingQuestion;
use App\Models\ReadingSubmission;
use App\Models\TranslationPassage;
use App\Models\TranslationSubmission;
use App\Models\User;
use App\Models\VocabularySubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Kiểm tra module Tiến trình (mục 10): gom lịch sử làm bài từ Từ vựng/Ngữ pháp/Đọc hiểu/Dịch thành
// 1 danh sách chung, hiển thị đúng điểm + chi tiết từng câu, không lẫn dữ liệu của user khác.
class ProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_shows_empty_state_for_new_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('progress.index'));

        $response->assertOk();
        $response->assertSee(__('progress.empty_state'));
    }

    public function test_shows_vocabulary_submission_with_detail(): void
    {
        $user = User::factory()->create();
        VocabularySubmission::create([
            'user_id' => $user->id,
            'level' => 'A1',
            'score' => 1,
            'total' => 2,
            'results' => [
                ['word' => 'cat', 'ipa' => '/kæt/', 'user_answer' => 'con mèo', 'correct_answer' => 'con mèo', 'is_correct' => true],
                ['word' => 'dog', 'ipa' => '/dɒg/', 'user_answer' => 'con gà', 'correct_answer' => 'con chó', 'is_correct' => false],
            ],
        ]);

        $response = $this->actingAs($user)->get(route('progress.index'));

        $response->assertOk();
        $response->assertSee(__('progress.vocabulary_title', ['level' => 'A1']));
        $response->assertSee('50%', false);
        $response->assertSee('cat');
        $response->assertSee('con gà');
        $response->assertSee('con chó');
    }

    public function test_shows_grammar_completed_set_with_detail(): void
    {
        $user = User::factory()->create();
        $set = GrammarQuestionSet::factory()->create([
            'topic_key' => 'tenses',
            'status' => 'completed',
            'user_id' => $user->id,
            'score' => 1,
            'total' => 1,
            'answers' => [],
            'completed_at' => now(),
        ]);
        $q = $set->questions()->create([
            'type' => 'fill',
            'question' => 'She ____ to school.',
            'options' => [],
            'correct_answer' => ['goes'],
            'order' => 0,
        ]);
        $set->update(['answers' => [$q->id => 'goes']]);

        $response = $this->actingAs($user)->get(route('progress.index'));

        $response->assertOk();
        $response->assertSee('She ____ to school.');
        $response->assertSee('100%', false);
    }

    public function test_shows_reading_submission_with_detail(): void
    {
        $user = User::factory()->create();
        $passage = ReadingPassage::factory()->create(['title' => 'A Reading Passage']);
        $q = ReadingQuestion::factory()->create([
            'passage_id' => $passage->id,
            'type' => 'fill',
            'question' => 'The cat sat on the ____.',
            'correct_answer' => ['mat'],
        ]);
        ReadingSubmission::create([
            'user_id' => $user->id,
            'passage_id' => $passage->id,
            'score' => 0,
            'total' => 1,
            'answers' => [$q->id => 'sofa'],
        ]);

        $response = $this->actingAs($user)->get(route('progress.index'));

        $response->assertOk();
        $response->assertSee('A Reading Passage');
        $response->assertSee('The cat sat on the ____.');
        $response->assertSee('sofa');
        $response->assertSee('mat');
        $response->assertSee('0%', false);
    }

    public function test_shows_translate_submission_with_source_and_feedback(): void
    {
        $user = User::factory()->create();
        $passage = TranslationPassage::factory()->create(['level' => 'B1', 'source_text' => 'Hello world.']);
        TranslationSubmission::create([
            'user_id' => $user->id,
            'passage_id' => $passage->id,
            'user_translation' => 'Xin chào thế giới.',
            'ai_score' => 85,
            'ai_feedback' => 'Well translated.',
        ]);

        $response = $this->actingAs($user)->get(route('progress.index'));

        $response->assertOk();
        $response->assertSee(__('progress.translate_title', ['level' => 'B1']));
        $response->assertSee('Hello world.');
        $response->assertSee('Xin chào thế giới.');
        $response->assertSee('Well translated.');
        $response->assertSee('85%', false);
    }

    public function test_does_not_show_other_users_history(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        VocabularySubmission::create([
            'user_id' => $stranger->id,
            'level' => 'A1',
            'score' => 1,
            'total' => 1,
            'results' => [['word' => 'secret', 'ipa' => '', 'user_answer' => 'x', 'correct_answer' => 'x', 'is_correct' => true]],
        ]);

        $response = $this->actingAs($user)->get(route('progress.index'));

        $response->assertOk();
        $response->assertSee(__('progress.empty_state'));
        $response->assertDontSee('secret');
    }

    public function test_history_sorted_by_most_recent_first(): void
    {
        $user = User::factory()->create();
        VocabularySubmission::create([
            'user_id' => $user->id, 'level' => 'A1', 'score' => 1, 'total' => 1,
            'results' => [['word' => 'older', 'ipa' => '', 'user_answer' => 'x', 'correct_answer' => 'x', 'is_correct' => true]],
            'created_at' => now()->subDays(2),
        ]);
        VocabularySubmission::create([
            'user_id' => $user->id, 'level' => 'A1', 'score' => 1, 'total' => 1,
            'results' => [['word' => 'newer', 'ipa' => '', 'user_answer' => 'x', 'correct_answer' => 'x', 'is_correct' => true]],
            'created_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('progress.index'));

        $content = $response->getContent();
        $this->assertTrue(strpos($content, 'newer') < strpos($content, 'older'));
    }

    public function test_vocabulary_quiz_persists_submission_on_finish(): void
    {
        $user = User::factory()->create();
        \App\Models\Vocabulary::factory()->count(2)->create(['level' => 'A1', 'meaning_vi' => 'nghĩa']);

        $component = \Livewire\Livewire::actingAs($user)->test(\App\Livewire\Vocabulary\Quiz::class, ['level' => 'A1']);
        $ids = $component->get('questionIds');
        foreach ($ids as $id) {
            $component->set('answers.' . $id, 'nghĩa');
        }
        $component->call('finishTest');

        $this->assertSame(1, VocabularySubmission::where('user_id', $user->id)->count());
        $submission = VocabularySubmission::first();
        $this->assertSame(count($ids), $submission->total);
        $this->assertSame(count($ids), $submission->score);
    }
}
