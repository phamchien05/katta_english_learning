<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SettingController extends Controller
{
    // Trang Cài đặt (mục 14): tuỳ chỉnh app (ngôn ngữ mặc định, API key Gemini riêng) - KHÔNG trùng
    // với Profile của Breeze (đổi tên/email/mật khẩu/xoá tài khoản đã có sẵn ở route /profile)
    public function index(Request $request)
    {
        return view('pages.settings.index', [
            'title' => __('nav.settings'),
            'icon' => 'settings',
        ]);
    }
}
