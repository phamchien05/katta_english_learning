<?php

namespace Tests\Feature;

use App\Livewire\Reading\Practice;
use App\Models\ReadingPassage;
use App\Models\ReadingQuestion;
use App\Models\ReadingSubmission;
use App\Models\StudySession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

// Kiểm tra module Đọc (mục 6): danh sách theo chủ đề, làm bài, chấm 4 loại câu hỏi, lịch sử + làm lại
class ReadingPracticeTest extends TestCase
{
    use RefreshDatabase;

    protected function makePassageWithAllQuestionTypes(): ReadingPassage
    {
        $passage = ReadingPassage::factory()->create([
            'topic' => 'technology',
            'title' => 'The Rise of Smart Homes',
            'content' => 'Modern technology has transformed our living spaces into smart homes.',
        ]);

        ReadingQuestion::factory()->create([
            'passage_id' => $passage->id,
            'type' => 'fill',
            'question' => 'Technology has transformed our living spaces into ____.',
            'options' => [],
            'correct_answer' => ['smart homes'],
            'order' => 0,
        ]);

        ReadingQuestion::factory()->create([
            'passage_id' => $passage->id,
            'type' => 'boolean',
            'question' => 'Smart homes use technology.',
            'options' => [],
            'correct_answer' => ['True'],
            'order' => 1,
        ]);

        ReadingQuestion::factory()->create([
            'passage_id' => $passage->id,
            'type' => 'mcq',
            'question' => 'What has technology transformed?',
            'options' => ['Living spaces', 'Cars', 'Food', 'Weather'],
            'correct_answer' => ['Living spaces'],
            'order' => 2,
        ]);

        ReadingQuestion::factory()->create([
            'passage_id' => $passage->id,
            'type' => 'multi',
            'question' => 'Which are mentioned? (choose all)',
            'options' => ['Smart homes', 'Smart cars', 'Technology', 'Weather'],
            'correct_answer' => ['Smart homes', 'Technology'],
            'order' => 3,
        ]);

        return $passage->fresh();
    }

    public function test_general_topic_picks_a_random_passage_for_the_level(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $a1 = ReadingPassage::factory()->create(['topic' => 'general', 'level' => 'A1']);
        ReadingPassage::factory()->create(['topic' => 'general', 'level' => 'B2']); // cấp khác, không được chọn

        $this->get(route('reading.general', 'A1'))->assertRedirect(route('reading.show', $a1));
    }

    public function test_general_topic_avoids_passages_already_read_by_the_user(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $done = ReadingPassage::factory()->create(['topic' => 'general', 'level' => 'A1']);
        $fresh = ReadingPassage::factory()->create(['topic' => 'general', 'level' => 'A1']);

        ReadingSubmission::create([
            'user_id' => $user->id, 'passage_id' => $done->id, 'score' => 3, 'total' => 5, 'answers' => [],
        ]);

        for ($i = 0; $i < 10; $i++) {
            $this->get(route('reading.general', 'A1'))->assertRedirect(route('reading.show', $fresh));
        }
    }

    public function test_general_topic_avoids_a_passage_just_viewed_even_without_submitting(): void
    {
        // Đúng lỗi thực tế đã báo: chỉ XEM (chưa nộp bài) vẫn phải bị loại trừ khỏi lần random tiếp theo,
        // không chỉ loại trừ bài đã nộp xong.
        $user = User::factory()->create();
        $this->actingAs($user);

        $seen = ReadingPassage::factory()->create(['topic' => 'general', 'level' => 'A1']);
        $other = ReadingPassage::factory()->create(['topic' => 'general', 'level' => 'A1']);

        // Lần 1: vào xem $seen (giả sử random trúng nó) nhưng KHÔNG nộp bài
        $this->get(route('reading.show', $seen))->assertOk();

        // Các lần bấm "Tổng quan A1" tiếp theo không được ra lại $seen nữa
        for ($i = 0; $i < 10; $i++) {
            $this->get(route('reading.general', 'A1'))->assertRedirect(route('reading.show', $other));
        }
    }

