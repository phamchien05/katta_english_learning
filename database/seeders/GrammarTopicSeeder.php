<?php

namespace Database\Seeders;

use App\Models\GrammarTopic;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

// Cây chủ đề ngữ pháp đầy đủ (Phần A - Xem Lý thuyết) - cấu trúc cây BẮT BUỘC đúng theo yêu cầu,
// nội dung bài học được viết sẵn cho từng chủ đề lá, CẢ 2 NGÔN NGỮ (mặc định hiển thị tiếng Anh theo
// đúng ngôn ngữ app đang chọn ở header, tiếng Việt chỉ hiện khi user tự bấm chuyển EN/VI). Nội dung
// tiếng Anh này cũng là tài liệu gốc để Phần B (luyện tập) dùng làm ngữ cảnh cho AI sinh đề.
class GrammarTopicSeeder extends Seeder
{
    // node(): helper dựng cây gọn - $children là mảng các node() con (rỗng = node lá)
    protected function node(string $title, array $children = []): array
    {
        return ['title' => $title, 'children' => $children];
    }

    public function run(): void
    {
        $tree = $this->tree();
        $content = $this->content();

        foreach ($tree as $order => $node) {
            $this->insert($node, null, $order, $content);
        }
    }

    protected function insert(array $node, ?int $parentId, int $order, array $content): void
    {
        $slug = $this->uniqueSlug($node['title']);
        $c = $content[$node['title']] ?? null;

        $topic = GrammarTopic::create([
            'parent_id' => $parentId,
            'title' => $node['title'],
            'slug' => $slug,
            'content_en' => $c['en'] ?? null,
            'content_vi' => $c['vi'] ?? null,
            'order' => $order,
        ]);

        foreach ($node['children'] as $i => $child) {
            $this->insert($child, $topic->id, $i, $content);
        }
    }

    protected function uniqueSlug(string $title): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $i = 2;
        while (GrammarTopic::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }

    // ========================= CÂY ĐIỀU HƯỚNG (mục A.2) =========================
    protected function tree(): array
    {
        return [
            $this->node('Parts of Speech', [
                $this->node('Nouns', [
                    $this->node('Common and Proper Nouns'),
                    $this->node('Concrete and Abstract Nouns'),
                    $this->node('Countable and Uncountable Nouns'),
                    $this->node('Compound Nouns'),
                ]),
                $this->node('Pronouns', [
                    $this->node('Personal Pronouns'),
                    $this->node('Possessive Pronouns'),
                    $this->node('Relative Pronouns'),
                    $this->node('Interrogative Pronouns'),
                    $this->node('Reflexive Pronouns'),
                    $this->node('Reciprocal Pronouns'),
                    $this->node('Demonstrative Pronouns and Adjectives'),
                    $this->node('Indefinite Pronouns'),
                ]),
                $this->node('Adjectives', [
                    $this->node('Possessive Adjectives'),
                    $this->node('-ing and -ed Adjectives'),
                    $this->node('Order of Adjectives'),
                ]),
                $this->node('Verbs', [
                    $this->node('Action Verbs'),
                    $this->node('Irregular Verbs'),
                    $this->node('Modal Verbs'),
                    $this->node("Verb 'to be'"),
                    $this->node('Auxiliary Verbs (Helping Verbs)'),
                    $this->node('Phrasal Verbs'),
                    $this->node('Transitive and Intransitive Verbs'),
                    $this->node('Linking Verbs'),
                    $this->node('Gerunds and Infinitives'),
                ]),
                $this->node('Adverbs', [
                    $this->node('Adverbs of Place'),
                    $this->node('Adverbs of Degree'),
                    $this->node('Adverbs of Time'),
                    $this->node('Adverbs of Manner'),
                    $this->node('Adverbs of Frequency'),
                ]),
                $this->node('Quantifiers & Determiners', [
                    $this->node('Few, a few, little, a little'),
                    $this->node('Some and Any'),
                    $this->node('Much and Many'),
                    $this->node('Other Determiners & Distributives'),
                ]),
                $this->node('Prepositions', [
                    $this->node('Prepositions of Place'),
                    $this->node('Prepositions of Time'),
                    $this->node('Other Prepositions'),
                ]),
                $this->node('Articles', [
                    $this->node('Definite and Indefinite Articles'),
                ]),
                $this->node('Conjunctions', [
                    $this->node('Coordinating Conjunctions'),
                    $this->node('Subordinating Conjunctions'),
                    $this->node('Correlative Conjunctions'),
                ]),
            ]),
            $this->node('Tenses', [
                $this->node('Present Tenses', [
                    $this->node('Present Simple'),
                    $this->node('Present Continuous'),
                    $this->node('Present Perfect'),
                    $this->node('Present Perfect Continuous'),
                ]),
                $this->node('Past Tenses', [
                    $this->node('Past Simple'),
                    $this->node('Past Continuous'),
                    $this->node('Past Perfect'),
                    $this->node('Past Perfect Continuous'),
                ]),
                $this->node('Future Tenses', [
                    $this->node('Future Simple (will)'),
                    $this->node('Be Going To'),
                    $this->node('Future Continuous'),
                    $this->node('Future Perfect'),
                    $this->node('Future Perfect Continuous'),
                ]),
                $this->node('Future in the Past'),
            ]),
            $this->node('Sentence Structures', [
                $this->node('Comparisons', [
                    $this->node('Comparison of Equality'),
                    $this->node('Comparative'),
                    $this->node('Superlative'),
                ]),
                $this->node('Conditional Sentences', [
                    $this->node('Type 0 Conditional'),
                    $this->node('Type 1 Conditional'),
                    $this->node('Type 2 Conditional'),
                    $this->node('Type 3 Conditional'),
                    $this->node('Mixed Conditional'),
                ]),
                $this->node('Wish Clauses'),
                $this->node('Passive Voice'),
                $this->node('Causative Verbs (have/get something done)'),
                $this->node('Inversion'),
                $this->node('Cleft Sentences'),
                $this->node('Subjunctive and Imperative Moods', [
                    $this->node('Subjunctive (Câu giả định)'),
                    $this->node('Imperative (Câu mệnh lệnh)'),
                ]),
                $this->node('Reported Speech'),
                $this->node('Clauses', [
                    $this->node('Relative Clauses'),
                    $this->node('Noun Clauses'),
                    $this->node('Participle Clauses'),
                ]),
            ]),
            $this->node('Question Forms', [
                $this->node('Wh-Questions'),
                $this->node('Yes/No Questions'),
                $this->node('Choice Questions'),
                $this->node('Tag Questions'),
                $this->node('Negative Questions'),
                $this->node('Indirect Questions'),
            ]),
            $this->node('Common Structures', [
                $this->node('Expressing Preferences, Wants & Suggestions', [
                    $this->node('Would you like...?'),
                    $this->node('Would rather'),
                    $this->node('Prefer'),
                    $this->node("Let / Let's"),
                    $this->node('Suggest'),
                    $this->node('Hope'),
                    $this->node('Advise'),
                    $this->node('Promise'),
                    $this->node('Ask'),
                    $this->node('Had better'),
                    $this->node('Need'),
                ]),
                $this->node('Describing Feelings, Attitudes & Behaviors', [
                    $this->node('Verbs followed by Gerund (V-ing)'),
                    $this->node('Refuse'),
                    $this->node('Regret'),
                    $this->node('Stop'),
                    $this->node('find it + adjective + to V'),
                ]),
                $this->node('Logical Connectors (Cause, Result, Contrast)', [
                    $this->node('Although / Despite / In spite of'),
                    $this->node('Because / Because of'),
                    $this->node('So / Such / Too'),
                    $this->node('Not only ... but also'),
                    $this->node('As well as'),
                    $this->node('It was not until ... that'),
                    $this->node('Purpose: So that / In order to / To V'),
                ]),
                $this->node('Time, Condition & Frequency', [
                    $this->node('When / While / After'),
                    $this->node('Used to / Be used to'),
                    $this->node('Remember'),
                    $this->node('Unless'),
                ]),
                $this->node('Other Common Patterns', [
                    $this->node('Enough'),
                    $this->node("Impersonal 'It' Structures"),
                    $this->node('There is / There are'),
                ]),
            ]),
        ];
    }

    // ========================= NỘI DUNG BÀI HỌC (mục A.3) =========================
    // key = title (khớp đúng title trong tree ở trên) -> ['en' => ..., 'vi' => ...]
    protected function content(): array
    {
        return array_merge(
            $this->contentPartsOfSpeech(),
            $this->contentTenses(),
            $this->contentSentenceStructures(),
            $this->contentQuestionForms(),
            $this->contentCommonStructures(),
        );
    }

