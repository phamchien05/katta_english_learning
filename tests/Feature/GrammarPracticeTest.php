<?php

namespace Tests\Feature;

use App\Livewire\Grammar\Quiz;
use App\Models\GrammarQuestionSet;
use App\Models\StudySession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

// Kiểm tra Phần B của module Ngữ pháp (mục 7): chọn chủ đề, "kho đề" tự bù (available -> in_progress ->
// completed, không bao giờ lặp lại), làm bài 3 loại câu hỏi (fill/mcq/multi), lịch sử "Các đề đã làm".
class GrammarPracticeTest extends TestCase
{
    use RefreshDatabase;

    protected function makeSetWithAllQuestionTypes(string $topicKey = 'tenses', string $status = 'available'): GrammarQuestionSet
    {
        $set = GrammarQuestionSet::factory()->create(['topic_key' => $topicKey, 'status' => $status]);

        $set->questions()->create([
            'type' => 'fill',
            'question' => 'She ____ to school every day.',
            'options' => [],
            'correct_answer' => ['goes'],
            'order' => 0,
        ]);

        $set->questions()->create([
            'type' => 'mcq',
            'question' => 'Which sentence is in the present simple?',
            'options' => ['He is running.', 'He runs.', 'He ran.', 'He will run.'],
            'correct_answer' => ['He runs.'],
            'order' => 1,
        ]);

        $set->questions()->create([
            'type' => 'multi',
            'question' => 'Which of these are present tenses? (choose all)',
            'options' => ['Present Simple', 'Present Continuous', 'Past Simple', 'Future Perfect'],
            'correct_answer' => ['Present Simple', 'Present Continuous'],
            'order' => 2,
        ]);

        return $set->fresh();
    }

