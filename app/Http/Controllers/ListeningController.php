<?php

namespace App\Http\Controllers;

use App\Models\ListeningPassage;
use App\Models\ListeningSubmission;
use App\Services\GeminiService;
use App\Services\ListeningPassageGenerator;
use App\Services\ListeningPassagePicker;
use Illuminate\Http\Request;

class ListeningController extends Controller
{
    // Trang danh sách bài nghe, nhóm theo chủ đề + tab "Đã nghe" (mục 8) - y hệt Đọc hiểu
    public function index(Request $request)
    {
        $grouped = ListeningPassage::orderByDesc('id')->get()->groupBy('topic')
            ->sortBy(fn ($items, $topic) => array_search($topic, ListeningPassageGenerator::TOPICS));

        $history = ListeningSubmission::with('passage')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return view('pages.listening.index', [
            'title' => __('nav.listening'),
            'icon' => 'headphones',
            'topics' => ListeningPassageGenerator::TOPICS,
            'fixedLevels' => ListeningPassageGenerator::FIXED_LEVELS,
            'grouped' => $grouped,
            'history' => $history,
        ]);
    }

    // Trang làm bài nghe (render Livewire component Practice) - transcript bị ẩn cho tới khi nộp bài
    public function show(Request $request, ListeningPassage $passage, ListeningPassagePicker $picker)
    {
        $picker->markSeen($passage);

        return view('pages.listening.practice', [
            'title' => __('nav.listening'),
            'icon' => 'headphones',
            'passageId' => $passage->id,
        ]);
    }

    // Chủ đề "Tổng quan" (general) tổ chức theo cấp độ CEFR, y hệt Đọc hiểu
    public function general(Request $request, string $level, ListeningPassagePicker $picker)
    {
        abort_unless(in_array($level, ['A1', 'A2', 'B1', 'B2', 'C1']), 404);

        $passageId = $picker->pickUnseen($request->user()->id, 'general', $level);

        abort_unless($passageId, 404);

        return redirect()->route('listening.show', $passageId);
    }

    // Bù kho: sinh 1 bài nghe mới, gọi ngầm từ JS ngay sau khi user vào làm 1 bài
    public function replenish(Request $request)
    {
        $data = $request->validate([
            'topic' => 'required|in:' . implode(',', ListeningPassageGenerator::TOPICS),
            'level' => 'nullable|string|max:2',
        ]);

        // Ưu tiên key Gemini riêng của user (Cài đặt, mục 14) - không có thì dùng key chung mặc định
        $apiKey = $request->user()?->geminiApiKey();
        if (! $apiKey) {
            return response()->noContent();
        }

        // Chủ đề không phải "general" luôn gắn cứng 1 cấp độ theo dạng bài (không nhận level tuỳ ý)
        $level = ListeningPassageGenerator::FIXED_LEVELS[$data['topic']] ?? ($data['level'] ?? 'B2');

        // Gửi kèm tiêu đề các bài đã có cùng topic/level - tránh AI sinh trùng/gần giống nội dung cũ
        $existingTitles = ListeningPassage::where('topic', $data['topic'])
            ->when($data['topic'] === 'general', fn ($q) => $q->where('level', $level))
            ->pluck('title')->all();

        $generator = new ListeningPassageGenerator(new GeminiService($apiKey));
        $result = $generator->generate($data['topic'], $level, maxRetries: 1, existingTitles: $existingTitles);

        if ($result) {
            $passage = ListeningPassage::create([
                'topic' => $data['topic'],
                'title' => $result['title'],
                'level' => $data['topic'] === 'general' ? $level : null,
                'transcript' => $result['transcript'],
            ]);

            foreach ($result['questions'] as $order => $q) {
                $passage->questions()->create([
                    'type' => $q['type'],
                    'question' => $q['question'],
                    'options' => $q['options'] ?? [],
                    'correct_answer' => $q['correct_answer'],
                    'order' => $order,
                ]);
            }
        }

        return response()->noContent();
    }
}
