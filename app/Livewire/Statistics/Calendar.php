<?php

namespace App\Livewire\Statistics;

use App\Models\StudySession;
use Carbon\Carbon;
use Livewire\Component;

// Lịch tháng đánh dấu ngày có buổi học hoàn thành (mục 10/11) - chuyển tháng trước/sau bằng nút mũi tên.
class Calendar extends Component
{
    public int $year;

    public int $month;

    public function mount(): void
    {
        $this->year = now()->year;
        $this->month = now()->month;
    }

    public function prevMonth(): void
    {
        $date = Carbon::create($this->year, $this->month, 1)->subMonthNoOverflow();
        $this->year = $date->year;
        $this->month = $date->month;
    }

    public function nextMonth(): void
    {
        $date = Carbon::create($this->year, $this->month, 1)->addMonthNoOverflow();
        $this->year = $date->year;
        $this->month = $date->month;
    }

    // Danh sách ngày (1-31) trong tháng đang xem có ít nhất 1 buổi học hoàn thành
    public function getActiveDaysProperty(): array
    {
        $start = Carbon::create($this->year, $this->month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth()->endOfDay();

        return StudySession::where('user_id', auth()->id())
            ->whereBetween('completed_at', [$start, $end])
            ->selectRaw('DISTINCT DATE(completed_at) as d')
            ->pluck('d')
            ->map(fn ($d) => (int) Carbon::parse($d)->day)
            ->all();
    }

    public function render()
    {
        $first = Carbon::create($this->year, $this->month, 1);
        $today = now();

        return view('livewire.statistics.calendar', [
            'daysInMonth' => $first->daysInMonth,
            // dayOfWeekIso: 1=Thứ 2 (Mon) .. 7=Chủ nhật (Sun) - khớp đúng cột T2..CN
            'leadingBlanks' => $first->dayOfWeekIso - 1,
            'monthLabel' => $first->locale(app()->getLocale())->translatedFormat('F Y'),
            'isCurrentMonth' => $today->year === $this->year && $today->month === $this->month,
            'todayDay' => $today->day,
        ]);
    }
}
