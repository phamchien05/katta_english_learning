<?php

namespace Tests\Feature;

use App\Models\GrammarTopic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Kiểm tra Phần A của module Ngữ pháp (mục 7): trang gốc /grammar, cây điều hướng lý thuyết,
// mở đúng bài theo slug, mặc định chọn bài đầu tiên có nội dung khi chưa chọn gì, và nội dung bài học
// phải theo đúng ngôn ngữ app đang hiển thị (mặc định tiếng Anh, đổi được sang tiếng Việt).
class GrammarTheoryTest extends TestCase
{
    use RefreshDatabase;

    protected function makeTree(): void
    {
        $partsOfSpeech = GrammarTopic::create(['title' => 'Parts of Speech', 'slug' => 'parts-of-speech', 'order' => 0]);
        $nouns = GrammarTopic::create(['parent_id' => $partsOfSpeech->id, 'title' => 'Nouns', 'slug' => 'nouns', 'order' => 0]);
        GrammarTopic::create([
            'parent_id' => $nouns->id,
            'title' => 'Common and Proper Nouns',
            'slug' => 'common-and-proper-nouns',
            'content_en' => '<p>Common and proper nouns explained.</p>',
            'content_vi' => '<p>Danh tu chung va danh tu rieng.</p>',
            'order' => 0,
        ]);
        GrammarTopic::create([
            'parent_id' => $nouns->id,
            'title' => 'Compound Nouns',
            'slug' => 'compound-nouns',
            'content_en' => '<p>Compound nouns explained.</p>',
            'content_vi' => '<p>Danh tu ghep.</p>',
            'order' => 1,
        ]);

        $tenses = GrammarTopic::create(['title' => 'Tenses', 'slug' => 'tenses', 'order' => 1]);
        GrammarTopic::create([
            'parent_id' => $tenses->id,
            'title' => 'Future in the Past',
            'slug' => 'future-in-the-past',
            'content_en' => '<h3>Structure</h3><p>Would + V.</p>',
            'content_vi' => '<h3>Cau truc</h3><p>Would + V.</p>',
            'order' => 1,
        ]);
    }

    public function test_grammar_index_page_shows_two_cards(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('grammar.index'));

        $response->assertOk();
        $response->assertSee(__('grammar.card_theory_title'));
        $response->assertSee(__('grammar.card_practice_title'));
        $response->assertSee(route('grammar.theory'), false);
        $response->assertSee(route('grammar.practice'), false);
    }

    public function test_theory_page_defaults_to_first_topic_with_content(): void
    {
        $this->makeTree();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('grammar.theory'));

        $response->assertOk();
        $response->assertSee('Common and Proper Nouns');
        $response->assertSee('Common and proper nouns explained.', false);
    }

    public function test_theory_page_shows_selected_topic_by_slug(): void
    {
        $this->makeTree();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('grammar.theory', 'future-in-the-past'));

        $response->assertOk();
        $response->assertSee('Future in the Past');
        $response->assertSee('Would + V.', false);
    }

    public function test_theory_page_invalid_slug_returns_404(): void
    {
        $this->makeTree();
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('grammar.theory', 'does-not-exist'))->assertNotFound();
    }

    public function test_theory_navigation_tree_renders_full_hierarchy(): void
    {
        $this->makeTree();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('grammar.theory'));

        $response->assertOk();
        $response->assertSee('Parts of Speech');
        $response->assertSee('Nouns');
        $response->assertSee('Tenses');
    }

    public function test_theory_content_defaults_to_english(): void
    {
        // Mặc định app hiển thị tiếng Anh (APP_LOCALE=en) - chưa đổi ngôn ngữ thì phải thấy bản tiếng Anh
        $this->makeTree();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('grammar.theory', 'common-and-proper-nouns'));

        $response->assertOk();
        $response->assertSee('Common and proper nouns explained.', false);
        $response->assertDontSee('Danh tu chung va danh tu rieng.', false);
    }

    public function test_theory_content_switches_to_vietnamese_when_locale_changed(): void
    {
        // User tự bấm chuyển ngôn ngữ (lưu trong session) - nội dung bài học phải đổi theo, không chỉ nhãn giao diện
        $this->makeTree();
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get('/lang/vi');

        $response = $this->get(route('grammar.theory', 'common-and-proper-nouns'));

        $response->assertOk();
        $response->assertSee('Danh tu chung va danh tu rieng.', false);
        $response->assertDontSee('Common and proper nouns explained.', false);
    }

    public function test_seeded_tree_matches_required_structure(): void
    {
        // Đúng theo A.2: 5 nhánh gốc, "Future in the Past" là node lá ngang hàng với "Future Tenses"
        // (không phải con của nó), mọi node lá đều có nội dung bài học ở cả 2 ngôn ngữ.
        $this->seed(\Database\Seeders\GrammarTopicSeeder::class);

        $this->assertSame(5, GrammarTopic::whereNull('parent_id')->count());

        $tenses = GrammarTopic::where('slug', 'tenses')->firstOrFail();
        $futureInThePast = GrammarTopic::where('title', 'Future in the Past')->firstOrFail();
        $this->assertSame($tenses->id, $futureInThePast->parent_id);
        $this->assertNotEmpty($futureInThePast->content_en);
        $this->assertNotEmpty($futureInThePast->content_vi);

        $leavesMissingContent = GrammarTopic::whereDoesntHave('children')
            ->where(function ($q) {
                $q->whereNull('content_en')->orWhereNull('content_vi');
            })
            ->count();
        $this->assertSame(0, $leavesMissingContent);
    }
}
