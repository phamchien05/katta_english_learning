<?php

namespace Tests\Feature;

use App\Livewire\Listening\Practice;
use App\Models\ListeningPassage;
use App\Models\ListeningQuestion;
use App\Models\ListeningSubmission;
use App\Models\StudySession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

// Kiểm tra module Nghe (mục 8): danh sách theo chủ đề, làm bài, chấm 4 loại câu hỏi, transcript ẩn cho
// tới khi nộp bài, lịch sử + làm lại, kho tự bù với cấp độ cố định theo từng dạng bài.
class ListeningPracticeTest extends TestCase
{
    use RefreshDatabase;

    protected function makePassageWithAllQuestionTypes(): ListeningPassage
    {
        $passage = ListeningPassage::factory()->create([
            'topic' => 'academic_lecture',
            'title' => 'The History of Coffee',
            'transcript' => 'Today I want to talk about the history of coffee and how it spread around the world.',
        ]);

        ListeningQuestion::factory()->create([
            'passage_id' => $passage->id,
            'type' => 'fill',
            'question' => 'Today the speaker wants to talk about the history of ____.',
            'options' => [],
            'correct_answer' => ['coffee'],
            'order' => 0,
        ]);

        ListeningQuestion::factory()->create([
            'passage_id' => $passage->id,
            'type' => 'boolean',
            'question' => 'The talk is about coffee.',
            'options' => [],
            'correct_answer' => ['True'],
            'order' => 1,
        ]);

        ListeningQuestion::factory()->create([
            'passage_id' => $passage->id,
            'type' => 'mcq',
            'question' => 'What is the talk about?',
            'options' => ['Tea', 'Coffee', 'Wine', 'Water'],
            'correct_answer' => ['Coffee'],
            'order' => 2,
        ]);

        ListeningQuestion::factory()->create([
            'passage_id' => $passage->id,
            'type' => 'multi',
            'question' => 'Which are mentioned? (choose all)',
            'options' => ['Coffee', 'History', 'World', 'Tea'],
            'correct_answer' => ['Coffee', 'History'],
            'order' => 3,
        ]);

        return $passage->fresh();
    }

    public function test_general_topic_picks_a_random_passage_for_the_level(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $a1 = ListeningPassage::factory()->create(['topic' => 'general', 'level' => 'A1']);
        ListeningPassage::factory()->create(['topic' => 'general', 'level' => 'B2']);

        $this->get(route('listening.general', 'A1'))->assertRedirect(route('listening.show', $a1));
    }

    public function test_general_topic_avoids_passages_already_listened_to_by_the_user(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $done = ListeningPassage::factory()->create(['topic' => 'general', 'level' => 'A1']);
        $fresh = ListeningPassage::factory()->create(['topic' => 'general', 'level' => 'A1']);

        ListeningSubmission::create([
            'user_id' => $user->id, 'passage_id' => $done->id, 'score' => 3, 'total' => 5, 'answers' => [],
        ]);

        for ($i = 0; $i < 10; $i++) {
            $this->get(route('listening.general', 'A1'))->assertRedirect(route('listening.show', $fresh));
        }
    }

    public function test_general_topic_invalid_level_returns_404(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/listening/general/Z9')->assertNotFound();
    }

    public function test_index_page_shows_topics(): void
    {
        $user = User::factory()->create();
        ListeningPassage::factory()->create(['topic' => 'academic_lecture', 'title' => 'A Lecture Clip']);

        $response = $this->actingAs($user)->get(route('listening.index'));

        $response->assertOk();
        $response->assertSee('A Lecture Clip');
        $response->assertSee(__('listening.topics.academic_lecture'));
    }

    public function test_practice_page_shows_title_but_hides_transcript_before_grading(): void
    {
        $user = User::factory()->create();
        $passage = $this->makePassageWithAllQuestionTypes();

        $response = $this->actingAs($user)->get(route('listening.show', $passage));

        $response->assertOk();
        $response->assertSee($passage->title);
        // Transcript không được in ra chữ trước khi nộp bài - chỉ dùng để đọc giọng nói qua JS (Alpine data)
        $response->assertDontSee('the history of coffee and how it spread around the world', false);
    }

    public function test_grades_all_four_question_types_correctly(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $passage = $this->makePassageWithAllQuestionTypes();
        $questions = $passage->questions;

        $component = Livewire::test(Practice::class, ['passageId' => $passage->id]);

        $component->call('submitAnswers', [
            $questions[0]->id => 'Coffee',
            $questions[1]->id => 'True',
            $questions[2]->id => 'Tea',
            $questions[3]->id => ['History', 'Coffee'],
        ]);

        $component->assertSet('graded', true);
        $component->assertSet('score', 3);

        $this->assertDatabaseHas('listening_submissions', [
            'user_id' => $user->id,
            'passage_id' => $passage->id,
            'score' => 3,
            'total' => 4,
        ]);
        $this->assertSame(1, StudySession::where('user_id', $user->id)->where('type', 'listening')->count());
    }

    public function test_transcript_is_revealed_after_grading(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $passage = $this->makePassageWithAllQuestionTypes();
        $questions = $passage->questions;

        $component = Livewire::test(Practice::class, ['passageId' => $passage->id]);
        $component->call('submitAnswers', [
            $questions[0]->id => 'coffee',
            $questions[1]->id => 'True',
            $questions[2]->id => 'Coffee',
            $questions[3]->id => ['History', 'Coffee'],
        ]);

        $component->assertSee('the history of coffee and how it spread around the world');
    }

