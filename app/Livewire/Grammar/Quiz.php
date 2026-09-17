<?php

namespace App\Livewire\Grammar;

use App\Models\GrammarQuestionSet;
use App\Models\StudySession;
use Carbon\Carbon;
use Livewire\Component;

// Component làm bộ đề luyện tập ngữ pháp (Phần B, mục 1.2) - 30-50 câu (fill/mcq/multi xen kẽ),
// chấm 1 lần cho tất cả câu giống Đọc hiểu (nhận toàn bộ answers từ Alpine, tránh bug đụng độ request
// rời rạc đã gặp ở Đọc hiểu). Nếu bộ đề đã completed (xem lại từ "Các đề đã làm"), hiển thị luôn kết
// quả đã lưu, không cho làm lại.
class Quiz extends Component
{
    public int $setId;

    /** @var array<int,mixed> question_id => câu trả lời (string hoặc mảng với type=multi) */
    public array $answers = [];

    public bool $graded = false;

    public int $score = 0;

    /** @var array<int,bool> question_id => đúng/sai sau khi chấm */
    public array $correctness = [];

    // Phải là public - Livewire chỉ lưu giữ property public giữa các request
    public int $startedAtTimestamp = 0;

    public function mount(int $setId): void
    {
        $this->setId = $setId;
        $this->startedAtTimestamp = now()->timestamp;

        if (! $this->set) {
            return;
        }

        // Đã nộp trước đó (xem lại từ "Các đề đã làm") - hiển thị luôn kết quả đã lưu, không chấm lại
        if ($this->set->status === 'completed') {
            $this->answers = $this->set->answers ?? [];
            $this->score = $this->set->score ?? 0;
            $this->graded = true;
            $this->correctness = $this->set->questions
                ->mapWithKeys(fn ($q) => [$q->id => $this->isCorrect($q->type, $this->answers[$q->id] ?? null, $q->correct_answer)])
                ->all();

            return;
        }

        // Bù kho: chỉ dispatch ĐÚNG 1 LẦN cho lần chuyển available -> in_progress này, kể cả khi user
        // tải lại trang nhiều lần (tránh sinh thừa nhiều bộ đề cho cùng 1 lượt lấy đề).
        if (! $this->set->replenish_dispatched) {
            $this->set->update(['replenish_dispatched' => true]);
            $this->dispatch('set-picked', topic: $this->set->topic_key);
        }
    }

    public function getSetProperty(): ?GrammarQuestionSet
    {
        return GrammarQuestionSet::with('questions')->find($this->setId);
    }

    public function getTotalQuestionsProperty(): int
    {
        return $this->set?->questions->count() ?? 0;
    }

    // Nhận TOÀN BỘ câu trả lời 1 lần từ Alpine (client-side) khi bấm nộp bài - cùng pattern đã chứng
    // minh ổn định ở Đọc hiểu, tránh các request rời rạc đụng độ ghi đè lẫn nhau.
    public function submitAnswers(array $answers): void
    {
        if (! $this->set || $this->set->status !== 'in_progress' || $this->set->user_id !== auth()->id()) {
            return;
        }

        // question_id trong $answers tới từ JS nên key luôn là string - ép lại về int cho khớp $q->id
        $answers = collect($answers)->mapWithKeys(fn ($v, $k) => [(int) $k => $v])->all();

        if (count($answers) < $this->totalQuestions) {
            return; // chưa đủ câu trả lời, không cho nộp
        }

        $this->answers = $answers;

        $score = 0;
        $correctness = [];

        foreach ($this->set->questions as $q) {
            $userAnswer = $answers[$q->id] ?? null;
            $isCorrect = $this->isCorrect($q->type, $userAnswer, $q->correct_answer);
            $correctness[$q->id] = $isCorrect;
            if ($isCorrect) {
                $score++;
            }
        }

        $this->correctness = $correctness;
        $this->score = $score;
        $this->graded = true;

        $this->set->update([
            'status' => 'completed',
            'score' => $score,
            'total' => $this->totalQuestions,
            'answers' => $answers,
            'completed_at' => now(),
        ]);

        StudySession::create([
            'user_id' => auth()->id(),
            'type' => 'grammar',
            'duration_seconds' => max(1, now()->timestamp - $this->startedAtTimestamp),
            'completed_at' => Carbon::now(),
        ]);
    }

    protected function isCorrect(string $type, mixed $userAnswer, array $correctAnswer): bool
    {
        $normalize = fn ($s) => mb_strtolower(trim((string) $s));

        if ($type === 'multi') {
            $user = array_map($normalize, is_array($userAnswer) ? $userAnswer : []);
            $correct = array_map($normalize, $correctAnswer);
            sort($user);
            sort($correct);
            return $user === $correct;
        }

        return $normalize($userAnswer) === $normalize($correctAnswer[0] ?? '');
    }

    public function render()
    {
        return view('livewire.grammar.quiz');
    }
}
