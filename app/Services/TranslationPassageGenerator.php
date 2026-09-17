<?php

namespace App\Services;

// Sinh đoạn văn luyện dịch bằng Gemini - dùng chung cho lệnh artisan (sinh hàng loạt)
// và route bù kho ngầm (mỗi lần user lấy 1 đoạn ra làm thì sinh 1 đoạn mới bù vào, xem TranslateController::replenish)
class TranslationPassageGenerator
{
    protected array $levelDescriptions = [
        'A1' => 'người mới bắt đầu, câu đơn giản, thì hiện tại đơn, từ vựng cơ bản (gia đình, trường học, đồ vật hàng ngày)',
        'A2' => 'sơ cấp, câu ghép đơn giản, thì quá khứ/tương lai đơn, chủ đề đời sống hàng ngày (mua sắm, du lịch ngắn, sở thích)',
        'B1' => 'trung cấp, nhiều mệnh đề phụ, thể hiện quan điểm cá nhân, chủ đề quen thuộc (văn hoá, công nghệ, môi trường, sức khoẻ)',
        'B2' => 'trung cao cấp, lập luận rõ ràng, từ vựng trừu tượng vừa phải, chủ đề xã hội (giáo dục, mạng xã hội, công việc, đô thị hoá)',
        'C1' => 'cao cấp, lập luận phức tạp, từ vựng học thuật/trừu tượng, chủ đề chuyên sâu (kinh tế, chính sách, công nghệ AI, toàn cầu hoá)',
    ];

    public function __construct(protected GeminiService $gemini)
    {
    }

    /**
     * Sinh $count đoạn văn mới cho $level + $direction. Trả về mảng chuỗi (rỗng nếu lỗi).
     */
    public function generate(string $level, string $direction, int $count = 1, int $maxRetries = 3, ?\Closure $onRetry = null): array
    {
        $prompt = $this->buildPrompt($level, $direction, $count);

        return $this->gemini->generateStringArray($prompt, $maxRetries, $onRetry) ?? [];
    }

    protected function buildPrompt(string $level, string $direction, int $count): string
    {
        $desc = $this->levelDescriptions[$level] ?? '';
        $lang = $direction === 'en_vi' ? 'tiếng Anh' : 'tiếng Việt';

        return <<<PROMPT
        Viết {$count} đoạn văn {$lang} HOÀN TOÀN KHÁC NHAU (chủ đề khác nhau, không trùng lặp ý), mỗi đoạn
        khoảng 100-160 từ, liền mạch, tự nhiên như văn viết thật (không phải danh sách từ), phù hợp để
        người học dùng luyện dịch sang ngôn ngữ khác.

        Độ khó: cấp độ CEFR {$level} - {$desc}.

        Trả về CHÍNH XÁC 1 mảng JSON gồm {$count} chuỗi, mỗi chuỗi là 1 đoạn văn hoàn chỉnh,
        không thêm tiêu đề, không đánh số, không thêm chữ nào khác ngoài mảng JSON.
        PROMPT;
    }
}
