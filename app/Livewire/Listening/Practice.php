<?php

namespace App\Livewire\Listening;

use App\Models\ListeningPassage;
use App\Models\ListeningSubmission;
use App\Models\StudySession;
use App\Services\ListeningPassagePicker;
use Carbon\Carbon;
use Livewire\Component;

// Component làm bài nghe hiểu (mục 8) - y hệt Reading\Practice (chấm 1 lần cho tất cả câu, nhận toàn
// bộ answers từ Alpine). Khác biệt duy nhất: transcript KHÔNG hiển thị cho tới khi nộp bài xong - view
// chỉ dùng transcript để trình duyệt đọc thành giọng nói (Web Speech API), không in ra chữ trước đó.
class Practice extends Component
{
    // Số lượt nghe tối đa trước khi nộp bài (giống giới hạn nghe lại khi thi thật)
    public const MAX_PLAYS = 3;

    public int $passageId;

    /** @var array<int,mixed> question_id => câu trả lời (string hoặc mảng với type=multi) */
    public array $answers = [];

    public bool $graded = false;

    public int $score = 0;

    /** @var array<int,bool> question_id => đúng/sai sau khi chấm */
    public array $correctness = [];

    // Đếm số lượt đã bấm nghe - lưu ở server (không phải Alpine) để không bị sửa qua devtools
    public int $playCount = 0;

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

    public function getPassageProperty(): ?ListeningPassage
    {
        return ListeningPassage::with('questions')->find($this->passageId);
    }

    public function getTotalQuestionsProperty(): int
    {
        return $this->passage?->questions->count() ?? 0;
    }

    // Trả transcript về cho JS đọc thành giọng nói khi bấm Play - KHÔNG nhúng sẵn transcript vào HTML
    // ban đầu (mới đúng tinh thần "ẩn cho tới khi nộp bài"), và đếm lượt nghe ở server cho khó gian lận.
    public function getTranscript(): array
    {
        if (! $this->passage) {
            return ['text' => '', 'allowed' => false];
        }

        if (! $this->graded) {
            if ($this->playCount >= self::MAX_PLAYS) {
                return ['text' => '', 'allowed' => false];
            }
            $this->playCount++;
        }

        return ['text' => $this->passage->transcript, 'allowed' => true];
    }

    public function submitAnswers(array $answers): void
    {
        if (! $this->passage) {
            return;
        }

        $answers = collect($answers)->mapWithKeys(fn ($v, $k) => [(int) $k => $v])->all();

        if (count($answers) < $this->totalQuestions) {
            return;
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

        ListeningSubmission::create([
            'user_id' => auth()->id(),
            'passage_id' => $this->passage->id,
            'score' => $score,
            'total' => $this->passage->questions->count(),
            'answers' => $answers,
        ]);

        StudySession::create([
            'user_id' => auth()->id(),
            'type' => 'listening',
            'duration_seconds' => max(1, now()->timestamp - $this->startedAtTimestamp),
            'completed_at' => Carbon::now(),
        ]);
    }

    public function nextArticle()
    {
        $currentTopic = $this->passage?->topic;
        $currentLevel = $this->passage?->level;
        if (! $currentTopic) {
            return $this->redirect(route('listening.index'), navigate: false);
        }

        $picker = app(ListeningPassagePicker::class);
        $next = $picker->pickUnseen(auth()->id(), $currentTopic, $currentLevel, excludeId: $this->passageId);

        return $this->redirect($next ? route('listening.show', $next) : route('listening.index'), navigate: false);
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
        return view('livewire.listening.practice');
    }
}
