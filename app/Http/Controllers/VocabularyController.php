<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class VocabularyController extends Controller
{
    // 6 cấp độ CEFR hỗ trợ trong app (dùng chung cho route constraint + view)
    public const LEVELS = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];

    // Trang chọn cấp độ (mục 4)
    public function index(Request $request)
    {
        return view('pages.vocabulary.index', [
            'title' => __('nav.vocabulary'),
            'icon' => 'book',
            'levels' => self::LEVELS,
        ]);
    }

    // Trang làm bài kiểm tra + kết quả (render Livewire component Quiz)
    public function test(Request $request, string $level)
    {
        abort_unless(in_array($level, self::LEVELS), 404);

        return view('pages.vocabulary.test', [
            'title' => __('nav.vocabulary'),
            'icon' => 'book',
            'level' => $level,
        ]);
    }
}
