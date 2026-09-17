<?php

namespace App\Services;

// Sinh bài đọc IELTS-style + câu hỏi đọc hiểu bằng Gemini (mục 6).
// Khác với Vocabulary/Translate (chỉ sinh text), ở đây phải sinh CẢ nội dung LẪN câu hỏi có đáp án
// chính xác, không mơ hồ - nên prompt được viết chặt chẽ hơn hẳn, sinh 1 bài/lần (không batch nhiều).
class ReadingPassageGenerator
{
    // 8 chủ đề đúng theo mục 6 của spec - "general" luôn hiển thị đầu tiên (mục lục cấp CEFR)
    public const TOPICS = ['general', 'business', 'environment', 'health', 'history', 'science', 'society', 'technology'];

    protected array $topicLabels = [
        'business' => 'kinh doanh',
        'environment' => 'môi trường',
        'general' => 'chủ đề tổng quát, đời sống hàng ngày',
        'health' => 'sức khoẻ',
        'history' => 'lịch sử',
        'science' => 'khoa học',
        'society' => 'xã hội',
        'technology' => 'công nghệ',
    ];

    public function __construct(protected GeminiService $gemini)
    {
    }

    /**
     * Sinh 1 bài đọc + câu hỏi cho $topic, độ khó $level.
     * Trả về mảng ['title'=>, 'content'=>, 'questions'=>[['type','question','options','correct_answer'],...]] hoặc null nếu lỗi.
     */
    public function generate(string $topic, string $level, int $maxRetries = 3, ?\Closure $onRetry = null): ?array
    {
        $result = $this->gemini->generate($this->buildPrompt($topic, $level), $this->schema(), $maxRetries, $onRetry);

        return $this->validate($result) ? $result : null;
    }

    protected function buildPrompt(string $topic, string $level): string
    {
        $topicLabel = $this->topicLabels[$topic] ?? $topic;

        return <<<PROMPT
        Bạn là chuyên gia biên soạn đề đọc hiểu tiếng Anh kiểu IELTS Reading, dùng để luyện thi cho người Việt học tiếng Anh.

        BƯỚC 1 - Viết 1 bài đọc tiếng Anh HOÀN CHỈNH:
        - Chủ đề: {$topicLabel}
        - Độ khó tương đương cấp CEFR {$level}
        - Dài khoảng 150-220 từ, văn phong tự nhiên, thông tin cụ thể, rõ ràng (không mơ hồ, không ẩn dụ khó hiểu)
        - Có tiêu đề ngắn gọn, hấp dẫn (dưới 10 từ)

        BƯỚC 2 - Viết CHÍNH XÁC 5 câu hỏi đọc hiểu dựa trên bài vừa viết, theo đúng 5 loại sau (mỗi loại đúng 1 câu):
        1. "fill" - điền từ/cụm từ còn thiếu. Đáp án BẮT BUỘC là 1 từ hoặc cụm từ NGUYÊN VĂN xuất hiện
           trong bài đọc (để chấm so khớp chính xác được).
        2. "boolean" - câu đúng/sai dựa trên thông tin CÓ trong bài. correct_answer là mảng 1 phần tử,
           giá trị CHÍNH XÁC là "True" hoặc "False".
        3. "mcq" - trắc nghiệm 4 lựa chọn, chỉ 1 đáp án đúng. Mảng "options" có đúng 4 chuỗi.
        4. "multi" - chọn nhiều đáp án đúng trong 4-5 lựa chọn (có từ 2 đáp án đúng trở lên).
        5. "fill" - thêm 1 câu điền từ khác (dạng giống câu 1, nội dung khác).

        YÊU CẦU BẮT BUỘC để câu hỏi không mơ hồ và chấm được tự động:
        - Mỗi câu hỏi chỉ có ĐÚNG 1 cách hiểu và 1 đáp án đúng rõ ràng dựa trên bài đọc, không gây tranh cãi
        - Với "mcq" và "multi": mọi giá trị trong "correct_answer" phải TRÙNG KHỚP NGUYÊN VĂN (copy chính xác,
          không viết lại/diễn giải khác đi) với 1 phần tử trong mảng "options" tương ứng
        - KHÔNG hỏi về ý kiến cá nhân, suy luận xa, hay thông tin không có trong bài
        - Với "boolean"/"fill", trường "options" để mảng rỗng []

        Trả về CHÍNH XÁC 1 object JSON theo đúng schema đã cho, không thêm chữ nào khác ngoài object JSON.
        PROMPT;
    }

    protected function schema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'title' => ['type' => 'STRING'],
                'content' => ['type' => 'STRING'],
                'questions' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'type' => ['type' => 'STRING'],
                            'question' => ['type' => 'STRING'],
                            'options' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
                            'correct_answer' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
                        ],
                        'required' => ['type', 'question', 'options', 'correct_answer'],
                    ],
                ],
            ],
            'required' => ['title', 'content', 'questions'],
        ];
    }

    // Kiểm tra output tối thiểu hợp lệ trước khi lưu DB - tránh rác nếu AI trả sai định dạng
    protected function validate(mixed $result): bool
    {
        if (! is_array($result) || empty($result['title']) || empty($result['content']) || empty($result['questions'])) {
            return false;
        }

        foreach ($result['questions'] as $q) {
            if (empty($q['type']) || empty($q['question']) || empty($q['correct_answer'])) {
                return false;
            }
            if (! in_array($q['type'], ['fill', 'boolean', 'mcq', 'multi'])) {
                return false;
            }
            if (in_array($q['type'], ['mcq', 'multi']) && empty($q['options'])) {
                return false;
            }
        }

        return true;
    }
}
