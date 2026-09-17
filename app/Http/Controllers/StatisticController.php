<?php

namespace App\Http\Controllers;

use App\Models\GrammarQuestionSet;
use App\Models\ReadingSubmission;
use App\Models\StudySession;
use App\Models\TranslationSubmission;
use App\Models\UserVocabProgress;
use App\Models\Vocabulary;
use App\Models\VocabularySubmission;
use App\Services\StudyStatsService;
use Illuminate\Http\Request;

// Module Thống kê (mục 11): 6 ô số liệu tổng quan, lịch đánh dấu ngày học, từ vựng theo cấp độ,
// độ chính xác từng module - tái sử dụng đúng các câu query đã có ở Trang chủ (mục 3) cho nhất quán.
class StatisticController extends Controller
{
    public function __construct(protected StudyStatsService $stats)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $streak = $this->stats->calculateStreak($user->id);
        $sessionsCompleted = StudySession::where('user_id', $user->id)->count();
        $vocabMastered = UserVocabProgress::where('user_id', $user->id)->where('is_mastered', true)->count();
        $articlesRead = StudySession::where('user_id', $user->id)->where('type', 'reading')->count();
        $totalStudySeconds = (int) StudySession::where('user_id', $user->id)->sum('duration_seconds');
        $minutesToday = (int) round(
            StudySession::where('user_id', $user->id)->whereDate('completed_at', today())->sum('duration_seconds') / 60
        );

        return view('pages.statistics.index', [
            'title' => __('nav.statistics'),
            'icon' => 'bar-chart-2',
            'streak' => $streak,
            'sessionsCompleted' => $sessionsCompleted,
            'vocabMastered' => $vocabMastered,
            'articlesRead' => $articlesRead,
            'minutesToday' => $minutesToday,
            'totalDurationLabel' => $this->formatDuration($totalStudySeconds),
            'todayDurationLabel' => $minutesToday . 'm',
            'vocabByLevel' => $this->vocabularyByLevel($user->id),
            'accuracy' => $this->accuracyByType($user->id),
        ]);
    }

    protected function formatDuration(int $seconds): string
    {
        $minutes = (int) round($seconds / 60);

        return $minutes < 60 ? "{$minutes}m" : sprintf('%dh %dm', intdiv($minutes, 60), $minutes % 60);
    }

    // Số từ đã thuộc / tổng số từ mỗi cấp độ CEFR - chỉ trả về cấp có từ vựng (bỏ qua cấp rỗng)
    protected function vocabularyByLevel(int $userId): \Illuminate\Support\Collection
    {
        return collect(['A1', 'A2', 'B1', 'B2', 'C1', 'C2'])
            ->map(function (string $level) use ($userId) {
                $total = Vocabulary::where('level', $level)->whereNotNull('meaning_vi')->count();
                $mastered = UserVocabProgress::where('user_id', $userId)
                    ->where('is_mastered', true)
                    ->whereHas('vocabulary', fn ($q) => $q->where('level', $level))
                    ->count();

                return ['level' => $level, 'mastered' => $mastered, 'total' => $total];
            })
            ->filter(fn ($row) => $row['total'] > 0)
            ->values();
    }

    // % trung bình + số phiên đã hoàn thành cho từng module - chỉ trả về module đã có ít nhất 1 phiên
    protected function accuracyByType(int $userId): \Illuminate\Support\Collection
    {
        $rows = collect();

        $vocab = VocabularySubmission::where('user_id', $userId)
            ->selectRaw('COUNT(*) as sessions, SUM(score) as sum_score, SUM(total) as sum_total')->first();
        if ($vocab->sessions > 0) {
            $rows->push(['type' => 'vocabulary', 'percent' => (int) round($vocab->sum_score / max(1, $vocab->sum_total) * 100), 'sessions' => $vocab->sessions]);
        }

        $grammar = GrammarQuestionSet::where('user_id', $userId)->where('status', 'completed')
            ->selectRaw('COUNT(*) as sessions, SUM(score) as sum_score, SUM(total) as sum_total')->first();
        if ($grammar->sessions > 0) {
            $rows->push(['type' => 'grammar', 'percent' => (int) round($grammar->sum_score / max(1, $grammar->sum_total) * 100), 'sessions' => $grammar->sessions]);
        }

        $reading = ReadingSubmission::where('user_id', $userId)
            ->selectRaw('COUNT(*) as sessions, SUM(score) as sum_score, SUM(total) as sum_total')->first();
        if ($reading->sessions > 0) {
            $rows->push(['type' => 'reading', 'percent' => (int) round($reading->sum_score / max(1, $reading->sum_total) * 100), 'sessions' => $reading->sessions]);
        }

        $translate = TranslationSubmission::where('user_id', $userId)->whereNotNull('ai_score')
            ->selectRaw('COUNT(*) as sessions, AVG(ai_score) as avg_score')->first();
        if ($translate->sessions > 0) {
            $rows->push(['type' => 'translate', 'percent' => (int) round($translate->avg_score), 'sessions' => $translate->sessions]);
        }

        return $rows;
    }
}
