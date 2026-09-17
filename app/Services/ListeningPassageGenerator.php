<?php

namespace App\Services;

// Sinh bài nghe IELTS-style + câu hỏi nghe hiểu bằng Gemini (mục 8). Khác Đọc hiểu: sinh "transcript"
// (lời thoại) thay vì "content" - trình duyệt sẽ tự đọc transcript này thành giọng nói bằng Web Speech
// API (không cần audio file thật), nên prompt yêu cầu lời thoại phải đọc lên tự nhiên, không có ký hiệu
// khó đọc. Với 4 chủ đề hội thoại/độc thoại, mỗi chủ đề gắn cứng 1 cấp độ CEFR phù hợp độ khó tự nhiên.
class ListeningPassageGenerator
{
    // "general" đầu tiên (tổ chức theo cấp CEFR, giống Đọc hiểu) - 4 chủ đề còn lại đúng 4 dạng bài
    // IELTS Listening thật, độ khó tăng dần
    public const TOPICS = ['general', 'everyday_conversation', 'social_monologue', 'academic_discussion', 'academic_lecture'];

    // Cấp độ cố định cho các chủ đề không phải "general" - không cho chọn cấp, độ khó tăng dần theo dạng bài
    public const FIXED_LEVELS = [
        'everyday_conversation' => 'A2',
        'social_monologue' => 'B1',
        'academic_discussion' => 'B2',
        'academic_lecture' => 'C1',
    ];

    protected array $topicDescriptions = [
        'general' => 'a short, natural everyday audio clip suitable for the given CEFR level',
        'everyday_conversation' => 'a two-person everyday conversation between native English speakers. Format each line as "Speaker Name: line".',
        'social_monologue' => 'a single-speaker monologue giving information in a social context. No speaker labels needed - just the spoken text.',
        'academic_discussion' => 'a discussion between 2-4 people in an academic/training context. Format each line as "Speaker Name: line".',
        'academic_lecture' => 'a single-speaker academic lecture or talk on a subject. No speaker labels needed - just the spoken text.',
    ];

    // Gợi ý tình huống cụ thể theo từng chủ đề - CHỌN NGẪU NHIÊN Ở PHÍA CODE (không phải nhờ AI tự
    // "nghĩ ra ngẫu nhiên") trước khi gọi Gemini. Lý do bắt buộc phải làm vậy: gửi cùng 1 prompt chung
    // chung nhiều lần khiến Gemini có xu hướng trả lời rất giống nhau mỗi lần (thực tế đã gặp - sinh
    // liên tiếp 3 bài "academic_lecture" ra y hệt tiêu đề "The Evolution of Urban Green Spaces").
    protected array $scenarioSeeds = [
        'general' => [
            'a phone call between friends making weekend plans', 'a conversation about a recent short trip',
            'someone describing their daily routine', 'a chat about starting a new hobby',
            'a discussion about favorite foods and cooking', 'planning a birthday party for a family member',
            'talking about a movie or book someone just finished', 'discussing the weather and plans for today',
            'a conversation about taking care of a pet', 'talking about a school or work project',
            'a chat about moving to a new apartment', 'discussing plans for a family gathering',
        ],
        'everyday_conversation' => [
            'booking a hotel room over the phone', 'ordering food at a restaurant',
            'asking a stranger for directions to a museum', 'returning a faulty product at a shop',
            'making a doctor\'s appointment by phone', 'checking in at an airport counter',
            'renting a bicycle at a tourist spot', 'asking about a gym membership',
            'buying tickets for a concert', 'reporting a lost item at a train station',
            'negotiating the price at a market stall', 'asking a librarian for help finding a book',
        ],
        'social_monologue' => [
            'an airport boarding announcement', 'a museum guided tour introduction',
            'a voicemail message with pickup instructions', 'a weather forecast broadcast',
            'a store\'s opening hours announcement', 'safety instructions given on a train',
            'a library orientation talk for new members', 'a welcome speech at a hotel check-in desk',
            'instructions for a fire drill in an office building', 'a radio traffic update',
            'an announcement about a delayed flight', 'a tour guide introducing a historic building',
        ],
        'academic_discussion' => [
            'students planning a group research project', 'a debate about renewable energy policy',
            'a seminar discussing a business case study', 'a panel discussing the effects of social media',
            'students preparing for a field trip', 'a discussion about a recent scientific discovery',
            'a planning session for a university event', 'students discussing results of a class survey',
            'a debate on urban planning ideas', 'a group reviewing feedback on a class assignment',
            'a discussion about internship opportunities', 'students comparing study techniques before an exam',
        ],
        'academic_lecture' => [
            'the history of a well-known scientific discovery', 'an introduction to a psychology concept',
            'the economics behind a market trend', 'the biology of a specific ecosystem',
            'the history of an architectural style', 'the principles of a marketing strategy',
            'the development of a well-known technology', 'a notable chapter of world history',
            'the science behind a weather phenomenon', 'an overview of a philosophical idea',
            'the impact of a historical invention on daily life', 'an introduction to a branch of economics',
        ],
    ];