    protected function fakeGeminiQuestionSet(int $count = 30): void
    {
        $questions = [];
        for ($i = 0; $i < $count; $i++) {
            $questions[] = [
                'type' => 'fill',
                'question' => "Test question {$i} ____.",
                'options' => [],
                'correct_answer' => ["answer{$i}"],
            ];
        }

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => json_encode(['questions' => $questions])]]]],
                ],
            ], 200),
        ]);

        config(['services.gemini.key' => 'fake-key-for-test']);
    }

    public function test_practice_index_shows_five_topic_cards(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('grammar.practice'));

        $response->assertOk();
        foreach (['parts-of-speech', 'tenses', 'sentence-structures', 'question-forms', 'common-structures'] as $key) {
            $response->assertSee(__('grammar.practice_topics.' . $key . '.title'));
        }
    }

    public function test_practice_start_picks_an_available_set_and_marks_it_in_progress(): void
    {
        $user = User::factory()->create();
        $set = $this->makeSetWithAllQuestionTypes('tenses');

        $response = $this->actingAs($user)->get(route('grammar.practice.start', 'tenses'));

        $response->assertRedirect(route('grammar.practice.show', $set));
        $this->assertDatabaseHas('grammar_question_sets', [
            'id' => $set->id,
            'status' => 'in_progress',
            'user_id' => $user->id,
        ]);
    }

    public function test_practice_start_invalid_topic_returns_404(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/grammar/practice/not-a-real-topic/start')->assertNotFound();
    }

    public function test_practice_start_never_assigns_the_same_set_to_two_different_users(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $setOne = $this->makeSetWithAllQuestionTypes('tenses');
        $setTwo = $this->makeSetWithAllQuestionTypes('tenses');

        $this->actingAs($userA)->get(route('grammar.practice.start', 'tenses'))->assertRedirect();
        $taken = GrammarQuestionSet::where('status', 'in_progress')->first();

        $this->actingAs($userB)->get(route('grammar.practice.start', 'tenses'))
            ->assertRedirect(route('grammar.practice.show', $taken->id === $setOne->id ? $setTwo : $setOne));
    }

    public function test_practice_start_generates_synchronously_when_pool_is_empty(): void
    {
        // groundingContent() cần có sẵn cây lý thuyết (Phần A) để lấy tài liệu gốc cho AI sinh đề
        $this->seed(\Database\Seeders\GrammarTopicSeeder::class);
        $user = User::factory()->create();
        $this->fakeGeminiQuestionSet(30);

        $this->assertSame(0, GrammarQuestionSet::count());

        $response = $this->actingAs($user)->get(route('grammar.practice.start', 'tenses'));

        $response->assertRedirect();
        $this->assertSame(1, GrammarQuestionSet::count());
        $created = GrammarQuestionSet::first();
        $this->assertSame('in_progress', $created->status);
        $this->assertSame($user->id, $created->user_id);
        $this->assertSame(30, $created->questions()->count());
    }

    public function test_practice_start_returns_error_when_pool_empty_and_no_gemini_key(): void
    {
        $user = User::factory()->create();
        config(['services.gemini.key' => '']);

        $this->actingAs($user)->get(route('grammar.practice.start', 'tenses'))->assertStatus(503);
    }

    public function test_practice_show_forbidden_for_a_different_user(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $set = $this->makeSetWithAllQuestionTypes('tenses', 'in_progress');
        $set->update(['user_id' => $owner->id]);

        $this->actingAs($stranger)->get(route('grammar.practice.show', $set))->assertForbidden();
    }

    public function test_grades_fill_mcq_and_multi_types_correctly(): void
    {
        $user = User::factory()->create();
        $set = $this->makeSetWithAllQuestionTypes('tenses', 'in_progress');
        $set->update(['user_id' => $user->id]);
        $this->actingAs($user);
        $questions = $set->questions;

        $component = Livewire::test(Quiz::class, ['setId' => $set->id]);

        $component->call('submitAnswers', [
            $questions[0]->id => 'Goes', // fill: đúng (không phân biệt hoa/thường)
            $questions[1]->id => 'He is running.', // mcq: sai
            $questions[2]->id => ['Present Continuous', 'Present Simple'], // multi: đúng (không cần đúng thứ tự)
        ]);

        $component->assertSet('graded', true);
        $component->assertSet('score', 2);

        $this->assertDatabaseHas('grammar_question_sets', [
            'id' => $set->id,
            'status' => 'completed',
            'score' => 2,
            'total' => 3,
        ]);
        $this->assertSame(1, StudySession::where('user_id', $user->id)->where('type', 'grammar')->count());
    }

    public function test_cannot_submit_with_missing_answers(): void
    {
        $user = User::factory()->create();
        $set = $this->makeSetWithAllQuestionTypes('tenses', 'in_progress');
        $set->update(['user_id' => $user->id]);
        $this->actingAs($user);
        $questions = $set->questions;

        $component = Livewire::test(Quiz::class, ['setId' => $set->id]);
        $component->call('submitAnswers', [
            $questions[0]->id => 'goes',
            $questions[1]->id => 'He runs.',
        ]);

        $component->assertSet('graded', false);
        $this->assertDatabaseHas('grammar_question_sets', ['id' => $set->id, 'status' => 'in_progress']);
    }

    public function test_completed_set_never_returns_to_the_pool(): void
    {
        $user = User::factory()->create();
        $set = $this->makeSetWithAllQuestionTypes('tenses', 'in_progress');
        $set->update(['user_id' => $user->id]);
        $this->actingAs($user);
        $questions = $set->questions;

        Livewire::test(Quiz::class, ['setId' => $set->id])->call('submitAnswers', [
            $questions[0]->id => 'goes',
            $questions[1]->id => 'He runs.',
            $questions[2]->id => ['Present Simple', 'Present Continuous'],
        ]);

        $this->assertSame(0, GrammarQuestionSet::where('status', 'available')->count());
        $this->assertSame('completed', $set->fresh()->status);
    }

    public function test_history_tab_lists_completed_sets_with_review_link(): void
    {
        $user = User::factory()->create();
        $set = GrammarQuestionSet::factory()->create([
            'topic_key' => 'tenses',
            'status' => 'completed',
            'user_id' => $user->id,
            'score' => 25,
            'total' => 30,
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('grammar.practice'));

        $response->assertOk();
        $response->assertSee('25/30');
        $response->assertSee(route('grammar.practice.show', $set), false);
    }

    public function test_viewing_a_completed_set_shows_readonly_review(): void
    {
        $user = User::factory()->create();
        $set = $this->makeSetWithAllQuestionTypes('tenses', 'completed');
        $questions = $set->questions;
        $set->update([
            'user_id' => $user->id,
            'score' => 2,
            'total' => 3,
            'answers' => [
                $questions[0]->id => 'goes',
                $questions[1]->id => 'He runs.',
                $questions[2]->id => ['Present Simple', 'Present Continuous'],
            ],
        ]);
        $this->actingAs($user);

        $component = Livewire::test(Quiz::class, ['setId' => $set->id]);

        $component->assertSet('graded', true);
        $component->assertSet('score', 2);
    }

    public function test_replenish_endpoint_adds_a_new_available_set_for_the_topic(): void
    {
        $this->seed(\Database\Seeders\GrammarTopicSeeder::class);
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->fakeGeminiQuestionSet(30);

        $this->postJson(route('grammar.practice.replenish'), ['topic' => 'tenses'])
            ->assertNoContent();

        $this->assertSame(1, GrammarQuestionSet::where('topic_key', 'tenses')->where('status', 'available')->count());
        $this->assertSame(30, GrammarQuestionSet::first()->questions()->count());
    }

    public function test_quiz_mount_dispatches_replenish_event_only_once(): void
    {
        $user = User::factory()->create();
        $set = $this->makeSetWithAllQuestionTypes('tenses', 'in_progress');
        $set->update(['user_id' => $user->id]);
        $this->actingAs($user);

        Livewire::test(Quiz::class, ['setId' => $set->id])->assertDispatched('set-picked');
        $this->assertTrue($set->fresh()->replenish_dispatched);

        // Tải lại trang (mount lại component) không được dispatch thêm lần nữa
        Livewire::test(Quiz::class, ['setId' => $set->id])->assertNotDispatched('set-picked');
    }
}
