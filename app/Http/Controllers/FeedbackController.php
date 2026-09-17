<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    // Trang Nhận xét (mục 13): tab "Gửi phản hồi" (form) + tab "Lịch sử" (các phản hồi đã gửi)
    public function index(Request $request)
    {
        $history = Feedback::where('user_id', $request->user()->id)->latest()->get();

        return view('pages.feedback.index', [
            'title' => __('nav.feedback'),
            'icon' => 'message-square',
            'history' => $history,
            // Trang user vừa đứng trước khi bấm vào "Nhận xét" ở sidebar - tự động gửi kèm làm ngữ cảnh,
            // không cần user tự gõ lại "lỗi xảy ra ở đâu"
            'contextUrl' => $request->headers->get('referer'),
        ]);
    }
}