    public function __construct(protected GeminiService $gemini)
    {
    }

    /**
     * Sinh 1 bài nghe + câu hỏi cho $topic, độ khó $level.
     * $existingTitles: tiêu đề các bài đã có sẵn cùng topic/level trong kho - AI được yêu cầu tránh trùng.
     * Trả về ['title'=>, 'transcript'=>, 'questions'=>[['type','question','options','correct_answer'],...]] hoặc null nếu lỗi.
     */
    public function generate(string $topic, string $level, int $maxRetries = 3, ?\Closure $onRetry = null, array $existingTitles = []): ?array
    {
        $result = $this->gemini->generate($this->buildPrompt($topic, $level, $existingTitles), $this->schema(), $maxRetries, $onRetry);

        return $this->validate($result) ? $result : null;
    }

    protected function buildPrompt(string $topic, string $level, array $existingTitles = []): string
    {
        $description = $this->topicDescriptions[$topic] ?? $this->topicDescriptions['general'];
        $seeds = $this->scenarioSeeds[$topic] ?? $this->scenarioSeeds['general'];
        $scenario = $seeds[array_rand($seeds)];

        $avoidBlock = '';
        if (! empty($existingTitles)) {
            $list = implode(', ', array_map(fn ($t) => "\"{$t}\"", $existingTitles));
            $avoidBlock = "\n\nThese titles already exist in the question bank - your new clip's scenario and title must be clearly DIFFERENT from all of them: {$list}";
        }

        return <<<PROMPT
        You are an expert IELTS Listening test writer creating listening comprehension practice for
        Vietnamese learners of English.

        STEP 1 - Write a COMPLETE spoken-English transcript:
        - Content: {$description}
        - Specific scenario to base this clip on: {$scenario}. Invent specific, concrete details (names,
          places, numbers) around this scenario - do not write a generic or abstract version of it.
        - Difficulty roughly matching CEFR level {$level}
        - About 150-220 words, natural spoken style (contractions, natural pauses via punctuation) -
          this text will be read aloud by a text-to-speech engine, so avoid abbreviations, symbols, or
          formatting that would sound awkward when spoken (write numbers and times out in words where
          natural, e.g. "half past three" or "three thirty").
        - Give it a short, clear title (under 10 words).{$avoidBlock}

        STEP 2 - Write EXACTLY 5 listening comprehension questions based on the transcript, in these 5
        types (exactly one of each):
        1. "fill" - fill in the missing word/phrase. correct_answer MUST be an array with 1 element that
           is a word or short phrase appearing WORD-FOR-WORD in the transcript (so it can be matched
           exactly when grading).
        2. "boolean" - true/false based on information IN the transcript. correct_answer is an array
           with 1 element, exactly "True" or "False".
        3. "mcq" - 4-option multiple choice, only 1 correct answer. "options" has exactly 4 strings.
        4. "multi" - select-all-that-apply with 2 or more correct answers among 4-5 options.
        5. "fill" - another fill-in-the-blank question (same rules as #1, different content).

        STRICT REQUIREMENTS so every question can be graded automatically and unambiguously:
        - Every question must have exactly ONE unambiguous correct interpretation based on the transcript.
        - For "mcq" and "multi": every value in "correct_answer" must be an EXACT copy of one of the
          "options" strings (no paraphrasing).
        - Do NOT ask about opinions, inference far beyond the text, or information not in the transcript.
        - For "boolean"/"fill", "options" is an empty array [].

        Return ONLY 1 JSON object matching the given schema, no extra text.
        PROMPT;
    }

    protected function schema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'title' => ['type' => 'STRING'],
                'transcript' => ['type' => 'STRING'],
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
            'required' => ['title', 'transcript', 'questions'],
        ];
    }

    protected function validate(mixed $result): bool
    {
        if (! is_array($result) || empty($result['title']) || empty($result['transcript']) || empty($result['questions'])) {
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
