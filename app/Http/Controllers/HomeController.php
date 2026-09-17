<?php

namespace App\Http\Controllers;

use App\Models\StudySession;
use App\Models\TranslationSubmission;
use App\Models\UserVocabProgress;
use App\Services\StudyStatsService;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __construct(protected StudyStatsService $stats)
    {
    }

    // Trang chủ (mục 3): banner chào hỏi, thống kê dịch thuật, lưới tính năng,
    // biểu đồ 7 ngày, hàng thống kê nhỏ, hoạt động gần đây.
    public function index(Request $request)
    {
        $user = $request->user();

        // Lời chào theo giờ hệ thống
        $hour = now()->hour;
        $greetingKey = match (true) {
            $hour < 12 => 'home.greeting_morning',
            $hour < 18 => 'home.greeting_afternoon',
            default => 'home.greeting_evening',
        };

        $streak = $this->stats->calculateStreak($user->id);
        $sessionsCompleted = StudySession::where('user_id', $user->id)->count();
        $minutesToday = (int) round(
            StudySession::where('user_id', $user->id)->whereDate('completed_at', today())->sum('duration_seconds') / 60
        );

        // Thống kê dịch thuật (khung nổi trên banner)
        $translationCount = TranslationSubmission::where('user_id', $user->id)->count();
        $translationAvgScore = (int) round(
            TranslationSubmission::where('user_id', $user->id)->whereNotNull('ai_score')->avg('ai_score') ?? 0
        );

        // Hàng 5 ô thống kê nhỏ
        $vocabMastered = UserVocabProgress::where('user_id', $user->id)->where('is_mastered', true)->count();
        $articlesRead = StudySession::where('user_id', $user->id)->where('type', 'reading')->count();
        $totalStudySeconds = (int) StudySession::where('user_id', $user->id)->sum('duration_seconds');

        // Biểu đồ hành trình học tập 7 ngày (Mon-Sun tuần hiện tại)
        $weekly = $this->stats->weeklyChartData($user->id);
        $isActiveThisWeek = $weekly->sum() > 0;

        // Hoạt động gần đây
        $recentActivity = StudySession::where('user_id', $user->id)
            ->latest('completed_at')
            ->take(5)
            ->get();

        return view('pages.home', [
            'title' => __('nav.home'),
            'icon' => 'home',
            'user' => $user,
            'greetingKey' => $greetingKey,
            'streak' => $streak,
            'sessionsCompleted' => $sessionsCompleted,
            'minutesToday' => $minutesToday,
            'translationCount' => $translationCount,
            'translationAvgScore' => $translationAvgScore,
            'vocabMastered' => $vocabMastered,
            'articlesRead' => $articlesRead,
            'totalStudySeconds' => $totalStudySeconds,
            'weekly' => $weekly,
            'isActiveThisWeek' => $isActiveThisWeek,
            'recentActivity' => $recentActivity,
        ]);
    }
}
