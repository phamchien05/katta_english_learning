<?php

namespace App\Http\Controllers;

use App\Models\GrammarQuestionSet;
use App\Models\ReadingSubmission;
use App\Models\TranslationSubmission;
use App\Models\VocabularySubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

// Module "Tiến trình" (mục 10) - gom lịch sử làm bài từ cả 4 module (Từ vựng/Ngữ pháp/Đọc hiểu/Dịch)
// thành 1 danh sách chung, sắp theo thời gian, mỗi thẻ mở rộng ra xem chi tiết từng câu đã làm.
class ProgressController extends Controller
{
    // Màu badge theo loại - viết literal để Tailwind JIT quét thấy
    public const TYPE_STYLE = [
        'vocabulary' => 'bg-indigo-100 text-indigo-700',
        'grammar' => 'bg-rose-100 text-katta-accent',
        'reading' => 'bg-emerald-100 text-emerald-700',
        'translate' => 'bg-blue-100 text-blue-700',
    ];

    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $history = collect()
            ->merge($this->vocabularyHistory($userId))
            ->merge($this->grammarHistory($userId))
            ->merge($this->readingHistory($userId))
            ->merge($this->translateHistory($userId))
            ->sortByDesc('date')
            ->values();

        return view('pages.progress.index', [
            'title' => __('nav.progress'),
            'icon' => 'trending-up',
            'history' => $history,
            'typeStyle' => self::TYPE_STYLE,
        ]);
    }

    protected function vocabularyHistory(int $userId): Collection
    {
        return VocabularySubmission::where('user_id', $userId)->latest()->get()
            ->map(function (VocabularySubmission $s) {
                return [
                    'type' => 'vocabulary',
                    'title' => __('progress.vocabulary_title', ['level' => $s->level]),
                    'score' => $s->score,
                    'total' => $s->total,
                    'date' => $s->created_at,
                    'detail_kind' => 'qa',
                    'items' => collect($s->results)->map(fn ($r) => [
                        'question' => $r['word'] . (filled($r['ipa'] ?? null) ? ' [' . $r['ipa'] . ']' : ''),
                        'user_answer' => $r['user_answer'],
                        'correct_answer' => $r['correct_answer'],
                        'is_correct' => $r['is_correct'],
                    ])->all(),
                ];
            });
    }

    protected function grammarHistory(int $userId): Collection
    {
        return GrammarQuestionSet::with('questions')
            ->where('user_id', $userId)->where('status', 'completed')->latest('completed_at')->get()
            ->map(function (GrammarQuestionSet $s) {
                $answers = $s->answers ?? [];

                return [
                    'type' => 'grammar',
                    'title' => __('grammar.quiz_title', ['topic' => __('grammar.practice_topics.' . $s->topic_key . '.title')]),
                    'score' => $s->score,
                    'total' => $s->total,
                    'date' => $s->completed_at,
                    'detail_kind' => 'qa',
                    'items' => $s->questions->map(fn ($q) => [
                        'question' => $q->question,
                        'user_answer' => $this->formatAnswer($answers[$q->id] ?? null),
                        'correct_answer' => implode(', ', $q->correct_answer),
                        'is_correct' => $this->isCorrect($answers[$q->id] ?? null, $q->correct_answer),
                    ])->all(),
                ];
            });
    }

    protected function readingHistory(int $userId): Collection
    {
        return ReadingSubmission::with('passage.questions')
            ->where('user_id', $userId)->latest()->get()
            ->filter(fn (ReadingSubmission $s) => $s->passage !== null)
            ->map(function (ReadingSubmission $s) {
                $answers = $s->answers ?? [];

                return [
                    'type' => 'reading',
                    'title' => $s->passage->title,
                    'score' => $s->score,
                    'total' => $s->total,
                    'date' => $s->created_at,
                    'detail_kind' => 'qa',
                    'items' => $s->passage->questions->map(fn ($q) => [
                        'question' => $q->question,
                        'user_answer' => $this->formatAnswer($answers[$q->id] ?? null),
                        'correct_answer' => implode(', ', $q->correct_answer),
                        'is_correct' => $this->isCorrect($answers[$q->id] ?? null, $q->correct_answer),
                    ])->all(),
                ];
            })
            ->values();
    }

    protected function translateHistory(int $userId): Collection
    {
        return TranslationSubmission::with('passage')
            ->where('user_id', $userId)->latest()->get()
            ->filter(fn (TranslationSubmission $s) => $s->passage !== null)
            ->map(function (TranslationSubmission $s) {
                return [
                    'type' => 'translate',
                    'title' => __('progress.translate_title', ['level' => $s->passage->level]),
                    // Dịch chấm theo thang 0-100 (không phải X/Y câu đúng) - quy về cùng đơn vị % như các loại khác
                    'score' => $s->ai_score,
                    'total' => 100,
                    'date' => $s->created_at,
                    'detail_kind' => 'translate',
                    'source_text' => $s->passage->source_text,
                    'user_translation' => $s->user_translation,
                    'feedback' => $s->ai_feedback,
                ];
            })
            ->values();
    }

    // Câu trả lời có thể là chuỗi (fill/mcq) hoặc mảng (multi) - hiển thị gộp bằng dấu phẩy cho dễ đọc
    protected function formatAnswer(mixed $answer): string
    {
        if (is_array($answer)) {
            return implode(', ', $answer);
        }

        return (string) ($answer ?? '');
    }

    protected function isCorrect(mixed $userAnswer, array $correctAnswer): bool
    {
        $normalize = fn ($s) => mb_strtolower(trim((string) $s));

        if (is_array($userAnswer) || count($correctAnswer) > 1) {
            $user = array_map($normalize, is_array($userAnswer) ? $userAnswer : []);
            $correct = array_map($normalize, $correctAnswer);
            sort($user);
            sort($correct);
            return $user === $correct;
        }

        return $normalize($userAnswer) === $normalize($correctAnswer[0] ?? '');
    }
}
