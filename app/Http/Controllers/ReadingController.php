<?php

namespace App\Http\Controllers;

use App\Models\ReadingPassage;
use App\Models\ReadingSubmission;
use App\Services\GeminiService;
use App\Services\ReadingPassageGenerator;
use App\Services\ReadingPassagePicker;
use Illuminate\Http\Request;

class ReadingController extends Controller
{
    // Trang danh sách bài đọc, nhóm theo chủ đề + tìm kiếm (lọc client-side) + tab "Đã đọc" (mục 6)
    public function index(Request $request)
    {
        // Nhóm theo chủ đề (không hiển thị số lượng cố định vì kho tự bù, luôn thay đổi)
        // Sắp xếp nhóm theo đúng thứ tự TOPICS ("general" lên đầu), không phải theo bài mới nhất
        $grouped = ReadingPassage::orderByDesc('id')->get()->groupBy('topic')
            ->sortBy(fn ($items, $topic) => array_search($topic, ReadingPassageGenerator::TOPICS));

        $history = ReadingSubmission::with('passage')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return view('pages.reading.index', [
            'title' => __('nav.reading'),
            'icon' => 'bookmark',
            'topics' => ReadingPassageGenerator::TOPICS,
            'grouped' => $grouped,
            'history' => $history,
        ]);
    }

    // Trang làm bài đọc (render Livewire component Practice)
    public function show(Request $request, ReadingPassage $passage, ReadingPassagePicker $picker)
    {
        $picker->markSeen($passage);

        return view('pages.reading.practice', [
            'title' => __('nav.reading'),
            'icon' => 'bookmark',
            'passageId' => $passage->id,
        ]);
    }

    // Chủ đề "Tổng quan" (general) tổ chức theo cấp độ CEFR (giống Từ vựng/Dịch): bấm vào 1 cấp ->
    // random 1 bài trong kho của cấp đó (tránh bài vừa xem, không chỉ bài đã nộp), rồi mở trang làm bài.
    public function general(Request $request, string $level, ReadingPassagePicker $picker)
    {
        abort_unless(in_array($level, ['A1', 'A2', 'B1', 'B2', 'C1']), 404);

        $passageId = $picker->pickUnseen($request->user()->id, 'general', $level);

        abort_unless($passageId, 404);

        return redirect()->route('reading.show', $passageId);
    }

    // Bù kho: sinh 1 bài đọc mới cho topic+level, gọi ngầm từ JS ngay sau khi user vào làm 1 bài
    public function replenish(Request $request)
    {
        $data = $request->validate([
            'topic' => 'required|in:' . implode(',', ReadingPassageGenerator::TOPICS),
            'level' => 'nullable|string|max:2',
        ]);

        // Ưu tiên key Gemini riêng của user (Cài đặt, mục 14) - không có thì dùng key chung mặc định
        $apiKey = $request->user()?->geminiApiKey();
        if (! $apiKey) {
            return response()->noContent();
        }

        $generator = new ReadingPassageGenerator(new GeminiService($apiKey));
        // Chỉ retry 1 lần - đây là request ngầm, không nên giữ kết nối quá lâu
        $result = $generator->generate($data['topic'], $data['level'] ?? 'B2', maxRetries: 1);

        if ($result) {
            $passage = ReadingPassage::create([
                'topic' => $data['topic'],
                'title' => $result['title'],
                'level' => $data['level'] ?? 'B2',
                'content' => $result['content'],
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
