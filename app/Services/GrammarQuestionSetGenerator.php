<?php

namespace App\Services;

use App\Models\GrammarTopic;

// Sinh 1 bộ đề luyện tập ngữ pháp bằng Gemini (Phần B). Khác Đọc hiểu (sinh 1 bài/5 câu), ở đây sinh
// HẲN 35 câu/lần (trong khoảng 30-50 theo yêu cầu), và PHẢI bám sát đúng nội dung lý thuyết (content_en)
// của nhánh chủ đề tương ứng đã xây ở Phần A - đây chính là lý do Phần A phải làm trước Phần B.
class GrammarQuestionSetGenerator
{
    // Khớp đúng slug của 5 nhánh gốc trong grammar_topics (Phần A, mục A.2) - dùng chung 1 khoá để
    // Phần C (lộ trình chung) sau này nối thẳng theory <-> practice qua topic_key này.
    public const TOPICS = [
        'parts-of-speech' => 'Parts of Speech',
        'tenses' => 'Tenses',
        'sentence-structures' => 'Sentence Structures',
        'question-forms' => 'Question Forms',
        'common-structures' => 'Common Structures',
    ];

    public const QUESTION_COUNT = 35; // giữa khoảng 30-50 theo yêu cầu
    protected const FILL_COUNT = 14;
    protected const MCQ_COUNT = 14;
    protected const MULTI_COUNT = 7;

    public function __construct(protected GeminiService $gemini)
    {
    }

    /**
     * Sinh 1 bộ đề cho $topicKey. Trả về ['questions'=>[['type','question','options','correct_answer'],...]] hoặc null nếu lỗi.
     */
    public function generate(string $topicKey, int $maxRetries = 3, ?\Closure $onRetry = null): ?array
    {
        $grounding = $this->groundingContent($topicKey);
        if ($grounding === '') {
            return null;
        }

        $result = $this->gemini->generate($this->buildPrompt($topicKey, $grounding), $this->schema(), $maxRetries, $onRetry);

        if (! $this->validate($result)) {
            return null;
        }

        // Xáo trộn thứ tự để 3 loại câu hỏi xen kẽ nhau khi hiển thị, không bị dồn cục theo từng loại
        $questions = $result['questions'];
        shuffle($questions);
        $result['questions'] = $questions;

        return $result;
    }

    // Gom nội dung lý thuyết (content_en) của toàn bộ chủ đề con dưới 1 nhánh gốc, làm tài liệu gốc
    // cho AI bám sát đúng kiến thức đã dạy ở Phần A thay vì tự bịa ra điểm ngữ pháp ngoài chương trình.
    protected function groundingContent(string $topicKey): string
    {
        $topics = GrammarTopic::orderBy('order')->get(['id', 'parent_id', 'slug', 'title', 'content_en']);
        $root = $topics->firstWhere('slug', $topicKey);
        if (! $root) {
            return '';
        }

        $lines = [];
        $collect = function (int $parentId) use (&$collect, $topics, &$lines) {
            foreach ($topics->where('parent_id', $parentId) as $t) {
                if (filled($t->content_en)) {
                    $plain = trim(preg_replace('/\s+/', ' ', strip_tags(str_replace(['<p>', '<h3>'], ["\n", "\n"], $t->content_en))));
                    $lines[] = "### {$t->title}\n{$plain}";
                }
                $collect($t->id);
            }
        };
        $collect($root->id);

        return implode("\n\n", $lines);
    }

    protected function buildPrompt(string $topicKey, string $grounding): string
    {
        $topicLabel = self::TOPICS[$topicKey] ?? $topicKey;
        $total = self::QUESTION_COUNT;
        $fill = self::FILL_COUNT;
        $mcq = self::MCQ_COUNT;
        $multi = self::MULTI_COUNT;

        return <<<PROMPT
        You are an expert English grammar exam writer creating a practice quiz for Vietnamese learners of English (IELTS-style grammar practice).

        Below is the REFERENCE GRAMMAR MATERIAL for the topic "{$topicLabel}". Base EVERY question strictly on the rules and structures explained in this material - do not test any grammar point that is not covered here:

        ---
        {$grounding}
        ---

        Write EXACTLY {$total} grammar practice questions testing the material above, made up of exactly:
        - {$fill} questions of type "fill": a sentence with a blank ("___") to fill in with the correct word/phrase/verb form. "options" is an empty array []. "correct_answer" is an array with exactly 1 short accepted answer (1-4 words).
        - {$mcq} questions of type "mcq": a question or sentence with a blank, with exactly 4 options, only 1 correct answer. "options" has exactly 4 strings; "correct_answer" has exactly 1 string that must match one option exactly.
        - {$multi} questions of type "multi": a question with 4-5 options where 2 or more are correct (e.g. "select all that apply"). "options" has 4-5 strings; "correct_answer" has 2 or more strings, each matching an option exactly.

        STRICT REQUIREMENTS so every question can be graded automatically and unambiguously:
        - Every question must have exactly ONE unambiguous correct interpretation based on standard English grammar rules - no room for debate.
        - For "mcq" and "multi": every value in "correct_answer" must be an EXACT copy of one of the "options" strings (no paraphrasing, no extra punctuation).
        - Write NEW example sentences that test the same rules - do not copy the example sentences from the reference material verbatim.
        - Cover a good variety of the different sub-topics listed in the reference material, not just one or two of them repeatedly.
        - Do not number the questions yourself; the "question" field should contain only the sentence/instruction text.

        Return ONLY 1 JSON object matching the given schema, no extra text.
        PROMPT;
    }

    protected function schema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
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
            'required' => ['questions'],
        ];
    }

    // Kiểm tra output tối thiểu hợp lệ trước khi lưu DB - chấp nhận AI sinh hơi lệch số lượng
    // (miễn còn trong khoảng 30-50 theo yêu cầu) nhưng phải đúng cấu trúc từng câu.
    protected function validate(mixed $result): bool
    {
        if (! is_array($result) || empty($result['questions'])) {
            return false;
        }

        $count = count($result['questions']);
        if ($count < 25 || $count > 60) {
            return false;
        }

        foreach ($result['questions'] as $q) {
            if (empty($q['type']) || empty($q['question']) || empty($q['correct_answer'])) {
                return false;
            }
            if (! in_array($q['type'], ['fill', 'mcq', 'multi'])) {
                return false;
            }
            if (in_array($q['type'], ['mcq', 'multi']) && empty($q['options'])) {
                return false;
            }
        }

        return true;
    }
}
