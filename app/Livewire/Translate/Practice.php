<?php

namespace App\Livewire\Translate;

use App\Models\StudySession;
use App\Models\TranslationPassage;
use App\Models\TranslationSubmission;
use App\Services\GeminiService;
use Carbon\Carbon;
use Livewire\Component;

// Component luyện dịch (mục 5) - random 1 đoạn văn theo cấp độ + chiều dịch (EN->VI / VI->EN),
// chấm điểm bằng Gemini (có fallback so khớp đơn giản nếu chưa cấu hình API key).
class Practice extends Component
{
    public string $level;

    public string $direction = 'en_vi';

    public ?int $passageId = null;

    public string $userTranslation = '';

    public bool $graded = false;

    public ?int $score = null;

    public ?string $feedback = null;

    public ?string $referenceTranslation = null;

    /** @var string[] Danh sách lỗi cụ thể AI chỉ ra */
    public array $issues = [];

    // Phải là public - Livewire chỉ lưu giữ property public giữa các request (mỗi lần gọi action là 1 request mới)
    public int $startedAtTimestamp = 0;

    public function mount(string $level, ?int $passageId = null): void
    {
        $this->level = $level;
        $this->direction = session('translate_direction', 'en_vi');

        // "Dịch lại" 1 đoạn cụ thể (từ tab Đã dịch) - chỉ nhận nếu đúng level + direction hiện tại
        if ($passageId && TranslationPassage::where('id', $passageId)
            ->where('level', $level)->where('direction', $this->direction)->exists()) {
            $this->passageId = $passageId;
        } else {
            $this->pickPassage();
        }

        $this->startedAtTimestamp = now()->timestamp;
        $this->notifyPassageTaken();
    }

    public function getPassageProperty(): ?TranslationPassage
    {
        return $this->passageId ? TranslationPassage::find($this->passageId) : null;
    }

    // Random lại 1 đoạn văn khác cùng cấp độ + chiều dịch (không load lại trang)
    public function shuffle(): void
    {
        $this->pickPassage(excludeCurrent: true);
        $this->reset('userTranslation', 'graded', 'score', 'feedback', 'referenceTranslation', 'issues');
        $this->startedAtTimestamp = now()->timestamp;
        $this->notifyPassageTaken();
    }

    // Ưu tiên đoạn user CHƯA từng làm (để không lặp lại), rơi về random toàn bộ nếu đã làm hết
    protected function pickPassage(bool $excludeCurrent = false): void
    {
        $attemptedIds = TranslationSubmission::where('user_id', auth()->id())->pluck('passage_id');

        $base = TranslationPassage::where('level', $this->level)->where('direction', $this->direction);
        if ($excludeCurrent && $this->passageId) {
            $base->where('id', '!=', $this->passageId);
        }

        $fresh = (clone $base)->whereNotIn('id', $attemptedIds)->inRandomOrder()->value('id');
        $this->passageId = $fresh ?? $base->inRandomOrder()->value('id') ?? $this->passageId;
    }

    // Báo cho JS biết vừa "lấy" 1 đoạn ra làm - JS sẽ âm thầm gọi route bù kho (xem practice.blade.php)
    protected function notifyPassageTaken(): void
    {
        $this->dispatch('passage-picked', level: $this->level, direction: $this->direction);
    }

    public function submitForGrading(): void
    {
        $text = trim($this->userTranslation);
        if ($text === '' || ! $this->passage) {
            return;
        }

        // Ưu tiên key Gemini riêng của user (Cài đặt, mục 14) - không có thì dùng key chung mặc định
        $apiKey = auth()->user()?->geminiApiKey();

        $result = $apiKey ? $this->gradeWithGemini($apiKey, $this->passage->source_text, $text) : null;

        // Fallback: so khớp đơn giản nếu chưa có API key hoặc Gemini lỗi (mục 5 cho phép)
        if ($result === null) {
            $result = $this->simpleGrade($text);
        }

        $this->score = $result['score'];
        $this->feedback = $result['feedback'];
        $this->referenceTranslation = $result['reference_translation'] ?? null;
        $this->issues = $result['issues'] ?? [];
        $this->graded = true;

        TranslationSubmission::create([
            'user_id' => auth()->id(),
            'passage_id' => $this->passage->id,
            'user_translation' => $text,
            'ai_score' => $this->score,
            'ai_feedback' => $this->feedback,
        ]);

        StudySession::create([
            'user_id' => auth()->id(),
            'type' => 'translate',
            'duration_seconds' => max(1, now()->timestamp - $this->startedAtTimestamp),
            'completed_at' => Carbon::now(),
        ]);
    }

    protected function gradeWithGemini(string $apiKey, string $source, string $translation): ?array
    {
        $gemini = new GeminiService($apiKey);

        $sourceLang = $this->direction === 'en_vi' ? 'tiếng Anh' : 'tiếng Việt';
        $targetLang = $this->direction === 'en_vi' ? 'tiếng Việt' : 'tiếng Anh';

        $prompt = <<<PROMPT
        Bạn là giáo viên chấm bài dịch {$sourceLang}-{$targetLang}. Chấm bản dịch của học viên cho đoạn văn
        {$sourceLang} dưới đây, theo thang điểm 0-100 (độ chính xác nghĩa, ngữ pháp, tự nhiên khi đọc).

        Đoạn văn gốc ({$sourceLang}):
        {$source}

        Bản dịch của học viên ({$targetLang}):
        {$translation}

        Trả về CHÍNH XÁC 1 object JSON gồm:
        - "score": số nguyên 0-100
        - "feedback": nhận xét tổng quát ngắn gọn (2-3 câu) bằng tiếng Việt
        - "reference_translation": 1 bản dịch mẫu chuẩn, tự nhiên, bằng {$targetLang}
        - "issues": mảng chuỗi, mỗi phần tử là 1 lỗi CỤ THỂ trong bài của học viên (trích đúng phần bị sai +
          giải thích ngắn tại sao sai), tối đa 5 lỗi quan trọng nhất; nếu không có lỗi thì trả về mảng rỗng []

        Không thêm chữ nào khác ngoài object JSON.
        PROMPT;

        $schema = [
            'type' => 'OBJECT',
            'properties' => [
                'score' => ['type' => 'INTEGER'],
                'feedback' => ['type' => 'STRING'],
                'reference_translation' => ['type' => 'STRING'],
                'issues' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
            ],
            'required' => ['score', 'feedback', 'reference_translation', 'issues'],
        ];

        $result = $gemini->generate($prompt, $schema);

        if (! is_array($result) || ! isset($result['score'])) {
            return null;
        }

        return [
            'score' => max(0, min(100, (int) $result['score'])),
            'feedback' => $result['feedback'] ?? '',
            'reference_translation' => $result['reference_translation'] ?? null,
            'issues' => array_values(array_filter($result['issues'] ?? [])),
        ];
    }

    // Chấm điểm giả lập đơn giản khi chưa có Gemini API key: dựa trên độ dài bản dịch so với gốc
    protected function simpleGrade(string $translation): array
    {
        $wordCount = str_word_count($translation);
        $sourceWordCount = str_word_count($this->passage->source_text);
        $ratio = $sourceWordCount > 0 ? $wordCount / $sourceWordCount : 0;

        $score = (int) round(min(1, $ratio) * 70); // chấm tạm dựa độ dài, tối đa 70 điểm khi chưa có AI thật

        return [
            'score' => $score,
            'feedback' => __('translate.simple_grade_notice'),
            'reference_translation' => null,
            'issues' => [],
        ];
    }

    public function render()
    {
        return view('livewire.translate.practice');
    }
}