    public function test_cannot_submit_with_missing_answers(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $passage = $this->makePassageWithAllQuestionTypes();
        $questions = $passage->questions;

        $component = Livewire::test(Practice::class, ['passageId' => $passage->id]);
        $component->call('submitAnswers', [
            $questions[0]->id => 'coffee',
            $questions[1]->id => 'True',
        ]);

        $component->assertSet('graded', false);
    }

    public function test_history_tab_lists_past_listens_with_retry_link(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $passage = ListeningPassage::factory()->create(['title' => 'My Past Listen']);

        ListeningSubmission::create([
            'user_id' => $user->id, 'passage_id' => $passage->id, 'score' => 4, 'total' => 5, 'answers' => [],
        ]);

        $response = $this->get(route('listening.index'));

        $response->assertOk();
        $response->assertSee('My Past Listen');
        $response->assertSee(route('listening.show', $passage), false);
    }

    public function test_get_transcript_is_blocked_after_max_plays_reached(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $passage = $this->makePassageWithAllQuestionTypes();

        $component = Livewire::test(Practice::class, ['passageId' => $passage->id]);

        for ($i = 0; $i < Practice::MAX_PLAYS; $i++) {
            $result = $component->instance()->getTranscript();
            $this->assertTrue($result['allowed']);
            $this->assertNotEmpty($result['text']);
        }

        // Đã dùng hết lượt - lần tiếp theo bị chặn, không trả transcript
        $result = $component->instance()->getTranscript();
        $this->assertFalse($result['allowed']);
        $this->assertSame('', $result['text']);
    }

    public function test_get_transcript_is_unlimited_after_grading(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $passage = $this->makePassageWithAllQuestionTypes();
        $questions = $passage->questions;

        $component = Livewire::test(Practice::class, ['passageId' => $passage->id]);
        // Dùng hết lượt nghe trước khi nộp bài
        for ($i = 0; $i < Practice::MAX_PLAYS; $i++) {
            $component->instance()->getTranscript();
        }

        $component->call('submitAnswers', [
            $questions[0]->id => 'coffee',
            $questions[1]->id => 'True',
            $questions[2]->id => 'Coffee',
            $questions[3]->id => ['History', 'Coffee'],
        ]);

        // Sau khi nộp bài, nghe lại thoải mái không giới hạn nữa
        $result = $component->instance()->getTranscript();
        $this->assertTrue($result['allowed']);
    }

    public function test_replenish_sends_existing_titles_to_gemini_to_avoid_duplicate_content(): void
    {
        // Đúng lỗi thực tế đã gặp: sinh liên tiếp cùng topic/level ra bài trùng gần như y hệt nhau
        // (3 bài "academic_lecture" cùng tiêu đề "The Evolution of Urban Green Spaces") - vì prompt
        // không hề nhắc AI tránh các bài đã có sẵn trong kho.
        $user = User::factory()->create();
        $this->actingAs($user);

        ListeningPassage::factory()->create(['topic' => 'academic_lecture', 'title' => 'The Evolution of Urban Green Spaces']);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => json_encode([
                        'title' => 'A Different Lecture',
                        'transcript' => 'Some other generated transcript.',
                        'questions' => [
                            ['type' => 'fill', 'question' => 'Q1?', 'options' => [], 'correct_answer' => ['answer']],
                            ['type' => 'boolean', 'question' => 'Q2?', 'options' => [], 'correct_answer' => ['True']],
                            ['type' => 'mcq', 'question' => 'Q3?', 'options' => ['A', 'B'], 'correct_answer' => ['A']],
                            ['type' => 'multi', 'question' => 'Q4?', 'options' => ['A', 'B', 'C'], 'correct_answer' => ['A', 'B']],
                            ['type' => 'fill', 'question' => 'Q5?', 'options' => [], 'correct_answer' => ['answer2']],
                        ],
                    ])]]]],
                ],
            ], 200),
        ]);

        config(['services.gemini.key' => 'fake-key-for-test']);

        $this->postJson(route('listening.replenish'), ['topic' => 'academic_lecture'])->assertNoContent();

        Http::assertSent(function ($request) {
            $prompt = $request->data()['contents'][0]['parts'][0]['text'] ?? '';
            return str_contains($prompt, 'The Evolution of Urban Green Spaces')
                && str_contains($prompt, 'must be clearly DIFFERENT');
        });
    }

    public function test_replenish_endpoint_uses_fixed_level_for_non_general_topics(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => json_encode([
                        'title' => 'Generated Clip',
                        'transcript' => 'Some generated transcript for testing.',
                        'questions' => [
                            ['type' => 'fill', 'question' => 'Q1?', 'options' => [], 'correct_answer' => ['answer']],
                            ['type' => 'boolean', 'question' => 'Q2?', 'options' => [], 'correct_answer' => ['True']],
                            ['type' => 'mcq', 'question' => 'Q3?', 'options' => ['A', 'B'], 'correct_answer' => ['A']],
                            ['type' => 'multi', 'question' => 'Q4?', 'options' => ['A', 'B', 'C'], 'correct_answer' => ['A', 'B']],
                            ['type' => 'fill', 'question' => 'Q5?', 'options' => [], 'correct_answer' => ['answer2']],
                        ],
                    ])]]]],
                ],
            ], 200),
        ]);

        config(['services.gemini.key' => 'fake-key-for-test']);

        // academic_lecture luôn gắn cứng cấp C1 (bỏ qua "level" gửi lên nếu có)
        $this->postJson(route('listening.replenish'), ['topic' => 'academic_lecture', 'level' => 'A1'])
            ->assertNoContent();

        $this->assertDatabaseHas('listening_passages', ['title' => 'Generated Clip', 'topic' => 'academic_lecture', 'level' => null]);
        $passage = ListeningPassage::where('title', 'Generated Clip')->first();
        $this->assertSame(5, $passage->questions()->count());
    }
}
