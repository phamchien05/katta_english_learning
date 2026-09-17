<?php

namespace App\Livewire\Vocabulary;

use App\Models\StudySession;
use App\Models\UserVocabProgress;
use App\Models\Vocabulary;
use App\Models\VocabularySubmission;
use Carbon\Carbon;
use Livewire\Component;

// Component quiz Từ vựng (mục 4) - chọn 1 cấp độ, làm 50 câu, chấm điểm, xem kết quả chi tiết.
class Quiz extends Component
{
    public string $level;

    /** @var int[] Danh sách id từ vựng theo thứ tự cố định trong suốt bài làm */
    public array $questionIds = [];

    public int $currentIndex = 0;

    /** @var array<int,string> vocabulary_id => câu trả lời của user */
    public array $answers = [];

    public string $currentInput = '';

    public bool $finished = false;

    public int $score = 0;

    /** @var array<int,array> kết quả chi tiết từng câu sau khi nộp bài */
    public array $results = [];

    // Phải là public - Livewire chỉ lưu giữ property public giữa các request (mỗi lần gọi action là 1 request mới)
    public int $startedAtTimestamp = 0;

    public function mount(string $level): void
    {
        $this->level = $level;
        // Lấy ngẫu nhiên tối đa 50 từ trong cả kho từ vựng của cấp độ - mỗi lần vào bài sẽ ra bộ từ khác nhau
        // Chỉ lấy từ đã có nghĩa tiếng Việt (meaning_vi) - tránh lỗi khi từ chưa kịp dịch
        $this->questionIds = Vocabulary::where('level', $level)
            ->whereNotNull('meaning_vi')
            ->inRandomOrder()
            ->limit(50)
            ->pluck('id')
            ->toArray();

        $this->startedAtTimestamp = now()->timestamp;
    }

    public function getCurrentVocabularyProperty(): ?Vocabulary
    {
        $id = $this->questionIds[$this->currentIndex] ?? null;

        return $id ? Vocabulary::find($id) : null;
    }

    public function getAnsweredCountProperty(): int
    {
        return count($this->answers);
    }

    public function getAllAnsweredProperty(): bool
    {
        return $this->answeredCount >= count($this->questionIds);
    }

    // Nhảy tới câu bất kỳ trong lưới bên trái
    public function selectQuestion(int $index): void
    {
        if (! isset($this->questionIds[$index])) {
            return;
        }

        $this->currentIndex = $index;
        $this->currentInput = $this->answers[$this->questionIds[$index]] ?? '';
    }

    // Lưu câu trả lời hiện tại rồi sang câu tiếp theo
    public function submitAnswer(): void
    {
        $vocabId = $this->questionIds[$this->currentIndex];
        $this->answers[$vocabId] = trim($this->currentInput);

        if ($this->currentIndex < count($this->questionIds) - 1) {
            $this->currentIndex++;
            $this->currentInput = $this->answers[$this->questionIds[$this->currentIndex]] ?? '';
        }
    }

    // Chấm điểm toàn bộ bài, lưu buổi học, chuyển sang màn kết quả
    public function finishTest(): void
    {
        if (! $this->allAnswered) {
            return;
        }

        $vocabularies = Vocabulary::whereIn('id', $this->questionIds)->get()->keyBy('id');
        $score = 0;
        $results = [];

        foreach ($this->questionIds as $vocabId) {
            $vocab = $vocabularies[$vocabId];
            $userAnswer = $this->answers[$vocabId] ?? '';
            $isCorrect = $this->isAnswerCorrect($userAnswer, $vocab->meaning_vi);

            if ($isCorrect) {
                $score++;
            }

            $results[] = [
                'word' => $vocab->word,
                'ipa' => $vocab->ipa,
                'user_answer' => $userAnswer,
                'correct_answer' => $vocab->meaning_vi,
                'is_correct' => $isCorrect,
            ];

            // Trả lời đúng -> đánh dấu từ đã thuộc (dùng cho "Từ đã thuộc" ở Trang chủ/Thống kê).
            // Không hạ mức "đã thuộc" xuống lại nếu lần sau trả lời sai - is_mastered chỉ tăng dần.
            if ($isCorrect) {
                UserVocabProgress::updateOrCreate(
                    ['user_id' => auth()->id(), 'vocabulary_id' => $vocabId],
                    ['is_mastered' => true, 'last_reviewed_at' => now()]
                );
            } else {
                UserVocabProgress::firstOrCreate(
                    ['user_id' => auth()->id(), 'vocabulary_id' => $vocabId],
                    ['is_mastered' => false, 'last_reviewed_at' => now()]
                );
            }
        }

        $this->score = $score;
        $this->results = $results;
        $this->finished = true;

        // Lưu lại đầy đủ kết quả (khác StudySession chỉ ghi nhận đã học, không có điểm/chi tiết) -
        // dùng cho "Tiến trình" (mục 10), giống Đọc hiểu/Ngữ pháp/Dịch.
        VocabularySubmission::create([
            'user_id' => auth()->id(),
            'level' => $this->level,
            'score' => $score,
            'total' => count($this->questionIds),
            'results' => $results,
        ]);

        StudySession::create([
            'user_id' => auth()->id(),
            'type' => 'vocabulary',
            'duration_seconds' => max(1, now()->timestamp - $this->startedAtTimestamp),
            'completed_at' => Carbon::now(),
        ]);
    }

    // So khớp đơn giản: cho phép nhiều đáp án đúng cách nhau bởi / , ;
    protected function isAnswerCorrect(string $userAnswer, string $correctMeaning): bool
    {
        $normalize = fn ($s) => mb_strtolower(trim($s));

        $candidates = preg_split('/[\/,;]/u', $correctMeaning);

        foreach ($candidates as $candidate) {
            if ($normalize($userAnswer) === $normalize($candidate)) {
                return true;
            }
        }

        return false;
    }

    public function render()
    {
        return view('livewire.vocabulary.quiz');
    }
}