    protected function contentPartsOfSpeech(): array
    {
        return [
            'Common and Proper Nouns' => [
                'en' => <<<HTML
                <p><strong>Definition:</strong> Nouns are words used to name people, things, or ideas. They can function as the subject or the object of a sentence.</p>
                <p><strong>Common nouns:</strong> Refer to general groups of people, things or phenomena. <em>e.g. a camera, a river, a city.</em></p>
                <p><strong>Proper nouns:</strong> Refer to the specific name of a person, place, or thing. Proper nouns are always capitalized. <em>e.g. Canon, Paris, John.</em></p>
                HTML,
                'vi' => <<<HTML
                <p><strong>Định nghĩa:</strong> Danh từ là những từ dùng để gọi tên người, vật hoặc ý tưởng. Chúng có thể đóng vai trò là chủ ngữ hoặc tân ngữ trong câu.</p>
                <p><strong>Danh từ chung:</strong> Chỉ các nhóm người, vật hoặc hiện tượng nói chung. <em>Ví dụ: a camera, a river, a city.</em></p>
                <p><strong>Danh từ riêng:</strong> Chỉ tên cụ thể của một người, địa điểm hoặc vật. Danh từ riêng luôn phải viết hoa. <em>Ví dụ: Canon, Paris, John.</em></p>
                HTML,
            ],

            'Concrete and Abstract Nouns' => [
                'en' => <<<HTML
                <p><strong>Concrete nouns:</strong> Refer to people, places or things that are tangible and can be perceived through the senses (sight, hearing, touch, taste, smell). <em>e.g. Tom gave me some apples.</em></p>
                <p><strong>Abstract nouns:</strong> Refer to concepts, ideas or phenomena that are intangible and cannot be perceived by the senses. <em>e.g. Jane's childhood memory was always her fear.</em></p>
                HTML,
                'vi' => <<<HTML
                <p><strong>Danh từ cụ thể:</strong> Chỉ người, địa điểm hoặc vật thể hữu hình và có thể cảm nhận được thông qua các giác quan (thị giác, thính giác, xúc giác, vị giác, khứu giác). <em>Ví dụ: Tom gave me some apples.</em></p>
                <p><strong>Danh từ trừu tượng:</strong> Chỉ các khái niệm, ý tưởng hoặc hiện tượng vô hình và không thể cảm nhận được bằng các giác quan. <em>Ví dụ: Jane's childhood memory was always her fear.</em></p>
                HTML,
            ],

            'Countable and Uncountable Nouns' => [
                'en' => <<<HTML
                <p><strong>Countable nouns:</strong> Refer to people, things... that can be counted and expressed with a specific number. They have a singular and a plural form. <em>e.g. a book (singular), two books (plural); a man, many men.</em></p>
                <p><strong>Uncountable nouns:</strong> Refer to things or concepts that cannot be counted with a specific number. They do not have a plural form. <em>e.g. water, food, information, sand, hope.</em></p>
                HTML,
                'vi' => <<<HTML
                <p><strong>Danh từ đếm được:</strong> Chỉ người, vật... có thể đếm được và diễn đạt bằng một số lượng cụ thể. Chúng có dạng số ít và số nhiều. <em>Ví dụ: a book (số ít), two books (số nhiều); a man, many men.</em></p>
                <p><strong>Danh từ không đếm được:</strong> Chỉ sự vật hoặc khái niệm không thể đếm được bằng số lượng cụ thể. Chúng không có dạng số nhiều. <em>Ví dụ: water, food, information, sand, hope.</em></p>
                HTML,
            ],

            'Compound Nouns' => [
                'en' => <<<HTML
                <p>Compound nouns are formed by combining two or more separate words into a new noun. They can be classified into three types:</p>
                <p><strong>Open compound nouns:</strong> The words stand separately. <em>e.g. bus stop, ice cream.</em></p>
                <p><strong>Hyphenated compound nouns:</strong> The words are joined with a hyphen. <em>e.g. mother-in-law, warm-up.</em></p>
                <p><strong>Closed compound nouns:</strong> The words are merged into one. <em>e.g. haircut, police officer.</em></p>
                HTML,
                'vi' => <<<HTML
                <p>Danh từ ghép được hình thành bằng cách kết hợp hai hoặc nhiều từ riêng biệt để tạo thành một danh từ mới. Chúng có thể được phân loại thành ba loại:</p>
                <p><strong>Danh từ ghép có khoảng cách:</strong> Các từ đứng riêng biệt. <em>Ví dụ: bus stop, ice cream.</em></p>
                <p><strong>Danh từ ghép có dấu gạch ngang:</strong> Các từ được nối với nhau bằng dấu gạch ngang. <em>Ví dụ: mother-in-law, warm-up.</em></p>
                <p><strong>Danh từ ghép liền mạch:</strong> Các từ được hợp nhất thành một. <em>Ví dụ: haircut, police officer.</em></p>
                HTML,
            ],

            'Personal Pronouns' => [
                'en' => <<<HTML
                <p>Personal pronouns replace nouns that refer to specific people or things to avoid repetition. They have two forms depending on their role in the sentence: <strong>subject pronouns</strong> (I, you, he, she, it, we, they) and <strong>object pronouns</strong> (me, you, him, her, it, us, them).</p>
                <p><em>e.g. My mother is a great doctor. She has performed many successful surgeries.</em></p>
                HTML,
                'vi' => <<<HTML
                <p>Đại từ nhân xưng thay thế danh từ chỉ người hoặc vật cụ thể để tránh lặp lại. Chúng có hai dạng dựa trên vai trò của chúng trong câu: <strong>đại từ chủ ngữ</strong> (I, you, he, she, it, we, they) và <strong>đại từ tân ngữ</strong> (me, you, him, her, it, us, them).</p>
                <p><em>Ví dụ: My mother is a great doctor. She has performed many successful surgeries.</em></p>
                HTML,
            ],

            'Possessive Pronouns' => [
                'en' => <<<HTML
                <p>Possessive pronouns show ownership and replace a noun phrase (e.g. 'my book' becomes 'mine').</p>
                <p><strong>mine:</strong> <em>Her skirt is black, mine is pink.</em></p>
                <p><strong>yours:</strong> <em>I have my pen. Where is yours?</em></p>
                <p><strong>ours:</strong> <em>That house is ours.</em></p>
                <p><strong>hers:</strong> <em>He got his certificate a year ago, but she just got hers.</em></p>
                <p><strong>his:</strong> <em>My car is white; his is blue.</em></p>
                <p><strong>theirs:</strong> <em>My notebook looks like theirs.</em></p>
                <p><strong>its:</strong> <em>The cat has a toy. The ball is its.</em></p>
                HTML,
                'vi' => <<<HTML
                <p>Đại từ sở hữu chỉ sự sở hữu và thay thế một cụm danh từ (ví dụ: 'my book' trở thành 'mine').</p>
                <p><strong>mine:</strong> <em>Her skirt is black, mine is pink.</em></p>
                <p><strong>yours:</strong> <em>I have my pen. Where is yours?</em></p>
                <p><strong>ours:</strong> <em>That house is ours.</em></p>
                <p><strong>hers:</strong> <em>He got his certificate a year ago, but she just got hers.</em></p>
                <p><strong>his:</strong> <em>My car is white; his is blue.</em></p>
                <p><strong>theirs:</strong> <em>My notebook looks like theirs.</em></p>
                <p><strong>its:</strong> <em>The cat has a toy. The ball is its.</em></p>
                HTML,
            ],

            'Relative Pronouns' => [
                'en' => <<<HTML
                <p>Relative pronouns connect a clause or phrase to a noun or pronoun. They introduce a relative clause, giving more information about the noun.</p>
                <p><strong>who:</strong> Refers to people. <em>The woman who called you is my boss.</em></p>
                <p><strong>whom:</strong> Refers to people (as an object). <em>The girl whom I met is my best friend.</em></p>
                <p><strong>which:</strong> Refers to things. <em>This is the comic book which he bought 3 years ago.</em></p>
                <p><strong>whose:</strong> Shows possession for people or things. <em>The woman whose name is Lona is my teacher.</em></p>
                <p><strong>that:</strong> Refers to people or things. <em>This is the book that belongs to Jane.</em></p>
                HTML,
                'vi' => <<<HTML
                <p>Đại từ quan hệ kết nối một mệnh đề hoặc cụm từ với một danh từ hoặc đại từ. Chúng giới thiệu một mệnh đề quan hệ, cung cấp thêm thông tin về danh từ.</p>
                <p><strong>who:</strong> Chỉ người. <em>The woman who called you is my boss.</em></p>
                <p><strong>whom:</strong> Chỉ người (như một tân ngữ). <em>The girl whom I met is my best friend.</em></p>
                <p><strong>which:</strong> Chỉ vật. <em>This is the comic book which he bought 3 years ago.</em></p>
                <p><strong>whose:</strong> Chỉ sự sở hữu của người hoặc vật. <em>The woman whose name is Lona is my teacher.</em></p>
                <p><strong>that:</strong> Chỉ người hoặc vật. <em>This is the book that belongs to Jane.</em></p>
                HTML,
            ],

            'Interrogative Pronouns' => [
                'en' => <<<HTML
                <p>Interrogative pronouns are used to ask questions. The answer is usually a noun or noun phrase. They usually appear at the beginning of the question.</p>
                <p><strong>What:</strong> <em>What did he do in Japan?</em></p>
                <p><strong>Which:</strong> <em>Which sport does she prefer, badminton or basketball?</em></p>
                <p><strong>Who:</strong> <em>Who is at the door?</em></p>
                <p><strong>Whom:</strong> <em>Whom did you invite to the party?</em></p>
                <p><strong>Whose:</strong> <em>Whose jacket is this?</em></p>
                HTML,
                'vi' => <<<HTML
                <p>Đại từ nghi vấn được dùng để đặt câu hỏi. Câu trả lời thường là một danh từ hoặc cụm danh từ. Chúng thường xuất hiện ở đầu câu hỏi.</p>
                <p><strong>What:</strong> <em>What did he do in Japan?</em></p>
                <p><strong>Which:</strong> <em>Which sport does she prefer, badminton or basketball?</em></p>
                <p><strong>Who:</strong> <em>Who is at the door?</em></p>
                <p><strong>Whom:</strong> <em>Whom did you invite to the party?</em></p>
                <p><strong>Whose:</strong> <em>Whose jacket is this?</em></p>
                HTML,
            ],

            'Reflexive Pronouns' => [
                'en' => <<<HTML
                <p>Reflexive pronouns are used when the subject and the object of the sentence are the same person or thing. They end in -self (singular) or -selves (plural): myself, yourself, himself, herself, itself, ourselves, yourselves, themselves.</p>
                <p><em>e.g. I cut myself while cooking.</em></p>
                <p><strong>Emphatic use:</strong> They can also be used to emphasize that someone did something alone, without help. <em>e.g. She painted the whole room herself.</em></p>
                HTML,
                'vi' => <<<HTML
                <p>Đại từ phản thân được sử dụng khi chủ ngữ và tân ngữ của câu là cùng một người hoặc cùng một vật. Chúng kết thúc bằng -self (số ít) hoặc -selves (số nhiều): myself, yourself, himself, herself, itself, ourselves, yourselves, themselves.</p>
                <p><em>Ví dụ: I cut myself while cooking.</em></p>
                <p><strong>Cách dùng nhấn mạnh:</strong> Chúng cũng có thể được dùng để nhấn mạnh rằng một người đã làm việc gì đó một mình, không cần sự giúp đỡ. <em>Ví dụ: She painted the whole room herself.</em></p>
                HTML,
            ],

            'Demonstrative Pronouns and Adjectives' => [
                'en' => <<<HTML
                <p>Demonstratives point to specific things and show whether they are near or far, singular or plural. The same four words (this, that, these, those) can work as a <strong>pronoun</strong> (standing alone, replacing a noun) or as an <strong>adjective</strong> (placed directly before a noun).</p>
                <p><strong>this / these:</strong> Near the speaker (this = singular, these = plural). <em>e.g. This is my bag. These shoes are new.</em></p>
                <p><strong>that / those:</strong> Far from the speaker (that = singular, those = plural). <em>e.g. That was a great movie. Those books belong to Jane.</em></p>
                HTML,
                'vi' => <<<HTML
                <p>Từ chỉ định dùng để trỏ vào một sự vật cụ thể, thể hiện sự vật đó ở gần hay xa, số ít hay số nhiều. Cùng 4 từ (this, that, these, those) có thể đóng vai trò <strong>đại từ</strong> (đứng một mình, thay cho danh từ) hoặc <strong>tính từ</strong> (đứng ngay trước danh từ).</p>
                <p><strong>this / these:</strong> Ở gần người nói (this = số ít, these = số nhiều). <em>Ví dụ: This is my bag. These shoes are new.</em></p>
                <p><strong>that / those:</strong> Ở xa người nói (that = số ít, those = số nhiều). <em>Ví dụ: That was a great movie. Those books belong to Jane.</em></p>
                HTML,
            ],

            'Indefinite Pronouns' => [
                'en' => <<<HTML
                <p>Indefinite pronouns refer to people, things, or amounts that are not specific or not named. Many are formed with some-/any-/no-/every- + -one/-body/-thing/-where.</p>
                <p><strong>some- (affirmative sentences):</strong> <em>Someone left this here. I need something to drink.</em></p>
                <p><strong>any- (questions and negatives):</strong> <em>Is anyone home? I don't have anything to say.</em></p>
                <p><strong>no- (negative meaning, singular verb):</strong> <em>Nobody knows the answer. Nothing happened.</em></p>
                <p><strong>every- (all members of a group, singular verb):</strong> <em>Everyone enjoyed the party. Everything is ready.</em></p>
                HTML,
                'vi' => <<<HTML
                <p>Đại từ bất định chỉ người, vật hoặc số lượng không xác định hoặc không nêu tên cụ thể. Nhiều từ được ghép từ some-/any-/no-/every- + -one/-body/-thing/-where.</p>
                <p><strong>some- (câu khẳng định):</strong> <em>Someone left this here. I need something to drink.</em></p>
                <p><strong>any- (câu hỏi và phủ định):</strong> <em>Is anyone home? I don't have anything to say.</em></p>
                <p><strong>no- (mang nghĩa phủ định, động từ số ít):</strong> <em>Nobody knows the answer. Nothing happened.</em></p>
                <p><strong>every- (toàn bộ thành viên của một nhóm, động từ số ít):</strong> <em>Everyone enjoyed the party. Everything is ready.</em></p>
                HTML,
            ],

            'Reciprocal Pronouns' => [
                'en' => <<<HTML
                <p>Reciprocal pronouns are used when two or more people perform the same action and both receive the benefit or consequence of that action at the same time.</p>
                <p><strong>each other:</strong> Usually used when talking about two people. <em>e.g. They looked at each other and smiled.</em></p>
                <p><strong>one another:</strong> Usually used when talking about more than two people. <em>e.g. The team members always support one another.</em></p>
                HTML,
                'vi' => <<<HTML
                <p>Đại từ tương hỗ được sử dụng khi hai hoặc nhiều người cùng thực hiện một hành động và cả hai đều nhận được lợi ích hoặc hậu quả của hành động đó cùng một lúc.</p>
                <p><strong>each other:</strong> Thường được sử dụng khi nói về hai người. <em>Ví dụ: They looked at each other and smiled.</em></p>
                <p><strong>one another:</strong> Thường được sử dụng khi nói về nhiều hơn hai người. <em>Ví dụ: The team members always support one another.</em></p>
                HTML,
            ],

            'Possessive Adjectives' => [
                'en' => <<<HTML
                <p>Possessive adjectives are used to show ownership. They come before a noun. They are different from possessive pronouns: my, your, our, their, his, her, its.</p>
                <p><em>e.g. That is her book. (The book belongs to her.)</em></p>
                HTML,
                'vi' => <<<HTML
                <p>Tính từ sở hữu được dùng để thể hiện quyền sở hữu. Chúng đứng trước danh từ. Chúng khác với đại từ sở hữu: my, your, our, their, his, her, its.</p>
                <p><em>Ví dụ: That is her book. (Cuốn sách thuộc về cô ấy).</em></p>
                HTML,
            ],

            '-ing and -ed Adjectives' => [
                'en' => <<<HTML
                <p>These adjectives are formed from verbs but describe nouns.</p>
                <p><strong>-ing adjectives:</strong> Describe the quality or characteristic of a person, thing, or situation that causes a feeling. They describe the source. <em>e.g. This old game is boring. (The game causes boredom.)</em></p>
                <p><strong>-ed adjectives:</strong> Describe a person's feeling caused by something. They describe the effect. <em>e.g. I feel bored because this game is boring. (I feel the boredom.)</em></p>
                HTML,
                'vi' => <<<HTML
                <p>Những tính từ này được hình thành từ động từ nhưng lại mô tả danh từ.</p>
                <p><strong>Tính từ kết thúc bằng -ing:</strong> Mô tả phẩm chất hoặc đặc điểm của người, vật hoặc tình huống gây ra cảm xúc. Chúng mô tả nguồn gốc. <em>Ví dụ: This old game is boring. (Trò chơi gây ra sự nhàm chán.)</em></p>
                <p><strong>Tính từ kết thúc bằng -ed:</strong> Mô tả cảm xúc của một người do một điều gì đó gây ra. Chúng mô tả tác động. <em>Ví dụ: I feel bored because this game is boring. (Tôi cảm thấy nhàm chán.)</em></p>
                HTML,
            ],

            'Order of Adjectives' => [
                'en' => <<<HTML
                <p>When several adjectives are used before a noun, they usually follow a specific order in English. This makes the sentence sound natural. The typical order is:</p>
                <p><strong>Quantity</strong> (two, several) → <strong>Opinion</strong> (beautiful, boring) → <strong>Size</strong> (big, small) → <strong>Age</strong> (young, old) → <strong>Shape</strong> (round, square) → <strong>Colour</strong> (red, blue) → <strong>Origin</strong> (Japanese, American) → <strong>Material</strong> (wooden, metal) → <strong>Purpose</strong> (sleeping bag)</p>
                <p><em>e.g. A beautiful old round red Italian wooden jewelry box.</em></p>
                <p>Note: Native speakers sometimes don't strictly follow this rule, but this is the standard order.</p>
                HTML,
                'vi' => <<<HTML
                <p>Khi nhiều tính từ được sử dụng trước một danh từ, chúng thường tuân theo một thứ tự cụ thể trong tiếng Anh. Điều này giúp câu văn nghe tự nhiên hơn. Thứ tự điển hình là:</p>
                <p><strong>Số lượng</strong> (two, several) → <strong>Ý kiến</strong> (beautiful, boring) → <strong>Kích thước</strong> (big, small) → <strong>Tuổi</strong> (young, old) → <strong>Hình dạng</strong> (round, square) → <strong>Màu sắc</strong> (red, blue) → <strong>Nguồn gốc</strong> (Japanese, American) → <strong>Chất liệu</strong> (wooden, metal) → <strong>Mục đích</strong> (sleeping bag)</p>
                <p><em>Ví dụ: A beautiful old round red Italian wooden jewelry box.</em></p>
                <p>Lưu ý: Người bản ngữ đôi khi không tuân theo quy tắc này, nhưng đây là thứ tự chuẩn.</p>
                HTML,
            ],

            'Action Verbs' => [
                'en' => <<<HTML
                <p>Action verbs express a physical or mental action. They describe what the subject of the sentence is doing.</p>
                <p><strong>Physical actions:</strong> run, jump, crash, cook.</p>
                <p><strong>Mental actions:</strong> think, remember, learn, believe.</p>
                HTML,
                'vi' => <<<HTML
                <p>Động từ chỉ hành động diễn tả một hành động thể chất hoặc tinh thần. Chúng mô tả những gì chủ ngữ của câu đang làm.</p>
                <p><strong>Hành động thể chất:</strong> run, jump, crash, cook.</p>
                <p><strong>Hành động tinh thần:</strong> think, remember, learn, believe.</p>
                HTML,
            ],

            'Irregular Verbs' => [
                'en' => <<<HTML
                <p>Irregular verbs do not follow the usual rule of adding '-ed' to form the past simple and past participle. You must memorize them.</p>
                <p><strong>begin → began → begun</strong></p>
                <p><strong>bring → brought → brought</strong></p>
                <p><strong>eat → ate → eaten</strong></p>
                HTML,
                'vi' => <<<HTML
                <p>Động từ bất quy tắc không tuân theo quy tắc thông thường là thêm '-ed' để tạo thành dạng quá khứ đơn và quá khứ phân từ. Bạn phải học thuộc lòng chúng.</p>
                <p><strong>begin → began → begun</strong></p>
                <p><strong>bring → brought → brought</strong></p>
                <p><strong>eat → ate → eaten</strong></p>
                HTML,
            ],

            'Modal Verbs' => [
                'en' => <<<HTML
                <p>Modal verbs are auxiliary verbs that express ability, possibility, permission, or obligation. The main verb that follows a modal verb is always in the base form.</p>
                <p><strong>Can / Could / Be able to</strong> (Ability): <em>Jane can play badminton.</em></p>
                <p><strong>Must / Have to</strong> (Obligation): <em>He must be tired.</em></p>
                <p><strong>May / Might</strong> (Possibility): <em>It might be cold.</em></p>
                <p><strong>Will / Would / Shall</strong> (Future/Intention): <em>John will win this match.</em></p>
                <p><strong>Should / Ought to</strong> (Advice): <em>You should lock the door.</em></p>
                HTML,
                'vi' => <<<HTML
                <p>Động từ khuyết thiếu là những động từ phụ trợ diễn tả khả năng, sự có thể xảy ra, sự cho phép hoặc nghĩa vụ. Động từ chính theo sau động từ khuyết thiếu luôn ở dạng nguyên mẫu.</p>
                <p><strong>Can / Could / Be able to</strong> (Khả năng): <em>Jane can play badminton.</em></p>
                <p><strong>Must / Have to</strong> (Nghĩa vụ): <em>He must be tired.</em></p>
                <p><strong>May / Might</strong> (Khả năng xảy ra): <em>It might be cold.</em></p>
                <p><strong>Will / Would / Shall</strong> (Tương lai/Ý định): <em>John will win this match.</em></p>
                <p><strong>Should / Ought to</strong> (Lời khuyên): <em>You should lock the door.</em></p>
                HTML,
            ],

            "Verb 'to be'" => [
                'en' => <<<HTML
                <p>The verb 'to be' (am, is, are, was, were) can act as a main verb to describe a state or characteristic, or as an auxiliary verb in continuous tenses and the passive voice.</p>
                <p><strong>As a main verb:</strong> <em>Jane is very beautiful.</em></p>
                <p><strong>As an auxiliary verb:</strong> <em>This beautiful house was built in 2000.</em></p>
                HTML,
                'vi' => <<<HTML
                <p>Động từ 'to be' (am, is, are, was, were) có thể đóng vai trò là động từ chính để miêu tả trạng thái hoặc đặc điểm, hoặc là động từ phụ trợ trong thì tiếp diễn và thể bị động.</p>
                <p><strong>Là động từ chính:</strong> <em>Jane is very beautiful.</em></p>
                <p><strong>Là động từ phụ trợ:</strong> <em>This beautiful house was built in 2000.</em></p>
                HTML,
            ],

            'Auxiliary Verbs (Helping Verbs)' => [
                'en' => <<<HTML
                <p>Auxiliary verbs (like 'be', 'do', 'have') help the main verb express tense, aspect, or voice. They are used to form questions, negatives, and complex tenses.</p>
                <p><strong>'do' for negatives/questions:</strong> <em>Henry does housework very well. He does not like it.</em></p>
                <p><strong>'have' for perfect tenses:</strong> <em>I have finished my work.</em></p>
                HTML,
                'vi' => <<<HTML
                <p>Các động từ phụ trợ (như 'be', 'do', 'have') giúp động từ chính thể hiện thì, thể hoặc giọng. Chúng được sử dụng để tạo câu hỏi, câu phủ định và các thì phức tạp.</p>
                <p><strong>'do' dùng cho câu phủ định/câu hỏi:</strong> <em>Henry does housework very well. He does not like it.</em></p>
                <p><strong>'have' dùng cho thì hoàn thành:</strong> <em>I have finished my work.</em></p>
                HTML,
            ],

            'Phrasal Verbs' => [
                'en' => <<<HTML
                <p>A phrasal verb is a combination of a verb and a preposition or adverb, creating a meaning different from the original verb.</p>
                <p><em>e.g. Don't bank on Henry doing that. ('Bank on' means 'to rely on'.)</em></p>
                HTML,
                'vi' => <<<HTML
                <p>Cụm động từ là sự kết hợp giữa một động từ và một giới từ hoặc trạng từ, tạo ra một nghĩa khác với động từ gốc.</p>
                <p><em>Ví dụ: Don't bank on Henry doing that. ('Bank on' có nghĩa là 'dựa vào').</em></p>
                HTML,
            ],

            'Transitive and Intransitive Verbs' => [
                'en' => <<<HTML
                <p><strong>Intransitive verbs:</strong> Express an action without a direct object. The action is complete on its own. <em>e.g. She agreed. The sun rises.</em></p>
                <p><strong>Transitive verbs:</strong> Express an action that has a direct object. The verb 'transfers' its action onto something or someone. <em>e.g. He owes Jane a lot of money. (The object is 'Jane'.)</em></p>
                HTML,
                'vi' => <<<HTML
                <p><strong>Động từ nội động từ:</strong> Biểu thị một hành động không có tân ngữ trực tiếp. Hành động đó đã hoàn thành. <em>Ví dụ: She agreed. The sun rises.</em></p>
                <p><strong>Động từ ngoại động từ:</strong> Biểu thị một hành động có tân ngữ trực tiếp. Động từ 'chuyển' hành động của nó cho một cái gì đó hoặc một người nào đó. <em>Ví dụ: He owes Jane a lot of money. (Tân ngữ là 'Jane').</em></p>
                HTML,
            ],

            'Linking Verbs' => [
                'en' => <<<HTML
                <p>Linking verbs connect the subject of the sentence to a subject complement (a noun or adjective that describes the subject). They don't express action; they express a state of being.</p>
                <p><strong>Common linking verbs:</strong> be, feel, look, sound, smell, taste, seem, become, appear, remain.</p>
                <p><em>e.g. Jane seems happy. The biscuit tastes sweet.</em></p>
                HTML,
                'vi' => <<<HTML
                <p>Động từ nối liên kết chủ ngữ của câu với bổ ngữ chủ ngữ (một danh từ hoặc tính từ mô tả chủ ngữ). Chúng không thể hiện hành động; chúng thể hiện trạng thái tồn tại.</p>
                <p><strong>Các động từ nối thông dụng:</strong> be, feel, look, sound, smell, taste, seem, become, appear, remain.</p>
                <p><em>Ví dụ: Jane seems happy. The biscuit tastes sweet.</em></p>
                HTML,
            ],

            'Gerunds and Infinitives' => [
                'en' => <<<HTML
                <p>A gerund is the -ing form of a verb used as a noun (<em>swimming, reading</em>). An infinitive is 'to' + the base form of a verb (<em>to swim, to read</em>). Whether a verb is followed by a gerund, an infinitive, or either depends on the verb itself.</p>
                <p><strong>Verb + Gerund only:</strong> enjoy, avoid, finish, suggest, mind, admit, consider. <em>e.g. She enjoys dancing.</em></p>
                <p><strong>Verb + Infinitive only:</strong> want, decide, plan, promise, agree, hope, refuse. <em>e.g. He decided to leave early.</em></p>
                <p><strong>Verb + both, same meaning:</strong> like, love, hate, start, begin, continue. <em>e.g. I like swimming. / I like to swim.</em></p>
                <p><strong>Verb + both, different meaning:</strong> stop, remember, try, forget. <em>e.g. He stopped to smoke (paused to do it) vs He stopped smoking (quit the habit).</em></p>
                HTML,
                'vi' => <<<HTML
                <p>Danh động từ (gerund) là dạng V-ing của động từ được dùng như một danh từ (<em>swimming, reading</em>). Động từ nguyên mẫu có "to" (infinitive) là 'to' + động từ nguyên mẫu (<em>to swim, to read</em>). Việc một động từ theo sau là danh động từ, động từ nguyên mẫu, hay cả hai tùy thuộc vào chính động từ đó.</p>
                <p><strong>Chỉ + Danh động từ:</strong> enjoy, avoid, finish, suggest, mind, admit, consider. <em>Ví dụ: She enjoys dancing.</em></p>
                <p><strong>Chỉ + Động từ nguyên mẫu có "to":</strong> want, decide, plan, promise, agree, hope, refuse. <em>Ví dụ: He decided to leave early.</em></p>
                <p><strong>+ cả hai, nghĩa giống nhau:</strong> like, love, hate, start, begin, continue. <em>Ví dụ: I like swimming. / I like to swim.</em></p>
                <p><strong>+ cả hai, nghĩa khác nhau:</strong> stop, remember, try, forget. <em>Ví dụ: He stopped to smoke (dừng lại để hút thuốc) khác He stopped smoking (bỏ hẳn thói quen hút thuốc).</em></p>
                HTML,
            ],

            'Adverbs of Place' => [
                'en' => <<<HTML
                <p>Adverbs of place describe where an action happens. They answer the question "Where?".</p>
                <p><strong>Common examples:</strong> here, there, somewhere, inside, outside, downstairs, between.</p>
                <p><em>e.g. Jane is cooking downstairs.</em></p>
                HTML,
                'vi' => <<<HTML
                <p>Trạng từ chỉ nơi chốn mô tả địa điểm xảy ra hành động. Chúng trả lời câu hỏi "Ở đâu?".</p>
                <p><strong>Ví dụ phổ biến:</strong> here, there, somewhere, inside, outside, downstairs, between.</p>
                <p><em>Ví dụ: Jane is cooking downstairs.</em></p>
                HTML,
            ],

            'Adverbs of Degree' => [
                'en' => <<<HTML
                <p>Adverbs of degree describe the intensity or extent of an action, adjective, or another adverb. They answer the question "To what extent?".</p>
                <p><strong>Common examples:</strong> hardly, little, fully, very, extremely, quite.</p>
                <p><em>e.g. I hardly need to say that I am extremely happy.</em></p>
                HTML,
                'vi' => <<<HTML
                <p>Trạng từ chỉ mức độ mô tả cường độ hoặc phạm vi của một hành động, tính từ hoặc trạng từ khác. Chúng trả lời câu hỏi "Đến mức độ nào?".</p>
                <p><strong>Ví dụ phổ biến:</strong> hardly, little, fully, very, extremely, quite.</p>
                <p><em>Ví dụ: I hardly need to say that I am extremely happy.</em></p>
                HTML,
            ],

            'Adverbs of Time' => [
                'en' => <<<HTML
                <p>Adverbs of time describe when an action happens. They answer the question "When?".</p>
                <p><strong>Common examples:</strong> early, now, soon, finally, last year, yesterday, today.</p>
                <p><em>e.g. I went to Dubai with my family last year.</em></p>
                HTML,
                'vi' => <<<HTML
                <p>Trạng từ chỉ thời gian mô tả thời điểm xảy ra một hành động. Chúng trả lời câu hỏi "Khi nào?".</p>
                <p><strong>Ví dụ phổ biến:</strong> early, now, soon, finally, last year, yesterday, today.</p>
                <p><em>Ví dụ: I went to Dubai with my family last year.</em></p>
                HTML,
            ],

            'Adverbs of Manner' => [
                'en' => <<<HTML
                <p>Adverbs of manner describe how an action happens. They answer the question "How?". Many manner adverbs end in "-ly".</p>
                <p><strong>Common examples:</strong> noisily, unfortunately, carefully, slowly, happily.</p>
                <p><em>e.g. My sister is chewing the food noisily.</em></p>
                HTML,
                'vi' => <<<HTML
                <p>Trạng từ chỉ cách thức mô tả cách thức một hành động diễn ra. Chúng trả lời câu hỏi "Như thế nào?". Nhiều trạng từ kết thúc bằng "-ly".</p>
                <p><strong>Ví dụ phổ biến:</strong> noisily, unfortunately, carefully, slowly, happily.</p>
                <p><em>Ví dụ: My sister is chewing the food noisily.</em></p>
                HTML,
            ],

            'Adverbs of Frequency' => [
                'en' => <<<HTML
                <p>Adverbs of frequency describe how often an action happens. They answer the question "How often?".</p>
                <p><strong>Common examples:</strong> always, often, sometimes, rarely, never.</p>
                <p><em>e.g. Jane always comes to school on time.</em></p>
                HTML,
                'vi' => <<<HTML
                <p>Trạng từ chỉ tần suất mô tả mức độ thường xuyên xảy ra của một hành động. Chúng trả lời câu hỏi "Thường xuyên như thế nào?".</p>
                <p><strong>Ví dụ phổ biến:</strong> always, often, sometimes, rarely, never.</p>
                <p><em>Ví dụ: Jane always comes to school on time.</em></p>
                HTML,
            ],

            'Few, a few, little, a little' => [
                'en' => <<<HTML
                <p><strong>A few / Few:</strong> Used with plural countable nouns.</p>
                <p>- <strong>A few:</strong> A small number (positive meaning, 'some'). <em>I have a few friends.</em></p>
                <p>- <strong>Few:</strong> Not many (negative meaning, 'hardly any'). <em>Few people came to the party.</em></p>
                <p><strong>A little / Little:</strong> Used with uncountable nouns.</p>
                <p>- <strong>A little:</strong> A small amount (positive meaning). <em>I have a little money.</em></p>
                <p>- <strong>Little:</strong> Not much (negative meaning). <em>There is little hope.</em></p>
                HTML,
                'vi' => <<<HTML
                <p><strong>A few / Few:</strong> Dùng với danh từ đếm được số nhiều.</p>
                <p>- <strong>A few:</strong> Một số lượng nhỏ (khẳng định, 'một vài'). <em>I have a few friends.</em></p>
                <p>- <strong>Few:</strong> Không nhiều (phủ định, 'hầu như không có'). <em>Few people came to the party.</em></p>
                <p><strong>A little / Little:</strong> Dùng với danh từ không đếm được.</p>
                <p>- <strong>A little:</strong> Một lượng nhỏ (khẳng định). <em>I have a little money.</em></p>
                <p>- <strong>Little:</strong> Không nhiều (phủ định). <em>There is little hope.</em></p>
                HTML,
            ],

            'Some and Any' => [
                'en' => <<<HTML
                <p>Both are used with plural countable nouns and uncountable nouns.</p>
                <p><strong>Some:</strong> Used in affirmative sentences and in offers/requests. <em>Henry bought some pencils. Would you like some tea?</em></p>
                <p><strong>Any:</strong> Used in negative sentences and most questions. <em>There aren't any tomatoes in the kitchen. Do you have any questions?</em></p>
                HTML,
                'vi' => <<<HTML
                <p>Cả hai đều được sử dụng với danh từ đếm được số nhiều và danh từ không đếm được.</p>
                <p><strong>Some:</strong> Được sử dụng trong câu khẳng định và trong các lời đề nghị/yêu cầu. <em>Henry bought some pencils. Would you like some tea?</em></p>
                <p><strong>Any:</strong> Được sử dụng trong câu phủ định và hầu hết các câu hỏi. <em>There aren't any tomatoes in the kitchen. Do you have any questions?</em></p>
                HTML,
            ],

            'Much and Many' => [
                'en' => <<<HTML
                <p>Both mean 'a large quantity' and are usually used in questions and negative sentences. 'A lot of' is more common in affirmative sentences.</p>
                <p><strong>Many:</strong> Used with plural countable nouns. <em>Many of my friends like playing badminton.</em></p>
                <p><strong>Much:</strong> Used with uncountable nouns. <em>How much time does she have left?</em></p>
                HTML,
                'vi' => <<<HTML
                <p>Cả hai đều có nghĩa là 'số lượng lớn' và thường được sử dụng trong câu hỏi và câu phủ định. 'A lot of' phổ biến hơn trong câu khẳng định.</p>
                <p><strong>Many:</strong> Được sử dụng với danh từ đếm được số nhiều. <em>Many of my friends like playing badminton.</em></p>
                <p><strong>Much:</strong> Được sử dụng với danh từ không đếm được. <em>How much time does she have left?</em></p>
                HTML,
            ],

            'Other Determiners & Distributives' => [
                'en' => <<<HTML
                <p>These words specify how something is distributed, shared, or divided among the members of a group.</p>
                <p><strong>each / every:</strong> Used with singular countable nouns. <em>each</em> focuses on individual members (Each student received a personalized gift), <em>every</em> focuses on the group as a whole (Every room in the hotel has a balcony).</p>
                <p><strong>both / neither / either:</strong> Used only when talking about exactly two entities. <em>both</em> (includes A and B), <em>neither</em> (excludes both, singular verb), <em>either</em> (one of the two).</p>
                <p><strong>all / whole:</strong> Used to refer to the entirety of something. <em>All the children were happy. / All the milk was spilled.</em></p>
                HTML,
                'vi' => <<<HTML
                <p>Những từ này chỉ rõ cách thức phân phối, chia sẻ hoặc phân chia một thứ gì đó giữa các thành viên trong một nhóm.</p>
                <p><strong>each / every:</strong> Dùng với danh từ đếm được số ít. <em>each</em> tập trung vào từng thành viên riêng lẻ (Each student received a personalized gift), <em>every</em> tập trung vào toàn bộ nhóm (Every room in the hotel has a balcony).</p>
                <p><strong>both / neither / either:</strong> Chỉ dùng khi nói về chính xác hai thực thể. <em>both</em> (bao gồm A và B), <em>neither</em> (loại trừ cả hai, động từ số ít), <em>either</em> (một trong hai).</p>
                <p><strong>all / whole:</strong> Dùng để chỉ toàn bộ. <em>All the children were happy. / All the milk was spilled.</em></p>
                HTML,
            ],

            'Prepositions of Place' => [
                'en' => <<<HTML
                <p>These prepositions clarify information about a location or position.</p>
                <p><strong>Common examples:</strong> in, on, at, under, between, behind, in front of.</p>
                <p><em>e.g. Jane is in her room. The cat is on the table.</em></p>
                HTML,
                'vi' => <<<HTML
                <p>Các giới từ này làm rõ thông tin về vị trí hoặc địa điểm.</p>
                <p><strong>Ví dụ phổ biến:</strong> in, on, at, under, between, behind, in front of.</p>
                <p><em>Ví dụ: Jane is in her room. The cat is on the table.</em></p>
                HTML,
            ],

            'Prepositions of Time' => [
                'en' => <<<HTML
                <p>These prepositions clarify information about when an event happens.</p>
                <p><strong>On:</strong> Used for days and dates. <em>on Monday, on May 5th.</em></p>
                <p><strong>At:</strong> Used for specific times and certain points in the day. <em>at 9 pm, at noon.</em></p>
                <p><strong>In:</strong> Used for longer periods such as months, years, seasons. <em>in the evening, in 2023, in summer.</em></p>
                HTML,
                'vi' => <<<HTML
                <p>Các giới từ này làm rõ thông tin về thời điểm xảy ra một sự kiện.</p>
                <p><strong>On:</strong> Dùng cho ngày và tháng. <em>on Monday, on May 5th.</em></p>
                <p><strong>At:</strong> Dùng cho thời gian cụ thể và những thời điểm nhất định trong ngày. <em>at 9 pm, at noon.</em></p>
                <p><strong>In:</strong> Dùng cho khoảng thời gian dài hơn như tháng, năm, mùa. <em>in the evening, in 2023, in summer.</em></p>
                HTML,
            ],

            'Other Prepositions' => [
                'en' => <<<HTML
                <p><strong>Direction/Movement:</strong> to, from, up, down, across, along.</p>
                <p><strong>Agent/Instrument:</strong> by, with, on.</p>
                <p><strong>Reason/Purpose:</strong> because of, due to, from, through.</p>
                HTML,
                'vi' => <<<HTML
                <p><strong>Hướng/Chuyển động:</strong> to, from, up, down, across, along.</p>
                <p><strong>Tác nhân/Công cụ:</strong> by, with, on.</p>
                <p><strong>Lý do/Mục đích:</strong> because of, due to, from, through.</p>
                HTML,
            ],

            'Definite and Indefinite Articles' => [
                'en' => <<<HTML
                <p>Articles are words placed before a noun to specify whether the noun is general or specific.</p>
                <p><strong>The (Definite article):</strong> Used to refer to a specific, unique, or previously mentioned noun that both the speaker and listener know. <em>Can you pass me the salt?</em></p>
                <p><strong>A / An (Indefinite articles):</strong> Used to refer to a general or non-specific noun. 'A' comes before a consonant sound, 'An' before a vowel sound. <em>I need to buy a car. I saw an elephant.</em></p>
                HTML,
                'vi' => <<<HTML
                <p>Mạo từ là những từ đứng trước danh từ để xác định xem danh từ đó là chung chung hay cụ thể.</p>
                <p><strong>The (Mạo từ xác định):</strong> Dùng để chỉ một danh từ cụ thể, duy nhất hoặc đã được nhắc đến trước đó mà cả người nói và người nghe đều biết. <em>Can you pass me the salt?</em></p>
                <p><strong>A / An (Mạo từ bất định):</strong> Dùng để chỉ một danh từ chung chung hoặc không cụ thể. 'A' đứng trước phụ âm, 'An' đứng trước nguyên âm. <em>I need to buy a car. I saw an elephant.</em></p>
                HTML,
            ],

            'Coordinating Conjunctions' => [
                'en' => <<<HTML
                <p>Coordinating conjunctions join words, phrases, or independent clauses of equal grammatical rank. You can remember them with the acronym <strong>FANBOYS</strong>: For, And, Nor, But, Or, Yet, So.</p>
                <p><em>e.g. I want to play badminton and handball.</em></p>
                HTML,
                'vi' => <<<HTML
                <p>Liên từ phối hợp nối các từ, cụm từ hoặc mệnh đề độc lập có cùng cấp bậc ngữ pháp. Bạn có thể nhớ chúng bằng từ viết tắt <strong>FANBOYS</strong>: For, And, Nor, But, Or, Yet, So.</p>
                <p><em>Ví dụ: I want to play badminton and handball.</em></p>
                HTML,
            ],

            'Subordinating Conjunctions' => [
                'en' => <<<HTML
                <p>Subordinating conjunctions connect an independent clause (a complete idea) with a dependent clause (an incomplete idea). The dependent clause cannot stand alone as a sentence.</p>
                <p><strong>Common examples:</strong> because, since, as, although, before, after, while, if, unless.</p>
                <p><em>e.g. Although Henry had a broken leg, he still passed the final exam.</em></p>
                HTML,
                'vi' => <<<HTML
                <p>Liên từ phụ thuộc nối một mệnh đề độc lập (một ý hoàn chỉnh) với một mệnh đề phụ thuộc (một ý chưa hoàn chỉnh). Mệnh đề phụ thuộc không thể đứng độc lập như một câu.</p>
                <p><strong>Ví dụ phổ biến:</strong> because, since, as, although, before, after, while, if, unless.</p>
                <p><em>Ví dụ: Although Henry had a broken leg, he still passed the final exam.</em></p>
                HTML,
            ],

            'Correlative Conjunctions' => [
                'en' => <<<HTML
                <p>Correlative conjunctions work in pairs to connect equivalent grammatical elements in a sentence.</p>
                <p><strong>either ... or:</strong> <em>You can have tea or coffee.</em></p>
                <p><strong>neither ... nor:</strong> <em>He is neither a teacher nor a student.</em></p>
                <p><strong>both ... and:</strong> <em>She likes both cats and dogs.</em></p>
                <p><strong>not only ... but also:</strong> <em>We play badminton not only on Tuesdays but also on Sundays.</em></p>
                HTML,
                'vi' => <<<HTML
                <p>Các liên từ tương quan hoạt động theo cặp để nối các yếu tố ngữ pháp tương đương trong câu.</p>
                <p><strong>either ... or:</strong> <em>You can have tea or coffee.</em></p>
                <p><strong>neither ... nor:</strong> <em>He is neither a teacher nor a student.</em></p>
                <p><strong>both ... and:</strong> <em>She likes both cats and dogs.</em></p>
                <p><strong>not only ... but also:</strong> <em>We play badminton not only on Tuesdays but also on Sundays.</em></p>
                HTML,
            ],
        ];
    }