    public function test_general_topic_invalid_level_returns_404(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/reading/general/Z9')->assertNotFound();
    }

    public function test_index_page_shows_topics(): void
    {
        $user = User::factory()->create();
        ReadingPassage::factory()->create(['topic' => 'science', 'title' => 'A Science Passage']);

        $response = $this->actingAs($user)->get(route('reading.index'));

        $response->assertOk();
        $response->assertSee('A Science Passage');
        $response->assertSee(__('reading.topics.science'));
    }

    public function test_practice_page_shows_passage_and_questions(): void
    {
        $user = User::factory()->create();
        $passage = $this->makePassageWithAllQuestionTypes();

        $response = $this->actingAs($user)->get(route('reading.show', $passage));

        $response->assertOk();
        $response->assertSee($passage->title);
        $response->assertSee('Technology has transformed our living spaces into');
    }

    public function test_grades_all_four_question_types_correctly(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $passage = $this->makePassageWithAllQuestionTypes();
        $questions = $passage->questions;

        $component = Livewire::test(Practice::class, ['passageId' => $passage->id]);

        // Alpine gom toàn bộ câu trả lời ở trình duyệt rồi gửi 1 lần duy nhất - mô phỏng đúng bằng cách
        // gọi submitAnswers() với cả mảng answers cùng lúc (không set từng trường một như trước, tránh
        // pattern gây ra bug đụng độ request thực tế đã gặp).
        $component->call('submitAnswers', [
            $questions[0]->id => 'Smart Homes', // fill: đúng (không phân biệt hoa/thường)
            $questions[1]->id => 'True', // boolean: đúng
            $questions[2]->id => 'Cars', // mcq: sai
            $questions[3]->id => ['Technology', 'Smart homes'], // multi: đúng
        ]);

        $component->assertSet('graded', true);
        $component->assertSet('score', 3); // fill đúng, boolean đúng, mcq sai, multi đúng

        $this->assertDatabaseHas('reading_submissions', [
            'user_id' => $user->id,
            'passage_id' => $passage->id,
            'score' => 3,
            'total' => 4,
        ]);
        $this->assertSame(1, StudySession::where('user_id', $user->id)->where('type', 'reading')->count());
    }

    public function test_cannot_submit_with_missing_answers(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $passage = $this->makePassageWithAllQuestionTypes();
        $questions = $passage->questions;

        $component = Livewire::test(Practice::class, ['passageId' => $passage->id]);
        // Chỉ gửi 3/4 câu - server phải từ chối chấm điểm
        $component->call('submitAnswers', [
            $questions[0]->id => 'Smart Homes',
            $questions[1]->id => 'True',
            $questions[2]->id => 'Cars',
        ]);

        $component->assertSet('graded', false);
    }

    public function test_history_tab_lists_past_reads_with_retry_link(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $passage = ReadingPassage::factory()->create(['title' => 'My Past Reading']);

        ReadingSubmission::create([
            'user_id' => $user->id,
            'passage_id' => $passage->id,
            'score' => 4,
            'total' => 5,
            'answers' => [],
        ]);

        $response = $this->get(route('reading.index'));

        $response->assertOk();
        $response->assertSee('My Past Reading');
        $response->assertSee(route('reading.show', $passage), false);
    }

    public function test_replenish_endpoint_adds_a_new_passage_with_questions(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => json_encode([
                        'title' => 'Generated Passage',
                        'content' => 'Some generated content for testing.',
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

        $this->postJson(route('reading.replenish'), ['topic' => 'technology', 'level' => 'B2'])
            ->assertNoContent();

        $this->assertDatabaseHas('reading_passages', ['title' => 'Generated Passage']);
        $passage = ReadingPassage::where('title', 'Generated Passage')->first();
        $this->assertSame(5, $passage->questions()->count());
    }
}
