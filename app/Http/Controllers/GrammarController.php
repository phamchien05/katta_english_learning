<?php

namespace App\Http\Controllers;

use App\Models\GrammarQuestionSet;
use App\Models\GrammarTopic;
use App\Services\GeminiService;
use App\Services\GrammarQuestionSetGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

// Module Ngữ pháp (mục 7). Phần A (Xem lý thuyết) được xây trước vì Phần B (luyện tập) sẽ tái sử dụng
// nội dung lý thuyết làm tài liệu gốc cho AI sinh đề.
class GrammarController extends Controller
{
    // Trang gốc /grammar - 2 thẻ lớn: Xem lý thuyết / Bắt đầu luyện tập
    public function index(Request $request)
    {
        return view('pages.grammar.index', [
            'title' => __('nav.grammar'),
            'icon' => 'pencil',
        ]);
    }

    // Phần A - /grammar/theory/{topic?} - sidebar accordion phân cấp + nội dung bài học bên phải
    public function theory(Request $request, ?string $topic = null)
    {
        // Lấy toàn bộ cây 1 lần (126 node) rồi dựng cây trong PHP - tránh N+1 khi đệ quy quan hệ Eloquent
        $all = GrammarTopic::orderBy('order')->get(['id', 'parent_id', 'title', 'slug', 'content_vi', 'content_en', 'order']);

        $active = $topic ? $all->firstWhere('slug', $topic) : null;
        if ($topic && ! $active) {
            abort(404);
        }

        // Chưa chọn bài nào - mặc định mở bài lá đầu tiên có nội dung (bài đầu của nhánh đầu tiên)
        if (! $active) {
            $active = $all->first(fn (GrammarTopic $t) => $t->hasContent());
        }

        // Danh sách id tổ tiên (bao gồm chính nó) của bài đang xem, dùng để tự mở accordion đúng đường dẫn
        $activePath = [];
        $cursor = $active;
        while ($cursor) {
            $activePath[] = $cursor->id;
            $cursor = $cursor->parent_id ? $all->firstWhere('id', $cursor->parent_id) : null;
        }

        return view('pages.grammar.theory', [
            'title' => __('nav.grammar'),
            'icon' => 'pencil',
            'tree' => $this->buildTree($all, null),
            'active' => $active,
            'activePath' => $activePath,
        ]);
    }

    // Phần B - /grammar/practice - 5 thẻ chủ đề (khớp đúng 5 nhánh gốc của Phần A) + tab "Đã làm"
    public function practice(Request $request)
    {
        $history = GrammarQuestionSet::where('user_id', $request->user()->id)
            ->where('status', 'completed')
            ->latest('completed_at')
            ->get();

        return view('pages.grammar.practice.index', [
            'title' => __('nav.grammar'),
            'icon' => 'pencil',
            'topics' => GrammarQuestionSetGenerator::TOPICS,
            'history' => $history,
        ]);
    }

    // Bấm vào 1 chủ đề để làm bài: lấy ngay 1 bộ "available" trong kho (không chờ AI) và gán cho user
    // (status -> in_progress). Nếu kho rỗng (hiếm khi xảy ra, ví dụ vừa khởi tạo xong) thì sinh đồng bộ
    // ngay 1 bộ để không chặn người dùng mãi. Việc bù kho (sinh thêm 1 bộ mới) xảy ra ở practiceReplenish(),
    // gọi ngầm từ JS ngay khi trang làm bài load xong - xem GrammarQuestionSetGenerator + Livewire\Grammar\Quiz.
    public function practiceStart(Request $request, string $topicKey)
    {
        abort_unless(array_key_exists($topicKey, GrammarQuestionSetGenerator::TOPICS), 404);

        $set = DB::transaction(function () use ($topicKey, $request) {
            $set = GrammarQuestionSet::where('topic_key', $topicKey)
                ->where('status', 'available')
                ->lockForUpdate()
                ->first();

            if (! $set) {
                $set = $this->generateSetSynchronously($topicKey);
            }

            if ($set) {
                $set->update([
                    'status' => 'in_progress',
                    'user_id' => $request->user()->id,
                    'started_at' => now(),
                ]);
            }

            return $set;
        });

        abort_unless($set, 503, 'Không thể tạo bộ đề lúc này, vui lòng thử lại.');

        return redirect()->route('grammar.practice.show', $set);
    }

    // Trang làm bài (render Livewire component Quiz) - chỉ user được gán mới xem được bộ đề đang làm/đã làm
    public function practiceShow(Request $request, GrammarQuestionSet $set)
    {
        abort_unless($set->user_id === $request->user()->id, 403);

        return view('pages.grammar.practice.show', [
            'title' => __('nav.grammar'),
            'icon' => 'pencil',
            'setId' => $set->id,
            'topicKey' => $set->topic_key,
        ]);
    }

    // Bù kho: sinh 1 bộ đề mới cho topic, gọi ngầm từ JS ngay sau khi user lấy 1 bộ ra làm (không chặn UI)
    public function practiceReplenish(Request $request)
    {
        $data = $request->validate([
            'topic' => 'required|in:' . implode(',', array_keys(GrammarQuestionSetGenerator::TOPICS)),
        ]);

        $this->generateAndStoreSet($data['topic'], maxRetries: 1);

        return response()->noContent();
    }

    protected function generateSetSynchronously(string $topicKey): ?GrammarQuestionSet
    {
        return $this->generateAndStoreSet($topicKey, maxRetries: 3);
    }

    protected function generateAndStoreSet(string $topicKey, int $maxRetries): ?GrammarQuestionSet
    {
        // Ưu tiên key Gemini riêng của user (Cài đặt, mục 14) - không có thì dùng key chung mặc định
        $apiKey = auth()->user()?->geminiApiKey();
        if (! $apiKey) {
            return null;
        }

        // Timeout dài hơn mặc định - mỗi bộ 30-50 câu hỏi phức tạp hơn nhiều so với các module khác
        $generator = new GrammarQuestionSetGenerator(new GeminiService($apiKey, timeoutSeconds: 90));
        $result = $generator->generate($topicKey, $maxRetries);

        if (! $result) {
            return null;
        }

        $set = GrammarQuestionSet::create(['topic_key' => $topicKey, 'status' => 'available']);

        foreach ($result['questions'] as $order => $q) {
            $set->questions()->create([
                'type' => $q['type'],
                'question' => $q['question'],
                'options' => $q['options'] ?? [],
                'correct_answer' => $q['correct_answer'],
                'order' => $order,
            ]);
        }

        return $set;
    }

    /**
     * Dựng cây lồng nhau {topic, children[]} từ danh sách phẳng đã sắp xếp theo order.
     *
     * @return array<int, array{topic: GrammarTopic, children: array}>
     */
    protected function buildTree(Collection $all, ?int $parentId): array
    {
        return $all->where('parent_id', $parentId)
            ->map(fn (GrammarTopic $t) => [
                'topic' => $t,
                'children' => $this->buildTree($all, $t->id),
            ])
            ->values()
            ->all();
    }
}