    protected function contentTenses(): array
    {
        return [
            'Present Simple' => [
                'en' => <<<HTML
                <h3>When to use</h3>
                <p><strong>Habits and schedules:</strong> Actions that happen regularly. <em>I drink coffee every morning.</em></p>
                <p><strong>Facts and general truths:</strong> Things that are always true. <em>The sun rises in the east.</em></p>
                <p><strong>Fixed timetables:</strong> Future events on a set schedule. <em>The train leaves at 8 pm.</em></p>
                <p><strong>States, feelings, opinions:</strong> Verbs describing a state, not an action. <em>He trusts you.</em></p>
                <h3>Structure</h3>
                <p><strong>(+)</strong> Subject + Verb (s/es) ... <em>She works at a hospital.</em></p>
                <p><strong>(-)</strong> Subject + do/does + not + Verb ... <em>She does not work at a hospital.</em></p>
                <p><strong>(?)</strong> Do/Does + Subject + Verb ...? <em>Does she work at a hospital?</em></p>
                <h3>Signal words</h3>
                <p>always, usually, often, sometimes, rarely, never, every day/week/month, on Mondays...</p>
                HTML,
                'vi' => <<<HTML
                <h3>Khi nào nên sử dụng</h3>
                <p><strong>Thói quen và lịch trình:</strong> Những hành động diễn ra thường xuyên. <em>I drink coffee every morning.</em></p>
                <p><strong>Sự thật và chân lý chung:</strong> Những điều luôn đúng. <em>The sun rises in the east.</em></p>
                <p><strong>Lịch trình cố định:</strong> Các sự kiện trong tương lai nằm trong một lịch trình. <em>The train leaves at 8 pm.</em></p>
                <p><strong>Trạng thái, cảm xúc, ý kiến:</strong> Động từ mô tả trạng thái, không phải hành động. <em>He trusts you.</em></p>
                <h3>Cấu trúc</h3>
                <p><strong>(+)</strong> Chủ ngữ + Động từ (s/es) ... <em>She works at a hospital.</em></p>
                <p><strong>(-)</strong> Chủ ngữ + do/does + not + Động từ ... <em>She does not work at a hospital.</em></p>
                <p><strong>(?)</strong> Do/Does + Chủ ngữ + Động từ ...? <em>Does she work at a hospital?</em></p>
                <h3>Từ tín hiệu</h3>
                <p>always, usually, often, sometimes, rarely, never, every day/week/month, on Mondays...</p>
                HTML,
            ],

            'Present Continuous' => [
                'en' => <<<HTML
                <h3>When to use</h3>
                <p><strong>Actions happening now:</strong> Activities in progress at the moment of speaking. <em>Please be quiet, I am studying.</em></p>
                <p><strong>Temporary situations:</strong> Actions happening around the present time. <em>He is living in London for a few months.</em></p>
                <p><strong>Near future plans:</strong> <em>We are meeting friends tonight.</em></p>
                <p><strong>Annoying habits (with 'always'):</strong> Complaining about something. <em>You are always losing your keys!</em></p>
                <h3>Structure</h3>
                <p><strong>(+)</strong> Subject + am/is/are + V-ing ... <em>They are watching TV.</em></p>
                <p><strong>(-)</strong> Subject + am/is/are + not + V-ing ... <em>They are not watching TV.</em></p>
                <p><strong>(?)</strong> Am/Is/Are + Subject + V-ing ...? <em>Are they watching TV?</em></p>
                <h3>Signal words</h3>
                <p>now, right now, at the moment, currently, still, Look!, Listen!</p>
                HTML,
                'vi' => <<<HTML
                <h3>Khi nào nên sử dụng</h3>
                <p><strong>Hành động đang diễn ra:</strong> Các hoạt động đang được thực hiện ngay tại thời điểm nói. <em>Please be quiet, I am studying.</em></p>
                <p><strong>Tình huống tạm thời:</strong> Các hành động đang xảy ra xung quanh thời điểm hiện tại. <em>He is living in London for a few months.</em></p>
                <p><strong>Kế hoạch tương lai gần:</strong> <em>We are meeting friends tonight.</em></p>
                <p><strong>Thói quen khó chịu (với 'always'):</strong> Phàn nàn về điều gì đó. <em>You are always losing your keys!</em></p>
                <h3>Cấu trúc</h3>
                <p><strong>(+)</strong> Chủ ngữ + am/is/are + V-ing ... <em>They are watching TV.</em></p>
                <p><strong>(-)</strong> Chủ ngữ + am/is/are + not + V-ing ... <em>They are not watching TV.</em></p>
                <p><strong>(?)</strong> Am/Is/Are + Chủ ngữ + V-ing ...? <em>Are they watching TV?</em></p>
                <h3>Từ tín hiệu</h3>
                <p>now, right now, at the moment, currently, still, Look!, Listen!</p>
                HTML,
            ],

            'Present Perfect' => [
                'en' => <<<HTML
                <h3>When to use</h3>
                <p><strong>Life experiences:</strong> Actions that happened at an unspecified time in the past. <em>I have been to Japan.</em></p>
                <p><strong>Recent action with a present result:</strong> <em>I have lost my wallet. I can't find it now.</em></p>
                <p><strong>Action that started in the past and continues to the present:</strong> Used with 'for' and 'since'. <em>She has lived here for three years.</em></p>
                <h3>Structure</h3>
                <p><strong>(+)</strong> Subject + have/has + V3 ... <em>He has finished his homework.</em></p>
                <p><strong>(-)</strong> Subject + have/has + not + V3 ... <em>He has not finished his homework yet.</em></p>
                <p><strong>(?)</strong> Have/Has + Subject + V3 ...? <em>Has he finished his homework?</em></p>
                <h3>Signal words</h3>
                <p>since, for, just, already, yet, ever, never, recently, lately.</p>
                HTML,
                'vi' => <<<HTML
                <h3>Khi nào nên sử dụng</h3>
                <p><strong>Kinh nghiệm sống:</strong> Những hành động đã xảy ra vào một thời điểm không xác định trong quá khứ. <em>I have been to Japan.</em></p>
                <p><strong>Hành động gần đây với kết quả hiện tại:</strong> <em>I have lost my wallet. I can't find it now.</em></p>
                <p><strong>Hành động bắt đầu trong quá khứ, tiếp diễn đến hiện tại:</strong> Dùng với 'for' và 'since'. <em>She has lived here for three years.</em></p>
                <h3>Cấu trúc</h3>
                <p><strong>(+)</strong> Chủ ngữ + have/has + V3 ... <em>He has finished his homework.</em></p>
                <p><strong>(-)</strong> Chủ ngữ + have/has + not + V3 ... <em>He has not finished his homework yet.</em></p>
                <p><strong>(?)</strong> Have/Has + Chủ ngữ + V3 ...? <em>Has he finished his homework?</em></p>
                <h3>Từ tín hiệu</h3>
                <p>since, for, just, already, yet, ever, never, recently, lately.</p>
                HTML,
            ],

            'Present Perfect Continuous' => [
                'en' => <<<HTML
                <h3>When to use</h3>
                <p><strong>Emphasizing duration:</strong> Used for actions that started in the past and are still continuing. <em>I have been waiting for you for two hours!</em></p>
                <p><strong>Recent continuous action:</strong> Used for actions that just finished and have a clear present result. <em>Why are you tired? - I have been running.</em></p>
                <h3>Structure</h3>
                <p><strong>(+)</strong> Subject + have/has + been + V-ing ... <em>She has been reading all day.</em></p>
                <p><strong>(-)</strong> Subject + have/has + not + been + V-ing ... <em>She has not been reading all day.</em></p>
                <p><strong>(?)</strong> Have/Has + Subject + been + V-ing ...? <em>Has she been reading all day?</em></p>
                <h3>Signal words</h3>
                <p>since, all day/morning, all week.</p>
                HTML,
                'vi' => <<<HTML
                <h3>Khi nào nên sử dụng</h3>
                <p><strong>Nhấn mạnh thời gian:</strong> Dùng cho các hành động đã bắt đầu trong quá khứ và vẫn đang tiếp diễn. <em>I have been waiting for you for two hours!</em></p>
                <p><strong>Hành động liên tục gần đây:</strong> Dùng cho các hành động vừa mới kết thúc và có kết quả rõ ràng ở hiện tại. <em>Why are you tired? - I have been running.</em></p>
                <h3>Cấu trúc</h3>
                <p><strong>(+)</strong> Chủ ngữ + have/has + been + V-ing ... <em>She has been reading all day.</em></p>
                <p><strong>(-)</strong> Chủ ngữ + have/has + not + been + V-ing ... <em>She has not been reading all day.</em></p>
                <p><strong>(?)</strong> Have/Has + Chủ ngữ + been + V-ing ...? <em>Has she been reading all day?</em></p>
                <h3>Từ tín hiệu</h3>
                <p>since, all day/morning, all week.</p>
                HTML,
            ],

            'Past Simple' => [
                'en' => <<<HTML
                <h3>When to use</h3>
                <p><strong>Completed action in the past:</strong> Actions that started and finished at a specific time in the past. <em>We visited Paris last year.</em></p>
                <p><strong>Sequence of past actions:</strong> To describe events in a story. <em>He came in, took off his coat and sat down.</em></p>
                <h3>Structure</h3>
                <p><strong>(+)</strong> Subject + V2 (past simple) ... <em>They watched a movie yesterday.</em></p>
                <p><strong>(-)</strong> Subject + did + not + V (base form) ... <em>They did not watch a movie yesterday.</em></p>
                <p><strong>(?)</strong> Did + Subject + V (base form) ...? <em>Did they watch a movie yesterday?</em></p>
                <h3>Signal words</h3>
                <p>yesterday, last night/week/year, ago, in 1999, the other day.</p>
                HTML,
                'vi' => <<<HTML
                <h3>Khi nào nên sử dụng</h3>
                <p><strong>Hành động đã hoàn thành trong quá khứ:</strong> Những hành động bắt đầu và kết thúc vào một thời điểm cụ thể trong quá khứ. <em>We visited Paris last year.</em></p>
                <p><strong>Chuỗi hành động trong quá khứ:</strong> Để mô tả các sự kiện trong một câu chuyện. <em>He came in, took off his coat and sat down.</em></p>
                <h3>Cấu trúc</h3>
                <p><strong>(+)</strong> Chủ ngữ + V2 (quá khứ đơn) ... <em>They watched a movie yesterday.</em></p>
                <p><strong>(-)</strong> Chủ ngữ + did + not + V (nguyên mẫu) ... <em>They did not watch a movie yesterday.</em></p>
                <p><strong>(?)</strong> Did + Chủ ngữ + V (nguyên mẫu) ...? <em>Did they watch a movie yesterday?</em></p>
                <h3>Từ tín hiệu</h3>
                <p>yesterday, last night/week/year, ago, in 1999, the other day.</p>
                HTML,
            ],

            'Past Continuous' => [
                'en' => <<<HTML
                <h3>When to use</h3>
                <p><strong>Action in progress in the past:</strong> <em>At 8 pm last night, I was studying.</em></p>
                <p><strong>Interrupted action:</strong> A longer action interrupted by a shorter one. <em>He was watching TV when the phone rang.</em></p>
                <p><strong>Two parallel actions:</strong> <em>While I was cooking, my husband was washing the dishes.</em></p>
                <h3>Structure</h3>
                <p><strong>(+)</strong> Subject + was/were + V-ing ... <em>It was raining.</em></p>
                <p><strong>(-)</strong> Subject + was/were + not + V-ing ... <em>It was not raining.</em></p>
                <p><strong>(?)</strong> Was/Were + Subject + V-ing ...? <em>Was it raining?</em></p>
                <h3>Signal words</h3>
                <p>while, when, as, at 8 o'clock last night.</p>
                HTML,
                'vi' => <<<HTML
                <h3>Khi nào nên sử dụng</h3>
                <p><strong>Hành động đang diễn ra trong quá khứ:</strong> <em>At 8 pm last night, I was studying.</em></p>
                <p><strong>Hành động bị gián đoạn:</strong> Một hành động dài bị gián đoạn bởi một hành động ngắn hơn. <em>He was watching TV when the phone rang.</em></p>
                <p><strong>Hai hành động song song:</strong> <em>While I was cooking, my husband was washing the dishes.</em></p>
                <h3>Cấu trúc</h3>
                <p><strong>(+)</strong> Chủ ngữ + was/were + V-ing ... <em>It was raining.</em></p>
                <p><strong>(-)</strong> Chủ ngữ + was/were + not + V-ing ... <em>It was not raining.</em></p>
                <p><strong>(?)</strong> Was/Were + Chủ ngữ + V-ing ...? <em>Was it raining?</em></p>
                <h3>Từ tín hiệu</h3>
                <p>while, when, as, at 8 o'clock last night.</p>
                HTML,
            ],

            'Past Perfect' => [
                'en' => <<<HTML
                <h3>When to use</h3>
                <p><strong>The "earlier" past tense:</strong> Describes an action that happened before another action or point in the past. <em>The train had left the station when we arrived.</em></p>
                <h3>Structure</h3>
                <p><strong>(+)</strong> Subject + had + V3 ... <em>She had finished her work before she went home.</em></p>
                <p><strong>(-)</strong> Subject + had + not + V3 ... <em>She had not finished her work.</em></p>
                <p><strong>(?)</strong> Had + Subject + V3 ...? <em>Had she finished her work?</em></p>
                <h3>Signal words</h3>
                <p>before, after, already, just, when, by that time.</p>
                HTML,
                'vi' => <<<HTML
                <h3>Khi nào nên sử dụng</h3>
                <p><strong>Thì quá khứ "trước đó":</strong> Mô tả một hành động xảy ra trước một hành động hoặc thời điểm khác trong quá khứ. <em>The train had left the station when we arrived.</em></p>
                <h3>Cấu trúc</h3>
                <p><strong>(+)</strong> Chủ ngữ + had + V3 ... <em>She had finished her work before she went home.</em></p>
                <p><strong>(-)</strong> Chủ ngữ + had + not + V3 ... <em>She had not finished her work.</em></p>
                <p><strong>(?)</strong> Had + Chủ ngữ + V3 ...? <em>Had she finished her work?</em></p>
                <h3>Từ tín hiệu</h3>
                <p>before, after, already, just, when, by that time.</p>
                HTML,
            ],

            'Past Perfect Continuous' => [
                'en' => <<<HTML
                <h3>When to use</h3>
                <p><strong>Duration before a past event:</strong> Shows how long an action had been happening before another past event. <em>They had been playing tennis for two hours before it started to rain.</em></p>
                <h3>Structure</h3>
                <p><strong>(+)</strong> Subject + had + been + V-ing ... <em>I had been working all day.</em></p>
                <p><strong>(-)</strong> Subject + had + not + been + V-ing ... <em>I had not been working all day.</em></p>
                <p><strong>(?)</strong> Had + Subject + been + V-ing ...? <em>Had you been working all day?</em></p>
                <h3>Signal words</h3>
                <p>since, before, until.</p>
                HTML,
                'vi' => <<<HTML
                <h3>Khi nào nên sử dụng</h3>
                <p><strong>Khoảng thời gian trước một sự kiện trong quá khứ:</strong> Thể hiện một hành động đã diễn ra trong bao lâu trước một sự kiện khác trong quá khứ. <em>They had been playing tennis for two hours before it started to rain.</em></p>
                <h3>Cấu trúc</h3>
                <p><strong>(+)</strong> Chủ ngữ + had + been + V-ing ... <em>I had been working all day.</em></p>
                <p><strong>(-)</strong> Chủ ngữ + had + not + been + V-ing ... <em>I had not been working all day.</em></p>
                <p><strong>(?)</strong> Had + Chủ ngữ + been + V-ing ...? <em>Had you been working all day?</em></p>
                <h3>Từ tín hiệu</h3>
                <p>since, before, until.</p>
                HTML,
            ],

            'Future Simple (will)' => [
                'en' => <<<HTML
                <h3>When to use</h3>
                <p><strong>Spontaneous decisions:</strong> Decisions made at the moment of speaking. <em>It's cold. I will close the window.</em></p>
                <p><strong>Predictions based on personal opinion:</strong> <em>I think it will rain tomorrow.</em></p>
                <p><strong>Promises, offers, requests:</strong> <em>I will help you with your homework.</em></p>
                <h3>Structure</h3>
                <p><strong>(+)</strong> Subject + will + V ... <em>I will call you later.</em></p>
                <p><strong>(-)</strong> Subject + will not (won't) + V ... <em>I won't call you later.</em></p>
                <p><strong>(?)</strong> Will + Subject + V ...? <em>Will you call me later?</em></p>
                <h3>Signal words</h3>
                <p>tomorrow, next week/month, in the future, I think, probably.</p>
                HTML,
                'vi' => <<<HTML
                <h3>Khi nào nên sử dụng</h3>
                <p><strong>Quyết định tự phát:</strong> Những quyết định được đưa ra ngay tại thời điểm nói. <em>It's cold. I will close the window.</em></p>
                <p><strong>Dự đoán dựa trên ý kiến cá nhân:</strong> <em>I think it will rain tomorrow.</em></p>
                <p><strong>Lời hứa, đề nghị, yêu cầu:</strong> <em>I will help you with your homework.</em></p>
                <h3>Cấu trúc</h3>
                <p><strong>(+)</strong> Chủ ngữ + will + V ... <em>I will call you later.</em></p>
                <p><strong>(-)</strong> Chủ ngữ + will not (won't) + V ... <em>I won't call you later.</em></p>
                <p><strong>(?)</strong> Will + Chủ ngữ + V ...? <em>Will you call me later?</em></p>
                <h3>Từ tín hiệu</h3>
                <p>tomorrow, next week/month, in the future, I think, probably.</p>
                HTML,
            ],

            'Be Going To' => [
                'en' => <<<HTML
                <h3>When to use</h3>
                <p><strong>Plans and intentions:</strong> Decisions made before the moment of speaking. <em>We are going to buy a new car next month.</em></p>
                <p><strong>Predictions based on evidence:</strong> When you see something that makes you certain about the future. <em>Look at those dark clouds! It's going to rain.</em></p>
                <h3>Structure</h3>
                <p><strong>(+)</strong> Subject + am/is/are + going to + V ... <em>She is going to travel to Spain.</em></p>
                <p><strong>(-)</strong> Subject + am/is/are + not + going to + V ... <em>She is not going to travel to Spain.</em></p>
                <p><strong>(?)</strong> Am/Is/Are + Subject + going to + V ...? <em>Is she going to travel to Spain?</em></p>
                <h3>Signal words</h3>
                <p>next week/year, tomorrow, in two days.</p>
                HTML,
                'vi' => <<<HTML
                <h3>Khi nào nên sử dụng</h3>
                <p><strong>Kế hoạch và dự định:</strong> Những quyết định được đưa ra trước khi nói. <em>We are going to buy a new car next month.</em></p>
                <p><strong>Dự đoán dựa trên bằng chứng:</strong> Khi bạn nhìn thấy điều gì đó khiến bạn chắc chắn về tương lai. <em>Look at those dark clouds! It's going to rain.</em></p>
                <h3>Cấu trúc</h3>
                <p><strong>(+)</strong> Chủ ngữ + am/is/are + going to + V ... <em>She is going to travel to Spain.</em></p>
                <p><strong>(-)</strong> Chủ ngữ + am/is/are + not + going to + V ... <em>She is not going to travel to Spain.</em></p>
                <p><strong>(?)</strong> Am/Is/Are + Chủ ngữ + going to + V ...? <em>Is she going to travel to Spain?</em></p>
                <h3>Từ tín hiệu</h3>
                <p>next week/year, tomorrow, in two days.</p>
                HTML,
            ],

            'Future Continuous' => [
                'en' => <<<HTML
                <h3>When to use</h3>
                <p><strong>Action in progress at a point in the future:</strong> <em>This time tomorrow, I will be flying to New York.</em></p>
                <h3>Structure</h3>
                <p><strong>(+)</strong> Subject + will be + V-ing ... <em>She will be working at 10 AM.</em></p>
                <p><strong>(-)</strong> Subject + will not be + V-ing ... <em>She will not be working at 10 AM.</em></p>
                <p><strong>(?)</strong> Will + Subject + be + V-ing ...? <em>Will she be working at 10 AM?</em></p>
                <h3>Signal words</h3>
                <p>at this time tomorrow, at 5 pm next Sunday.</p>
                HTML,
                'vi' => <<<HTML
                <h3>Khi nào nên sử dụng</h3>
                <p><strong>Hành động đang diễn ra vào thời điểm trong tương lai:</strong> <em>This time tomorrow, I will be flying to New York.</em></p>
                <h3>Cấu trúc</h3>
                <p><strong>(+)</strong> Chủ ngữ + will be + V-ing ... <em>She will be working at 10 AM.</em></p>
                <p><strong>(-)</strong> Chủ ngữ + will not be + V-ing ... <em>She will not be working at 10 AM.</em></p>
                <p><strong>(?)</strong> Will + Chủ ngữ + be + V-ing ...? <em>Will she be working at 10 AM?</em></p>
                <h3>Từ tín hiệu</h3>
                <p>at this time tomorrow, at 5 pm next Sunday.</p>
                HTML,
            ],

            'Future Perfect' => [
                'en' => <<<HTML
                <h3>When to use</h3>
                <p><strong>Action completed before a point in the future:</strong> <em>By 2030, I will have graduated from university.</em></p>
                <h3>Structure</h3>
                <p><strong>(+)</strong> Subject + will have + V3 ... <em>We will have finished the project by Monday.</em></p>
                <p><strong>(-)</strong> Subject + will not have + V3 ... <em>We will not have finished the project by Monday.</em></p>
                <p><strong>(?)</strong> Will + Subject + have + V3 ...? <em>Will we have finished the project by Monday?</em></p>
                <h3>Signal words</h3>
                <p>by the time, by next week, by 2050.</p>
                HTML,
                'vi' => <<<HTML
                <h3>Khi nào nên sử dụng</h3>
                <p><strong>Hành động hoàn thành trước một thời điểm trong tương lai:</strong> <em>By 2030, I will have graduated from university.</em></p>
                <h3>Cấu trúc</h3>
                <p><strong>(+)</strong> Chủ ngữ + will have + V3 ... <em>We will have finished the project by Monday.</em></p>
                <p><strong>(-)</strong> Chủ ngữ + will not have + V3 ... <em>We will not have finished the project by Monday.</em></p>
                <p><strong>(?)</strong> Will + Chủ ngữ + have + V3 ...? <em>Will we have finished the project by Monday?</em></p>
                <h3>Từ tín hiệu</h3>
                <p>by the time, by next week, by 2050.</p>
                HTML,
            ],

            'Future Perfect Continuous' => [
                'en' => <<<HTML
                <h3>When to use</h3>
                <p><strong>Emphasizing duration before a future point:</strong> <em>By next year, I will have been living here for ten years.</em></p>
                <h3>Structure</h3>
                <p><strong>(+)</strong> Subject + will have been + V-ing ... <em>He will have been studying for four hours by the time you arrive.</em></p>
                <p><strong>(-)</strong> Subject + will not have been + V-ing ... <em>He won't have been studying for four hours by then.</em></p>
                <p><strong>(?)</strong> Will + Subject + have been + V-ing ...? <em>Will he have been studying for four hours by the time you arrive?</em></p>
                <h3>Signal words</h3>
                <p>for..., by the time..., by next week/month/year.</p>
                HTML,
                'vi' => <<<HTML
                <h3>Khi nào nên sử dụng</h3>
                <p><strong>Nhấn mạnh khoảng thời gian trước một thời điểm tương lai:</strong> <em>By next year, I will have been living here for ten years.</em></p>
                <h3>Cấu trúc</h3>
                <p><strong>(+)</strong> Chủ ngữ + will have been + V-ing ... <em>He will have been studying for four hours by the time you arrive.</em></p>
                <p><strong>(-)</strong> Chủ ngữ + will not have been + V-ing ... <em>He won't have been studying for four hours by then.</em></p>
                <p><strong>(?)</strong> Will + Chủ ngữ + have been + V-ing ...? <em>Will he have been studying for four hours by the time you arrive?</em></p>
                <h3>Từ tín hiệu</h3>
                <p>for..., by the time..., by next week/month/year.</p>
                HTML,
            ],

            'Future in the Past' => [
                'en' => <<<HTML
                <h3>When to use</h3>
                <p>Used to describe an idea about the future viewed from a specific point in the past. It's like looking back at a past prediction, plan, or promise from that past moment itself.</p>
                <h3>Structure</h3>
                <p><strong>(+)</strong> Subject + would + V <em>(He said he would help.)</em> / was/were going to + V <em>(I was going to leave early.)</em> / was/were to + V / was/were about to + V.</p>
                <p><strong>(-)</strong> Subject + would not (wouldn't) + V <em>(He said he wouldn't come.)</em></p>
                <p><strong>(?)</strong> Would + subject + V ...? <em>(Would he come?)</em></p>
                <h3>Signal words</h3>
                <p>said (he/she), thought (I), believed, was sure, was going to, would, was about to, was to.</p>
                HTML,
                'vi' => <<<HTML
                <h3>Khi nào nên sử dụng</h3>
                <p>Được dùng để mô tả một ý tưởng ở tương lai tại một thời điểm cụ thể trong quá khứ. Nó giống như việc nhìn lại một dự đoán, kế hoạch hoặc lời hứa trong quá khứ từ góc nhìn của chính quá khứ đó.</p>
                <h3>Cấu trúc</h3>
                <p><strong>(+)</strong> Chủ ngữ + would + V <em>(He said he would help.)</em> / was/were going to + V <em>(I was going to leave early.)</em> / was/were to + V / was/were about to + V.</p>
                <p><strong>(-)</strong> Chủ ngữ + would not (wouldn't) + V <em>(He said he wouldn't come.)</em></p>
                <p><strong>(?)</strong> Would + chủ ngữ + V ...? <em>(Would he come?)</em></p>
                <h3>Từ tín hiệu</h3>
                <p>said (he/she), thought (I), believed, was sure, was going to, would, was about to, was to.</p>
                HTML,
            ],
        ];
    }

    protected function contentSentenceStructures(): array
    {
        return [
            'Comparison of Equality' => [
                'en' => <<<HTML
                <p>Used to say that two things are similar in some way.</p>
                <p><strong>Structure:</strong> as + adjective/adverb + as. <em>She is as tall as her brother.</em></p>
                <p><strong>Negative form (not as/so ... as):</strong> Says two things are not equal. <em>This book is not as interesting as that one.</em></p>
                HTML,
                'vi' => <<<HTML
                <p>Được dùng để nói rằng hai thứ giống nhau ở một khía cạnh nào đó.</p>
                <p><strong>Cấu trúc:</strong> as + tính từ/trạng từ + as. <em>She is as tall as her brother.</em></p>
                <p><strong>Dạng phủ định (not as/so ... as):</strong> Nói rằng hai thứ không bằng nhau. <em>This book is not as interesting as that one.</em></p>
                HTML,
            ],

            'Comparative' => [
                'en' => <<<HTML
                <p>Used to compare two people, things, or ideas, showing a difference in degree.</p>
                <p><strong>Short adjectives:</strong> adjective + er. <em>taller, faster, bigger.</em></p>
                <p><strong>Long adjectives:</strong> more + adjective. <em>more expensive, more interesting.</em></p>
                <p><strong>Structure:</strong> Subject + verb + comparative + than + noun. <em>My car is faster than yours.</em></p>
                HTML,
                'vi' => <<<HTML
                <p>Được dùng để so sánh hai người, hai vật hoặc hai ý tưởng, thể hiện sự khác biệt về mức độ.</p>
                <p><strong>Tính từ ngắn:</strong> tính từ + er. <em>taller, faster, bigger.</em></p>
                <p><strong>Tính từ dài:</strong> more + tính từ. <em>more expensive, more interesting.</em></p>
                <p><strong>Cấu trúc:</strong> Chủ ngữ + động từ + so sánh + than + danh từ. <em>My car is faster than yours.</em></p>
                HTML,
            ],

            'Superlative' => [
                'en' => <<<HTML
                <p>Used to compare three or more things, showing the highest or lowest degree within a group.</p>
                <p><strong>Short adjectives:</strong> the + adjective + est. <em>the tallest, the fastest.</em></p>
                <p><strong>Long adjectives:</strong> the most + adjective. <em>the most expensive.</em></p>
                <p><em>e.g. This is the most beautiful beach I've ever seen.</em></p>
                HTML,
                'vi' => <<<HTML
                <p>Được dùng để so sánh ba hoặc nhiều thứ, thể hiện mức độ cao nhất hoặc thấp nhất trong một nhóm.</p>
                <p><strong>Tính từ ngắn:</strong> the + tính từ + est. <em>the tallest, the fastest.</em></p>
                <p><strong>Tính từ dài:</strong> the most + tính từ. <em>the most expensive.</em></p>
                <p><em>Ví dụ: This is the most beautiful beach I've ever seen.</em></p>
                HTML,
            ],

            'Type 0 Conditional' => [
                'en' => <<<HTML
                <p>Used for general truths, facts, and things that always happen as a result of a certain condition.</p>
                <p><strong>Structure:</strong> If + present simple, present simple.</p>
                <p><em>e.g. If you heat ice, it melts.</em></p>
                HTML,
                'vi' => <<<HTML
                <p>Được sử dụng cho các sự thật tổng quát, các sự kiện và những điều luôn xảy ra do một điều kiện nhất định.</p>
                <p><strong>Cấu trúc:</strong> If + hiện tại đơn, hiện tại đơn.</p>
                <p><em>Ví dụ: If you heat ice, it melts.</em></p>
                HTML,
            ],

            'Type 1 Conditional' => [
                'en' => <<<HTML
                <p>Used for real and possible situations in the future.</p>
                <p><strong>Structure:</strong> If + present simple, will + V (base form).</p>
                <p><em>e.g. If it rains tomorrow, I will stay home.</em></p>
                HTML,
                'vi' => <<<HTML
                <p>Được sử dụng cho các tình huống thực tế và có thể xảy ra trong tương lai.</p>
                <p><strong>Cấu trúc:</strong> If + hiện tại đơn, will + V (nguyên mẫu).</p>
                <p><em>Ví dụ: If it rains tomorrow, I will stay home.</em></p>
                HTML,
            ],

            'Type 2 Conditional' => [
                'en' => <<<HTML
                <p>Used for unreal, hypothetical, or unlikely situations in the present or future.</p>
                <p><strong>Structure:</strong> If + past simple, would + V (base form).</p>
                <p><em>e.g. If I were rich, I would travel the world.</em></p>
                <p>Note: 'were' is used for all subjects with 'be' in formal English.</p>
                HTML,
                'vi' => <<<HTML
                <p>Được sử dụng cho các tình huống không có thật, giả định hoặc khó xảy ra trong hiện tại hoặc tương lai.</p>
                <p><strong>Cấu trúc:</strong> If + quá khứ đơn, would + V (nguyên mẫu).</p>
                <p><em>Ví dụ: If I were rich, I would travel the world.</em></p>
                <p>Lưu ý: 'were' được sử dụng cho tất cả các chủ ngữ với 'be' trong tiếng Anh trang trọng.</p>
                HTML,
            ],

            'Type 3 Conditional' => [
                'en' => <<<HTML
                <p>Used for unreal situations in the past — something that actually did not happen.</p>
                <p><strong>Structure:</strong> If + past perfect, would have + V3.</p>
                <p><em>e.g. If I had studied harder, I would have passed the exam.</em></p>
                HTML,
                'vi' => <<<HTML
                <p>Được dùng cho những tình huống không có thật trong quá khứ — điều gì đó thực sự không xảy ra.</p>
                <p><strong>Cấu trúc:</strong> If + quá khứ hoàn thành, would have + V3.</p>
                <p><em>Ví dụ: If I had studied harder, I would have passed the exam.</em></p>
                HTML,
            ],

            'Mixed Conditional' => [
                'en' => <<<HTML
                <p>Combines a condition from one time period with a result from another.</p>
                <p><strong>Past condition → Present result:</strong> If + past perfect, would + V. <em>If she had taken that job, she would be rich now.</em></p>
                <p><strong>Present condition → Past result:</strong> If + past simple, would have + V3. <em>If he weren't so lazy, he would have finished the project.</em></p>
                HTML,
                'vi' => <<<HTML
                <p>Kết hợp một điều kiện từ một thời kỳ với một kết quả ở một thời kỳ khác.</p>
                <p><strong>Điều kiện quá khứ → Kết quả hiện tại:</strong> If + quá khứ hoàn thành, would + V. <em>If she had taken that job, she would be rich now.</em></p>
                <p><strong>Điều kiện hiện tại → Kết quả quá khứ:</strong> If + quá khứ đơn, would have + V3. <em>If he weren't so lazy, he would have finished the project.</em></p>
                HTML,
            ],

            'Wish Clauses' => [
                'en' => <<<HTML
                <p>Used to express a desire for something to be different from reality.</p>
                <p><strong>Wish + past simple:</strong> used for present situations you want to change. <em>I wish I had more time.</em></p>
                <p><strong>Wish + past perfect:</strong> used for regrets about the past. <em>I wish I had studied harder.</em></p>
                <p><strong>Wish + would + V:</strong> used to express annoyance about someone else's habit. <em>I wish you would stop screaming.</em></p>
                HTML,
                'vi' => <<<HTML
                <p>Được dùng để diễn tả mong muốn điều gì đó khác với thực tế.</p>
                <p><strong>Wish + thì quá khứ đơn:</strong> dùng cho những tình huống hiện tại bạn muốn thay đổi. <em>I wish I had more time.</em></p>
                <p><strong>Wish + thì quá khứ hoàn thành:</strong> dùng cho sự hối tiếc về quá khứ. <em>I wish I had studied harder.</em></p>
                <p><strong>Wish + would + V:</strong> dùng để diễn tả sự khó chịu về thói quen của người khác. <em>I wish you would stop screaming.</em></p>
                HTML,
            ],

            'Passive Voice' => [
                'en' => <<<HTML
                <p>Used when the action or the object of the action is more important than who performed it.</p>
                <p><strong>Structure:</strong> Subject + be + V3 (+ by + agent)</p>
                <p><em>Active voice: The chef cooked the meal. → Passive voice: The meal was cooked by the chef.</em></p>
                <p>The verb 'be' changes form to match the tense of the original sentence (is/was/has been/will be cooked, etc.).</p>
                HTML,
                'vi' => <<<HTML
                <p>Được sử dụng khi hành động hoặc đối tượng của hành động quan trọng hơn người thực hiện nó.</p>
                <p><strong>Cấu trúc:</strong> Chủ ngữ + be + V3 (+ by + tác nhân)</p>
                <p><em>Thể chủ động: The chef cooked the meal. → Thể bị động: The meal was cooked by the chef.</em></p>
                <p>Động từ 'be' thay đổi hình thức để phù hợp với thì của câu gốc (is/was/has been/will be cooked, v.v.).</p>
                HTML,
            ],

            'Causative Verbs (have/get something done)' => [
                'en' => <<<HTML
                <p>Used when someone arranges for another person to do something for them, rather than doing it themselves. The focus is on the action and the result, not on who performed it.</p>
                <p><strong>Structure:</strong> have/get + object + V3 (past participle)</p>
                <p><em>e.g. I had my hair cut yesterday. (Someone else cut it for me.)</em></p>
                <p><em>e.g. We are going to get the car repaired this weekend.</em></p>
                <p>'Have' is more common in everyday speech; 'get' is slightly more informal and can also suggest more effort was needed to arrange it.</p>
                HTML,
                'vi' => <<<HTML
                <p>Được dùng khi ai đó nhờ/thuê người khác làm việc gì đó cho mình, thay vì tự mình làm. Trọng tâm là hành động và kết quả, không phải ai đã thực hiện nó.</p>
                <p><strong>Cấu trúc:</strong> have/get + tân ngữ + V3 (quá khứ phân từ)</p>
                <p><em>Ví dụ: I had my hair cut yesterday. (Người khác cắt tóc cho tôi.)</em></p>
                <p><em>Ví dụ: We are going to get the car repaired this weekend.</em></p>
                <p>'Have' phổ biến hơn trong giao tiếp hàng ngày; 'get' hơi thân mật hơn và có thể ngụ ý cần nhiều công sức hơn để sắp xếp việc đó.</p>
                HTML,
            ],

            'Inversion' => [
                'en' => <<<HTML
                <p>Inversion means swapping the normal subject-verb order (auxiliary verb before the subject), used in formal or literary English for emphasis. It is triggered by certain negative or limiting adverbials placed at the start of a sentence.</p>
                <p><strong>Not only ... but also:</strong> <em>Not only did she pass the exam, but she also got the highest score.</em></p>
                <p><strong>No sooner ... than:</strong> <em>No sooner had he arrived than the meeting started.</em></p>
                <p><strong>Hardly/Scarcely ... when:</strong> <em>Hardly had I sat down when the phone rang.</em></p>
                <p><strong>Never / Rarely / Little (did):</strong> <em>Never have I seen such a beautiful sunset. Little did he know the truth.</em></p>
                HTML,
                'vi' => <<<HTML
                <p>Đảo ngữ là việc đảo trật tự chủ ngữ-động từ thông thường (trợ động từ đứng trước chủ ngữ), dùng trong tiếng Anh trang trọng hoặc văn học để nhấn mạnh. Nó được kích hoạt bởi một số trạng từ mang nghĩa phủ định hoặc giới hạn khi đặt ở đầu câu.</p>
                <p><strong>Not only ... but also:</strong> <em>Not only did she pass the exam, but she also got the highest score.</em></p>
                <p><strong>No sooner ... than:</strong> <em>No sooner had he arrived than the meeting started.</em></p>
                <p><strong>Hardly/Scarcely ... when:</strong> <em>Hardly had I sat down when the phone rang.</em></p>
                <p><strong>Never / Rarely / Little (did):</strong> <em>Never have I seen such a beautiful sunset. Little did he know the truth.</em></p>
                HTML,
            ],

            'Cleft Sentences' => [
                'en' => <<<HTML
                <p>Cleft sentences split one idea into two clauses to put special emphasis on a particular piece of information.</p>
                <p><strong>It is/was ... that/who:</strong> emphasizes a specific part of the sentence. <em>e.g. It was Jane who called me last night. (Not someone else - Jane.)</em></p>
                <p><strong>What ... is/was:</strong> emphasizes an action or a need. <em>e.g. What I need is a good night's sleep.</em></p>
                HTML,
                'vi' => <<<HTML
                <p>Câu chẻ tách một ý thành hai mệnh đề để nhấn mạnh đặc biệt vào một phần thông tin cụ thể.</p>
                <p><strong>It is/was ... that/who:</strong> nhấn mạnh một phần cụ thể của câu. <em>Ví dụ: It was Jane who called me last night. (Không phải ai khác - chính là Jane.)</em></p>
                <p><strong>What ... is/was:</strong> nhấn mạnh một hành động hoặc nhu cầu. <em>Ví dụ: What I need is a good night's sleep.</em></p>
                HTML,
            ],

            'Subjunctive (Câu giả định)' => [
                'en' => <<<HTML
                <p>Used after certain verbs and phrases (suggest, recommend, insist, demand, it's important/essential that...) to express something necessary, mandatory, or hypothetical. The verb stays in its base form regardless of the subject.</p>
                <p><em>e.g. The doctor suggested that he rest for a few days.</em></p>
                <p><em>e.g. It's essential that she be on time.</em></p>
                HTML,
                'vi' => <<<HTML
                <p>Được sử dụng sau một số động từ và cụm từ nhất định (suggest, recommend, insist, demand, it's important/essential that...) để diễn tả điều gì đó cần thiết, bắt buộc hoặc giả định. Động từ vẫn giữ nguyên dạng nguyên mẫu bất kể chủ ngữ.</p>
                <p><em>Ví dụ: The doctor suggested that he rest for a few days.</em></p>
                <p><em>Ví dụ: It's essential that she be on time.</em></p>
                HTML,
            ],

            'Imperative (Câu mệnh lệnh)' => [
                'en' => <<<HTML
                <p>Used to give commands, instructions, requests, or advice. The verb is in the base form and the subject "you" is omitted.</p>
                <p><strong>Affirmative:</strong> V (base form) ... <em>Close the door.</em></p>
                <p><strong>Negative:</strong> Don't + V ... <em>Don't be late.</em></p>
                <p><strong>Polite request:</strong> Please + V ... <em>Please sit down.</em></p>
                HTML,
                'vi' => <<<HTML
                <p>Được sử dụng để đưa ra mệnh lệnh, hướng dẫn, yêu cầu hoặc lời khuyên. Động từ ở dạng nguyên mẫu và chủ ngữ "you" được lược bỏ.</p>
                <p><strong>Khẳng định:</strong> V (nguyên mẫu) ... <em>Close the door.</em></p>
                <p><strong>Phủ định:</strong> Don't + V ... <em>Don't be late.</em></p>
                <p><strong>Yêu cầu lịch sự:</strong> Please + V ... <em>Please sit down.</em></p>
                HTML,
            ],

            'Reported Speech' => [
                'en' => <<<HTML
                <p>Used to report what someone else said. The tense usually shifts back one step (backshift), and pronouns/time expressions change accordingly.</p>
                <p><em>Direct: "I am tired," she said. → Reported: She said that she was tired.</em></p>
                <p><em>Direct: "I will call you," he said. → Reported: He said that he would call me.</em></p>
                <p><strong>Common reporting verbs:</strong> say, tell, ask, explain.</p>
                HTML,
                'vi' => <<<HTML
                <p>Được dùng để thuật lại lời nói của người khác. Thì thường lùi lại một bậc (backshift), và đại từ/biểu thức thời gian cũng thay đổi tương ứng.</p>
                <p><em>Trực tiếp: "I am tired," she said. → Tường thuật: She said that she was tired.</em></p>
                <p><em>Trực tiếp: "I will call you," he said. → Tường thuật: He said that he would call me.</em></p>
                <p><strong>Các động từ tường thuật thông dụng:</strong> say, tell, ask, explain.</p>
                HTML,
            ],

            'Relative Clauses' => [
                'en' => <<<HTML
                <p>A clause that gives extra information about a noun, introduced by a relative pronoun (who, which, that, whose...).</p>
                <p><strong>Defining clauses</strong> (no comma, essential information): <em>The man who called you is my boss.</em></p>
                <p><strong>Non-defining clauses</strong> (with commas, extra information): <em>My brother, who lives in Hanoi, is a doctor.</em></p>
                HTML,
                'vi' => <<<HTML
                <p>Mệnh đề bổ sung thông tin về danh từ, được giới thiệu bởi đại từ quan hệ (who, which, that, whose...).</p>
                <p><strong>Mệnh đề xác định</strong> (không có dấu phẩy, thông tin thiết yếu): <em>The man who called you is my boss.</em></p>
                <p><strong>Mệnh đề không xác định</strong> (có dấu phẩy, thông tin bổ sung): <em>My brother, who lives in Hanoi, is a doctor.</em></p>
                HTML,
            ],

            'Noun Clauses' => [
                'en' => <<<HTML
                <p>A clause that functions as a noun — as the subject, object, or complement of a sentence — usually starting with 'that', 'if/whether', or a wh-word.</p>
                <p><em>e.g. What she said surprised everyone. (subject)</em></p>
                <p><em>e.g. I don't know where he lives. (object)</em></p>
                HTML,
                'vi' => <<<HTML
                <p>Mệnh đề đóng vai trò như một danh từ — là chủ ngữ, tân ngữ hoặc bổ ngữ của câu — thường bắt đầu bằng 'that', 'if/whether', hoặc một từ nghi vấn (wh-word).</p>
                <p><em>Ví dụ: What she said surprised everyone. (chủ ngữ)</em></p>
                <p><em>Ví dụ: I don't know where he lives. (tân ngữ)</em></p>
                HTML,
            ],

            'Participle Clauses' => [
                'en' => <<<HTML
                <p>A shortened way of writing a relative or adverbial clause, using a present participle (V-ing), past participle (V3), or 'having' + V3, instead of a full clause with a subject and a conjugated verb. Common in formal writing.</p>
                <p><strong>-ing form (active meaning):</strong> replaces a clause with an active verb. <em>e.g. The woman standing by the door is my aunt. (= who is standing)</em></p>
                <p><strong>-ed form (passive meaning):</strong> replaces a clause with a passive verb. <em>e.g. The letter written in French was hard to read. (= that was written)</em></p>
                <p><strong>Having + V3 (an earlier completed action):</strong> <em>Having finished his homework, he went out to play. (= After he had finished...)</em></p>
                HTML,
                'vi' => <<<HTML
                <p>Một cách viết rút gọn của mệnh đề quan hệ hoặc mệnh đề trạng ngữ, dùng phân từ hiện tại (V-ing), phân từ quá khứ (V3), hoặc 'having' + V3, thay vì một mệnh đề đầy đủ có chủ ngữ và động từ chia. Thường gặp trong văn viết trang trọng.</p>
                <p><strong>Dạng -ing (nghĩa chủ động):</strong> thay thế một mệnh đề có động từ chủ động. <em>Ví dụ: The woman standing by the door is my aunt. (= who is standing)</em></p>
                <p><strong>Dạng -ed (nghĩa bị động):</strong> thay thế một mệnh đề có động từ bị động. <em>Ví dụ: The letter written in French was hard to read. (= that was written)</em></p>
                <p><strong>Having + V3 (một hành động đã hoàn thành trước đó):</strong> <em>Ví dụ: Having finished his homework, he went out to play. (= After he had finished...)</em></p>
                HTML,
            ],
        ];
    }

    protected function contentQuestionForms(): array
    {
        return [
            'Wh-Questions' => [
                'en' => <<<HTML
                <p>Begin with a question word (What, Where, When, Who, Why, How) to ask for specific information.</p>
                <p><strong>Structure:</strong> Question word + auxiliary verb + subject + verb ...?</p>
                <p><em>e.g. Where do you live?</em></p>
                HTML,
                'vi' => <<<HTML
                <p>Bắt đầu bằng một từ hỏi (What, Where, When, Who, Why, How) để hỏi thông tin cụ thể.</p>
                <p><strong>Cấu trúc:</strong> Từ hỏi + trợ động từ + chủ ngữ + động từ ...?</p>
                <p><em>Ví dụ: Where do you live?</em></p>
                HTML,
            ],

            'Yes/No Questions' => [
                'en' => <<<HTML
                <p>The answer is yes or no; the auxiliary verb comes before the subject.</p>
                <p><strong>Structure:</strong> Auxiliary verb + subject + verb ...?</p>
                <p><em>e.g. Do you like coffee? — Yes, I do.</em></p>
                HTML,
                'vi' => <<<HTML
                <p>Câu trả lời là có hoặc không; trợ động từ đứng trước chủ ngữ.</p>
                <p><strong>Cấu trúc:</strong> Trợ động từ + chủ ngữ + động từ ...?</p>
                <p><em>Ví dụ: Do you like coffee? — Yes, I do.</em></p>
                HTML,
            ],

            'Choice Questions' => [
                'en' => <<<HTML
                <p>Gives the listener a choice between two or more options, connected with the word "or".</p>
                <p><em>e.g. Would you like tea or coffee?</em></p>
                HTML,
                'vi' => <<<HTML
                <p>Đưa ra cho người nghe sự lựa chọn giữa hai hoặc nhiều phương án, được nối với nhau bằng từ "or".</p>
                <p><em>Ví dụ: Would you like tea or coffee?</em></p>
                HTML,
            ],

            'Tag Questions' => [
                'en' => <<<HTML
                <p>A short question added to the end of a statement to confirm information, using the opposite polarity (affirmative statement → negative tag, and vice versa).</p>
                <p><em>e.g. You will come to the party, won't you?</em></p>
                <p><em>e.g. She doesn't smoke, does she?</em></p>
                HTML,
                'vi' => <<<HTML
                <p>Một câu hỏi ngắn được thêm vào cuối câu để xác nhận thông tin, sử dụng cực tính ngược lại (câu khẳng định → câu phủ định, và ngược lại).</p>
                <p><em>Ví dụ: You will come to the party, won't you?</em></p>
                <p><em>Ví dụ: She doesn't smoke, does she?</em></p>
                HTML,
            ],

            'Negative Questions' => [
                'en' => <<<HTML
                <p>Questions formed with a negative auxiliary verb, often used to express surprise, confirm an assumption, or make a polite suggestion.</p>
                <p><em>e.g. Isn't it a beautiful day?</em></p>
                <p><em>e.g. Don't you think we should go now?</em></p>
                HTML,
                'vi' => <<<HTML
                <p>Câu hỏi được hình thành với trợ động từ phủ định, thường được dùng để bày tỏ sự ngạc nhiên, xác nhận một giả định hoặc đưa ra một lời đề nghị lịch sự.</p>
                <p><em>Ví dụ: Isn't it a beautiful day?</em></p>
                <p><em>Ví dụ: Don't you think we should go now?</em></p>
                HTML,
            ],

            'Indirect Questions' => [
                'en' => <<<HTML
                <p>More polite questions, embedded so that the word order returns to normal statement order (no subject-auxiliary inversion).</p>
                <p><em>e.g. Could you tell me where the station is? (not "where is the station")</em></p>
                HTML,
                'vi' => <<<HTML
                <p>Các câu hỏi lịch sự hơn, được lồng ghép trong đó trật tự từ trở lại trật tự câu khẳng định thông thường (không đảo ngữ chủ ngữ-trợ động từ).</p>
                <p><em>Ví dụ: Could you tell me where the station is? (không phải "where is the station")</em></p>
                HTML,
            ],
        ];
    }

    protected function contentCommonStructures(): array
    {
        return [
            'Would you like...?' => [
                'en' => <<<HTML
                <p>A polite way to offer or suggest something.</p>
                <p><em>e.g. Would you like some tea? Would you like to join us?</em></p>
                HTML,
                'vi' => <<<HTML
                <p>Cách nói lịch sự để mời hoặc đề nghị điều gì đó.</p>
                <p><em>Ví dụ: Would you like some tea? Would you like to join us?</em></p>
                HTML,
            ],

            'Would rather' => [
                'en' => <<<HTML
                <p>Used to express a preference between two options; followed by the base form.</p>
                <p><em>e.g. I would rather stay home than go out tonight.</em></p>
                <p><strong>For someone else's action:</strong> "would rather" + subject + past simple. <em>I'd rather you didn't smoke here.</em></p>
                HTML,
                'vi' => <<<HTML
                <p>Được dùng để diễn tả sự ưu tiên giữa hai lựa chọn; theo sau là dạng nguyên mẫu.</p>
                <p><em>Ví dụ: I would rather stay home than go out tonight.</em></p>
                <p><strong>Đối với hành động của người khác:</strong> "would rather" + chủ ngữ + quá khứ đơn. <em>I'd rather you didn't smoke here.</em></p>
                HTML,
            ],

            'Prefer' => [
                'en' => <<<HTML
                <p>Used to express a general preference.</p>
                <p><strong>Structure:</strong> Prefer + noun/V-ing + to + noun/V-ing. <em>I prefer tea to coffee.</em></p>
                <p><strong>Another structure:</strong> Prefer + to V + rather than + V. <em>I prefer to walk rather than drive.</em></p>
                HTML,
                'vi' => <<<HTML
                <p>Được dùng để diễn tả sở thích chung.</p>
                <p><strong>Cấu trúc:</strong> Prefer + danh từ/V-ing + to + danh từ/V-ing. <em>I prefer tea to coffee.</em></p>
                <p><strong>Cấu trúc khác:</strong> Prefer + to V + rather than + V. <em>I prefer to walk rather than drive.</em></p>
                HTML,
            ],

            "Let / Let's" => [
                'en' => <<<HTML
                <p><strong>Let + object + V (base form):</strong> allow someone to do something. <em>Let me help you.</em></p>
                <p><strong>Let's + V (base form):</strong> make a suggestion that includes the speaker. <em>Let's go to the beach.</em></p>
                HTML,
                'vi' => <<<HTML
                <p><strong>Let + tân ngữ + V (nguyên mẫu):</strong> cho phép ai đó làm điều gì đó. <em>Let me help you.</em></p>
                <p><strong>Let's + V (nguyên mẫu):</strong> đưa ra lời đề nghị bao gồm cả người nói. <em>Let's go to the beach.</em></p>
                HTML,
            ],

            'Suggest' => [
                'en' => <<<HTML
                <p><strong>Suggest + V-ing:</strong> <em>I suggest going by train.</em></p>
                <p><strong>Suggest + that + subject + (should) + V (base form):</strong> <em>I suggest she see a doctor.</em></p>
                HTML,
                'vi' => <<<HTML
                <p><strong>Suggest + V-ing:</strong> <em>I suggest going by train.</em></p>
                <p><strong>Suggest + that + chủ ngữ + (should) + V (nguyên mẫu):</strong> <em>I suggest she see a doctor.</em></p>
                HTML,
            ],

            'Hope' => [
                'en' => <<<HTML
                <p><strong>Hope + (that) + clause:</strong> expresses a desire for something to be or become true.</p>
                <p><em>e.g. I hope you get well soon. I hope it doesn't rain tomorrow.</em></p>
                HTML,
                'vi' => <<<HTML
                <p><strong>Hope + (that) + mệnh đề:</strong> diễn tả mong muốn điều gì đó là hoặc sẽ là sự thật.</p>
                <p><em>Ví dụ: I hope you get well soon. I hope it doesn't rain tomorrow.</em></p>
                HTML,
            ],

            'Advise' => [
                'en' => <<<HTML
                <p><strong>Advise + object + to V:</strong> <em>I advise you to see a doctor.</em></p>
                <p><strong>Advise + V-ing</strong> (general advice): <em>I advise seeing a doctor.</em></p>
                HTML,
                'vi' => <<<HTML
                <p><strong>Advise + tân ngữ + to V:</strong> <em>I advise you to see a doctor.</em></p>
                <p><strong>Advise + V-ing</strong> (lời khuyên chung chung): <em>I advise seeing a doctor.</em></p>
                HTML,
            ],

            'Promise' => [
                'en' => <<<HTML
                <p><strong>Promise + to V:</strong> <em>She promised to call me.</em></p>
                <p><strong>Promise + (that) + clause:</strong> <em>He promised that he would help.</em></p>
                HTML,
                'vi' => <<<HTML
                <p><strong>Promise + to V:</strong> <em>She promised to call me.</em></p>
                <p><strong>Promise + (that) + mệnh đề:</strong> <em>He promised that he would help.</em></p>
                HTML,
            ],

            'Ask' => [
                'en' => <<<HTML
                <p><strong>Ask + object + to V:</strong> to request someone to do something. <em>I asked him to close the door.</em></p>
                <p><strong>Ask + if/wh-word + clause:</strong> to ask an indirect question. <em>She asked if I was ready.</em></p>
                HTML,
                'vi' => <<<HTML
                <p><strong>Ask + tân ngữ + to V:</strong> yêu cầu ai đó làm điều gì đó. <em>I asked him to close the door.</em></p>
                <p><strong>Ask + if/wh-word + mệnh đề:</strong> hỏi một câu hỏi gián tiếp. <em>She asked if I was ready.</em></p>
                HTML,
            ],

            'Had better' => [
                'en' => <<<HTML
                <p>Used to give strong advice or a warning about the consequences of not following it. Followed by the base form.</p>
                <p><em>e.g. You had better hurry, or you'll miss the bus.</em></p>
                <p><strong>Negative form:</strong> "had better not" + V. <em>You'd better not be late.</em></p>
                HTML,
                'vi' => <<<HTML
                <p>Được dùng để đưa ra lời khuyên mạnh mẽ hoặc cảnh báo về hậu quả nếu không tuân theo lời khuyên đó. Theo sau là dạng nguyên mẫu.</p>
                <p><em>Ví dụ: You had better hurry, or you'll miss the bus.</em></p>
                <p><strong>Dạng phủ định:</strong> "had better not" + V. <em>You'd better not be late.</em></p>
                HTML,
            ],

            'Need' => [
                'en' => <<<HTML
                <p><strong>Need + V:</strong> <em>I need to finish this today.</em></p>
                <p><strong>Need + V-ing</strong> (passive meaning): <em>This shirt needs washing.</em> = Need + to be + V3: <em>This shirt needs to be washed.</em></p>
                HTML,
                'vi' => <<<HTML
                <p><strong>Need + V:</strong> <em>I need to finish this today.</em></p>
                <p><strong>Need + V-ing</strong> (nghĩa bị động): <em>This shirt needs washing.</em> = Need + to be + V3: <em>This shirt needs to be washed.</em></p>
                HTML,
            ],

            'Verbs followed by Gerund (V-ing)' => [
                'en' => <<<HTML
                <p>Some verbs are always followed by a gerund (V-ing), not an infinitive.</p>
                <p><strong>Common verbs:</strong> enjoy, avoid, finish, suggest, mind, consider, practice, admit.</p>
                <p><em>e.g. She enjoys reading novels.</em></p>
                HTML,
                'vi' => <<<HTML
                <p>Một số động từ luôn đi kèm với danh động từ (V-ing), chứ không phải động từ nguyên mẫu.</p>
                <p><strong>Các động từ phổ biến:</strong> enjoy, avoid, finish, suggest, mind, consider, practice, admit.</p>
                <p><em>Ví dụ: She enjoys reading novels.</em></p>
                HTML,
            ],

            'Refuse' => [
                'en' => <<<HTML
                <p><strong>Refuse + to V:</strong> to say no to doing something.</p>
                <p><em>e.g. He refused to sign the contract.</em></p>
                HTML,
                'vi' => <<<HTML
                <p><strong>Refuse + to V:</strong> nói không với việc làm điều gì đó.</p>
                <p><em>Ví dụ: He refused to sign the contract.</em></p>
                HTML,
            ],

            'Regret' => [
                'en' => <<<HTML
                <p><strong>Regret + V-ing:</strong> feeling sorry about a past action. <em>I regret telling him the truth.</em></p>
                <p><strong>Regret + to V</strong> (formal, used to announce something): <em>We regret to inform you that your flight has been cancelled.</em></p>
                HTML,
                'vi' => <<<HTML
                <p><strong>Regret + V-ing:</strong> cảm thấy tiếc nuối về một hành động trong quá khứ. <em>I regret telling him the truth.</em></p>
                <p><strong>Regret + to V</strong> (trang trọng, dùng để thông báo): <em>We regret to inform you that your flight has been cancelled.</em></p>
                HTML,
            ],

            'Stop' => [
                'en' => <<<HTML
                <p><strong>Stop + V-ing:</strong> to end an action that is in progress. <em>He stopped smoking last year.</em></p>
                <p><strong>Stop + to V:</strong> to pause one action in order to do another. <em>He stopped to smoke a cigarette.</em></p>
                HTML,
                'vi' => <<<HTML
                <p><strong>Stop + V-ing:</strong> để kết thúc một hành động đang diễn ra. <em>He stopped smoking last year.</em></p>
                <p><strong>Stop + to V:</strong> để tạm dừng một hành động để thực hiện một hành động khác. <em>He stopped to smoke a cigarette.</em></p>
                HTML,
            ],

            'find it + adjective + to V' => [
                'en' => <<<HTML
                <p>A structure used to express an opinion about an activity, with 'it' acting as a preparatory object.</p>
                <p><em>e.g. I find it difficult to wake up early. She finds it easy to make friends.</em></p>
                HTML,
                'vi' => <<<HTML
                <p>Một cấu trúc được sử dụng để diễn đạt ý kiến về một hoạt động, với 'it' đóng vai trò là tân ngữ chuẩn bị.</p>
                <p><em>Ví dụ: I find it difficult to wake up early. She finds it easy to make friends.</em></p>
                HTML,
            ],

            'Although / Despite / In spite of' => [
                'en' => <<<HTML
                <p>All three express contrast, but the grammar that follows them is different.</p>
                <p><strong>Although / Even though + clause</strong> (subject + verb): <em>Although it rained, we went out.</em></p>
                <p><strong>Despite / In spite of + noun / V-ing:</strong> <em>Despite the rain, we went out.</em></p>
                HTML,
                'vi' => <<<HTML
                <p>Cả ba đều thể hiện sự tương phản, nhưng ngữ pháp theo sau lại khác nhau.</p>
                <p><strong>Although / Even though + mệnh đề</strong> (chủ ngữ + động từ): <em>Although it rained, we went out.</em></p>
                <p><strong>Despite / In spite of + danh từ / V-ing:</strong> <em>Despite the rain, we went out.</em></p>
                HTML,
            ],

            'Because / Because of' => [
                'en' => <<<HTML
                <p><strong>Because + clause</strong> (subject + verb): <em>We stayed home because it was raining.</em></p>
                <p><strong>Because of + noun/noun phrase:</strong> <em>We stayed home because of the rain.</em></p>
                HTML,
                'vi' => <<<HTML
                <p><strong>Because + mệnh đề</strong> (chủ ngữ + động từ): <em>We stayed home because it was raining.</em></p>
                <p><strong>Because of + danh từ/cụm danh từ:</strong> <em>We stayed home because of the rain.</em></p>
                HTML,
            ],

            'So / Such / Too' => [
                'en' => <<<HTML
                <p><strong>So + adjective/adverb + that:</strong> <em>She was so tired that she fell asleep.</em></p>
                <p><strong>Such + (a/an) + adjective + noun + that:</strong> <em>It was such a good movie that I watched it twice.</em></p>
                <p><strong>Too + adjective + to V:</strong> <em>He was too tired to drive.</em></p>
                HTML,
                'vi' => <<<HTML
                <p><strong>So + tính từ/trạng từ + that:</strong> <em>She was so tired that she fell asleep.</em></p>
                <p><strong>Such + (a/an) + tính từ + danh từ + that:</strong> <em>It was such a good movie that I watched it twice.</em></p>
                <p><strong>Too + tính từ + to V:</strong> <em>He was too tired to drive.</em></p>
                HTML,
            ],

            'Not only ... but also' => [
                'en' => <<<HTML
                <p>Used for emphasis by linking two related ideas; causes subject-verb inversion when placed at the start of a sentence.</p>
                <p><em>e.g. She is not only smart but also kind.</em></p>
                <p><em>e.g. Not only was he late, but he also forgot his documents.</em></p>
                HTML,
                'vi' => <<<HTML
                <p>Được dùng để nhấn mạnh bằng cách liên kết hai ý tưởng có liên quan; gây ra hiện tượng đảo ngữ chủ ngữ-động từ khi đứng đầu câu.</p>
                <p><em>Ví dụ: She is not only smart but also kind.</em></p>
                <p><em>Ví dụ: Not only was he late, but he also forgot his documents.</em></p>
                HTML,
            ],

            'As well as' => [
                'en' => <<<HTML
                <p>Used to add information, similar to "and"; usually followed by a noun or an -ing verb form.</p>
                <p><em>e.g. She speaks French as well as English.</em></p>
                HTML,
                'vi' => <<<HTML
                <p>Được dùng để bổ sung thông tin, tương tự như "and"; thường theo sau là một danh từ hoặc động từ ở dạng -ing.</p>
                <p><em>Ví dụ: She speaks French as well as English.</em></p>
                HTML,
            ],

            'It was not until ... that' => [
                'en' => <<<HTML
                <p>Used for emphasis, pointing out when something finally happened; causes inversion in the "until" clause when placed at the start of a sentence.</p>
                <p><em>e.g. It was not until midnight that he finished his work.</em></p>
                HTML,
                'vi' => <<<HTML
                <p>Được dùng để nhấn mạnh, chỉ thời điểm một việc gì đó cuối cùng xảy ra; gây ra sự đảo ngữ trong mệnh đề "until" khi đặt ở đầu câu.</p>
                <p><em>Ví dụ: It was not until midnight that he finished his work.</em></p>
                HTML,
            ],

            'Purpose: So that / In order to / To V' => [
                'en' => <<<HTML
                <p>Used to state the purpose or reason behind an action - answering the question "Why?".</p>
                <p><strong>To V / In order to V:</strong> followed by the base form, usually with the same subject. <em>e.g. She saved money to buy a new phone. He arrived early in order to get a good seat.</em></p>
                <p><strong>So that + clause:</strong> followed by a full clause (subject + verb), often with can/could/will/would. Used when the subjects are different or when a modal is needed. <em>e.g. She whispered so that no one would hear her.</em></p>
                <p><strong>Negative purpose:</strong> to avoid + V-ing / so as not to + V. <em>e.g. He left early to avoid the traffic.</em></p>
                HTML,
                'vi' => <<<HTML
                <p>Được dùng để nêu mục đích hoặc lý do đằng sau một hành động - trả lời câu hỏi "Tại sao?".</p>
                <p><strong>To V / In order to V:</strong> theo sau là động từ nguyên mẫu, thường cùng chủ ngữ với mệnh đề chính. <em>Ví dụ: She saved money to buy a new phone. He arrived early in order to get a good seat.</em></p>
                <p><strong>So that + mệnh đề:</strong> theo sau là một mệnh đề đầy đủ (chủ ngữ + động từ), thường có can/could/will/would. Dùng khi chủ ngữ khác nhau hoặc cần dùng động từ khuyết thiếu. <em>Ví dụ: She whispered so that no one would hear her.</em></p>
                <p><strong>Mục đích phủ định:</strong> to avoid + V-ing / so as not to + V. <em>Ví dụ: He left early to avoid the traffic.</em></p>
                HTML,
            ],

            'When / While / After' => [
                'en' => <<<HTML
                <p><strong>When:</strong> at the moment something happens. <em>When I arrived, she was cooking.</em></p>
                <p><strong>While:</strong> during a period of time, often with a continuous tense. <em>While I was cooking, he called.</em></p>
                <p><strong>After:</strong> following an action. <em>After he finished eating, he left.</em></p>
                HTML,
                'vi' => <<<HTML
                <p><strong>When:</strong> vào thời điểm một việc gì đó xảy ra. <em>When I arrived, she was cooking.</em></p>
                <p><strong>While:</strong> trong một khoảng thời gian, thường đi kèm với thì tiếp diễn. <em>While I was cooking, he called.</em></p>
                <p><strong>After:</strong> sau một hành động. <em>After he finished eating, he left.</em></p>
                HTML,
            ],

            'Used to / Be used to' => [
                'en' => <<<HTML
                <p><strong>Used to + V (base form):</strong> a past habit that no longer happens now. <em>I used to play football every weekend.</em></p>
                <p><strong>Be/Get used to + V-ing:</strong> to become familiar with something. <em>I am used to waking up early now.</em></p>
                HTML,
                'vi' => <<<HTML
                <p><strong>Used to + V (nguyên mẫu):</strong> một thói quen trong quá khứ mà hiện tại không còn thực hiện nữa. <em>I used to play football every weekend.</em></p>
                <p><strong>Be/Get used to + V-ing:</strong> trở nên quen với điều gì đó. <em>I am used to waking up early now.</em></p>
                HTML,
            ],

            'Remember' => [
                'en' => <<<HTML
                <p><strong>Remember + V-ing:</strong> to recall a past action. <em>I remember locking the door.</em></p>
                <p><strong>Remember + to V:</strong> not to forget a future task. <em>Remember to lock the door.</em></p>
                HTML,
                'vi' => <<<HTML
                <p><strong>Remember + V-ing:</strong> để nhớ lại một hành động trong quá khứ. <em>I remember locking the door.</em></p>
                <p><strong>Remember + to V:</strong> để không quên một nhiệm vụ trong tương lai. <em>Remember to lock the door.</em></p>
                HTML,
            ],

            'Unless' => [
                'en' => <<<HTML
                <p>Means "if...not"; used to state a condition that must be true to avoid a negative result.</p>
                <p><em>e.g. You won't pass unless you study harder. (= if you don't study harder)</em></p>
                HTML,
                'vi' => <<<HTML
                <p>Có nghĩa là "nếu...không"; dùng để chỉ một điều kiện phải đúng để tránh kết quả tiêu cực.</p>
                <p><em>Ví dụ: You won't pass unless you study harder. (= if you don't study harder)</em></p>
                HTML,
            ],

            'Enough' => [
                'en' => <<<HTML
                <p><strong>Adjective/adverb + enough (+ to V):</strong> <em>She is old enough to drive.</em></p>
                <p><strong>Enough + noun:</strong> <em>We don't have enough time.</em></p>
                HTML,
                'vi' => <<<HTML
                <p><strong>Tính từ/trạng từ + enough (+ to V):</strong> <em>She is old enough to drive.</em></p>
                <p><strong>Enough + danh từ:</strong> <em>We don't have enough time.</em></p>
                HTML,
            ],

            "Impersonal 'It' Structures" => [
                'en' => <<<HTML
                <p>'It' is used as a dummy subject to talk about time, weather, distance, or to give an opinion.</p>
                <p><em>e.g. It is raining. It takes an hour to get there. It is important to study hard.</em></p>
                HTML,
                'vi' => <<<HTML
                <p>'It' được sử dụng như một chủ ngữ giả để nói về thời gian, thời tiết, khoảng cách, hoặc để nêu lên một ý kiến.</p>
                <p><em>Ví dụ: It is raining. It takes an hour to get there. It is important to study hard.</em></p>
                HTML,
            ],

            'There is / There are' => [
                'en' => <<<HTML
                <p>Used to say that something exists or is present somewhere, introducing new information into a sentence. 'There' is a dummy subject; the real subject comes after the verb, and the verb agrees with that real subject.</p>
                <p><strong>There is</strong> + singular/uncountable noun: <em>e.g. There is a cat on the roof. There is some milk in the fridge.</em></p>
                <p><strong>There are</strong> + plural noun: <em>e.g. There are three books on the table.</em></p>
                <p><strong>Other tenses:</strong> There was/were (past), there will be (future), there has/have been (present perfect). <em>e.g. There used to be a park here.</em></p>
                HTML,
                'vi' => <<<HTML
                <p>Được dùng để nói rằng điều gì đó tồn tại hoặc có mặt ở đâu đó, giới thiệu thông tin mới vào câu. 'There' là chủ ngữ giả; chủ ngữ thật đứng sau động từ, và động từ chia theo chủ ngữ thật đó.</p>
                <p><strong>There is</strong> + danh từ số ít/không đếm được: <em>Ví dụ: There is a cat on the roof. There is some milk in the fridge.</em></p>
                <p><strong>There are</strong> + danh từ số nhiều: <em>Ví dụ: There are three books on the table.</em></p>
                <p><strong>Các thì khác:</strong> There was/were (quá khứ), there will be (tương lai), there has/have been (hiện tại hoàn thành). <em>Ví dụ: There used to be a park here.</em></p>
                HTML,
            ],
        ];
    }
}
