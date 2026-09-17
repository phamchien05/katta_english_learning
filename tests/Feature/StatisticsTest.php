<?php

namespace Tests\Feature;

use App\Livewire\Statistics\Calendar;
use App\Livewire\Vocabulary\Quiz;
use App\Models\StudySession;
use App\Models\User;
use App\Models\UserVocabProgress;
use App\Models\Vocabulary;
use App\Models\VocabularySubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

// Kiểm tra module Thống kê (mục 11): 6 ô số liệu, lịch chuyển tháng đánh dấu ngày học,
// từ vựng theo cấp độ, độ chính xác từng module - và việc làm bài Từ vựng đánh dấu "đã thuộc".
class StatisticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_shows_default_state_for_new_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('statistics.index'));

        $response->assertOk();
        $response->assertSee(__('statistics.page_title'));
        $response->assertSee(__('statistics.vocabulary_by_level_empty'));
        $response->assertSee(__('statistics.accuracy_empty'));
    }

    public function test_stat_tiles_reflect_real_data(): void
    {
        $user = User::factory()->create();
        StudySession::factory()->create(['user_id' => $user->id, 'type' => 'reading', 'duration_seconds' => 120, 'completed_at' => now()]);
        StudySession::factory()->create(['user_id' => $user->id, 'type' => 'reading', 'duration_seconds' => 60, 'completed_at' => now()]);
        $vocab = Vocabulary::factory()->create();
        UserVocabProgress::create(['user_id' => $user->id, 'vocabulary_id' => $vocab->id, 'is_mastered' => true]);

        $response = $this->actingAs($user)->get(route('statistics.index'));

        $response->assertOk();
        // 2 session, 1 từ đã thuộc, 2 bài đọc, tổng 180s = 3m
        $response->assertSeeText('3m');
    }

    public function test_vocabulary_by_level_shows_mastered_ratio(): void
    {
        $user = User::factory()->create();
        Vocabulary::factory()->count(3)->create(['level' => 'A1', 'meaning_vi' => 'x']);
        $mastered = Vocabulary::factory()->create(['level' => 'A1', 'meaning_vi' => 'x']);
        UserVocabProgress::create(['user_id' => $user->id, 'vocabulary_id' => $mastered->id, 'is_mastered' => true]);

        $response = $this->actingAs($user)->get(route('statistics.index'));

        $response->assertOk();
        $response->assertSee('A1');
        $response->assertSee(__('statistics.words_mastered_count', ['mastered' => 1, 'total' => 4]));
    }

    public function test_accuracy_only_shows_types_with_completed_sessions(): void
    {
        $user = User::factory()->create();
        VocabularySubmission::create(['user_id' => $user->id, 'level' => 'A1', 'score' => 8, 'total' => 10, 'results' => []]);

        $response = $this->actingAs($user)->get(route('statistics.index'));

        $response->assertOk();
        $response->assertSee(__('statistics.type_vocabulary'));
        $response->assertSee('80%', false);
        // "Grammar" vẫn chỉ xuất hiện ở sidebar (desktop + mobile drawer, layout include 2 lần) -
        // không được có thêm dòng độ chính xác nào cho Ngữ pháp vì user chưa hoàn thành bộ đề nào.
        $this->assertSame(2, substr_count($response->getContent(), __('statistics.type_grammar')));
    }

    public function test_calendar_marks_days_with_completed_sessions(): void
    {
        $user = User::factory()->create();
        StudySession::factory()->create(['user_id' => $user->id, 'completed_at' => now()->startOfMonth()->addDays(4)]);

        $component = Livewire::actingAs($user)->test(Calendar::class);

        $component->assertSet('year', now()->year);
        $expectedDay = now()->startOfMonth()->addDays(4)->day;
        $this->assertContains($expectedDay, $component->get('activeDays'));
    }

    public function test_calendar_month_navigation_changes_active_days(): void
    {
        $user = User::factory()->create();
        StudySession::factory()->create(['user_id' => $user->id, 'completed_at' => now()->subMonthNoOverflow()->startOfMonth()->addDays(2)]);

        $component = Livewire::actingAs($user)->test(Calendar::class);
        $component->call('prevMonth');

        $expectedDay = now()->subMonthNoOverflow()->startOfMonth()->addDays(2)->day;
        $this->assertContains($expectedDay, $component->get('activeDays'));
    }

    public function test_vocabulary_quiz_marks_correct_words_as_mastered(): void
    {
        $user = User::factory()->create();
        $correct = Vocabulary::factory()->create(['level' => 'A1', 'meaning_vi' => 'đúng']);
        $wrong = Vocabulary::factory()->create(['level' => 'A1', 'meaning_vi' => 'sai']);

        $component = Livewire::actingAs($user)->test(Quiz::class, ['level' => 'A1']);
        $component->set('questionIds', [$correct->id, $wrong->id]);
        $component->set('answers.' . $correct->id, 'đúng');
        $component->set('answers.' . $wrong->id, 'không đúng');
        $component->call('finishTest');

        $this->assertDatabaseHas('user_vocab_progress', [
            'user_id' => $user->id, 'vocabulary_id' => $correct->id, 'is_mastered' => true,
        ]);
        $this->assertDatabaseHas('user_vocab_progress', [
            'user_id' => $user->id, 'vocabulary_id' => $wrong->id, 'is_mastered' => false,
        ]);
    }
}
