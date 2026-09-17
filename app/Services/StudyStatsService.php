<?php

namespace App\Services;

use App\Models\StudySession;
use Carbon\Carbon;
use Illuminate\Support\Collection;

// Service tính các số liệu thống kê học tập dùng chung cho Trang chủ / Thống kê / Tiến trình
class StudyStatsService
{
    /**
     * Tính chuỗi ngày học liên tiếp (streak) tính đến hôm nay.
     * Nếu hôm nay chưa học thì vẫn tính từ hôm qua trở về trước (không mất streak ngay trong ngày).
     */
    public function calculateStreak(int $userId): int
    {
        $studiedDates = StudySession::where('user_id', $userId)
            ->selectRaw('DATE(completed_at) as d')
            ->distinct()
            ->pluck('d')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->flip(); // để isset() tra cứu nhanh O(1)

        $streak = 0;
        $cursor = Carbon::today();

        // Nếu hôm nay chưa học, bắt đầu đếm từ hôm qua
        if (! isset($studiedDates[$cursor->toDateString()])) {
            $cursor->subDay();
        }

        while (isset($studiedDates[$cursor->toDateString()])) {
            $streak++;
            $cursor->subDay();
        }

        return $streak;
    }

    /**
     * Số buổi học hoàn thành mỗi ngày trong tuần hiện tại (Thứ 2 -> Chủ nhật).
     * Trả về Collection dạng ['Mon' => 0, 'Tue' => 2, ...] theo đúng thứ tự.
     */
    public function weeklyChartData(int $userId): Collection
    {
        $startOfWeek = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $endOfWeek = Carbon::now()->endOfWeek(Carbon::SUNDAY);

        $counts = StudySession::where('user_id', $userId)
            ->whereBetween('completed_at', [$startOfWeek, $endOfWeek])
            ->selectRaw('DATE(completed_at) as d, COUNT(*) as total')
            ->groupBy('d')
            ->pluck('total', 'd');

        $labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        $result = collect();

        foreach ($labels as $i => $label) {
            $date = $startOfWeek->copy()->addDays($i)->toDateString();
            $result[$label] = (int) ($counts[$date] ?? 0);
        }

        return $result;
    }
}
