<?php

namespace App\Livewire\Reading;

use App\Models\ReadingPassage;
use App\Models\ReadingSubmission;
use App\Models\StudySession;
use Carbon\Carbon;
use Livewire\Component;

// Component làm bài đọc hiểu (mục 6) - hiện bài + câu hỏi, chấm 1 lần cho tất cả câu, hiện đúng/sai
// từng câu ngay tại chỗ (không phải màn kết quả riêng như Từ vựng).
class Practice extends Component
{
    public int $passageId;

    /** @var array<int,mixed> question_id => câu trả lời (string hoặc mảng với type=multi) */
    public array $answers = [];

    public bool $graded = false;

    public int $score = 0;

    /** @var array<int,bool> question_id => đúng/sai sau khi chấm */
    public array $correctness = [];

    // Phải là public - Livewire chỉ lưu giữ property public giữa các request
    public int $startedAtTimestamp = 0;

    public function mount(int $passageId): void
    {
        $this->passageId = $passageId;
        $this->startedAtTimestamp = now()->timestamp;

        if ($this->passage) {
            $this->dispatch('passage-picked', topic: $this->passage->topic, level: $this->passage->level);
        }
    }

    public function getPassageProperty(): ?ReadingPassage
    {
        return ReadingPassage::with('questions')->find($this->passageId);
    }

    public function getTotalQuestionsProperty(): int
    {
        return $this->passage?->questions->count() ?? 0;
    }

    // Nhận TOÀN BỘ câu trả lời 1 lần từ Alpine (client-side) khi bấm nộp bài - không đồng bộ từng câu
    // lên server trong lúc làm bài nữa, tránh các request rời rạc đụng độ ghi đè lẫn nhau (bug thực tế
    // đã gặp: bấm nhanh nhiều checkbox / gõ rồi bấm nộp ngay khiến 1 vài câu bị "quên" không tính).
    public function submitAnswers(array $answers): void
    {
        if (! $this->passage) {
            return;
        }

        // question_id trong $answers tới từ JS nên key luôn là string - ép lại về int cho khớp $q->id
        $answers = collect($answers)->mapWithKeys(fn ($v, $k) => [(int) $k => $v])->all();

        if (count($answers) < $this->totalQuestions) {
            return; // chưa đủ câu trả lời, không cho nộp (phòng trường hợp gọi thẳng bỏ qua UI)
        }

        $this->answers = $answers;

        $score = 0;
        $correctness = [];

        foreach ($this->passage->questions as $q) {
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

        ReadingSubmission::create([
            'user_id' => auth()->id(),
            'passage_id' => $this->passage->id,
            'score' => $score,
            'total' => $this->passage->questions->count(),
            'answers' => $answers,
        ]);

        StudySession::create([
            'user_id' => auth()->id(),
            'type' => 'reading',
            'duration_seconds' => max(1, now()->timestamp - $this->startedAtTimestamp),
            'completed_at' => Carbon::now(),
        ]);
    }

    // Chuyển sang 1 bài đọc khác cùng chủ đề (random, tránh bài vừa xem - không chỉ bài đã nộp)
    public function nextArticle()
    {
        $currentTopic = $this->passage?->topic;
        $currentLevel = $this->passage?->level;
        if (! $currentTopic) {
            return $this->redirect(route('reading.index'), navigate: false);
        }

        $picker = app(\App\Services\ReadingPassagePicker::class);
        $next = $picker->pickUnseen(auth()->id(), $currentTopic, $currentLevel, excludeId: $this->passageId);

        return $this->redirect($next ? route('reading.show', $next) : route('reading.index'), navigate: false);
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
        return view('livewire.reading.practice');
    }
}
