<?php

namespace App\Http\Controllers;

use App\Models\TranslationPassage;
use App\Models\TranslationSubmission;
use App\Services\GeminiService;
use App\Services\TranslationPassageGenerator;
use Illuminate\Http\Request;

class TranslateController extends Controller
{
    // 5 cấp độ dịch hỗ trợ (mục 5: A1-C1, khác Từ vựng có thêm C2)
    public const LEVELS = ['A1', 'A2', 'B1', 'B2', 'C1'];

    // Trang chọn cấp độ dịch + tab "Đã dịch" (lịch sử các lần nộp bài của user)
    public function index(Request $request)
    {
        $history = TranslationSubmission::with('passage')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return view('pages.translate.index', [
            'title' => __('nav.translate'),
            'icon' => 'globe',
            'levels' => self::LEVELS,
            'direction' => session('translate_direction', 'en_vi'),
            'history' => $history,
        ]);
    }

    // Trang luyện dịch (render Livewire component Practice)
    // ?passage={id} = "Dịch lại" 1 đoạn cụ thể thay vì lấy ngẫu nhiên
    public function practice(Request $request, string $level)
    {
        abort_unless(in_array($level, self::LEVELS), 404);

        return view('pages.translate.practice', [
            'title' => __('nav.translate'),
            'icon' => 'globe',
            'level' => $level,
            'passageId' => $request->integer('passage') ?: null,
        ]);
    }

    // Bù kho: sinh 1 đoạn văn mới cho level+direction, gọi ngầm từ JS ngay sau khi user lấy 1 đoạn ra làm
    // (xem mục "kho luôn có lượng đoạn văn nhất định" đã thống nhất) - không chặn UI, không trả nội dung.
    public function replenish(Request $request)
    {
        $data = $request->validate([
            'level' => 'required|in:A1,A2,B1,B2,C1',
            'direction' => 'required|in:en_vi,vi_en',
        ]);

        // Ưu tiên key Gemini riêng của user (Cài đặt, mục 14) - không có thì dùng key chung mặc định
        $apiKey = $request->user()?->geminiApiKey();
        if (! $apiKey) {
            return response()->noContent();
        }

        $generator = new TranslationPassageGenerator(new GeminiService($apiKey));
        // Chỉ retry 1 lần (không phải 3) - đây là request ngầm, không nên giữ kết nối quá lâu
        $passages = $generator->generate($data['level'], $data['direction'], 1, maxRetries: 1);

        foreach ($passages as $text) {
            $text = trim($text);
            if ($text !== '') {
                TranslationPassage::create([
                    'level' => $data['level'],
                    'direction' => $data['direction'],
                    'source_text' => $text,
                ]);
            }
        }

        return response()->noContent();
    }
}
