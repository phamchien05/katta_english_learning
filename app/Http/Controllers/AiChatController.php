<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AiChatController extends Controller
{
    // Placeholder - nội dung đầy đủ của module này sẽ được xây dựng ở bước tương ứng (xem mục 18)
    public function index(Request $request)
    {
        return view('pages.placeholder', [
            'title' => __('nav.ai_chat'),
            'icon' => 'message-circle',
        ]);
    }
}
