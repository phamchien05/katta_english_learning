<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Middleware đọc ngôn ngữ đã chọn và set locale cho app. Thứ tự ưu tiên: session của phiên hiện tại
// (đổi ngay lập tức qua nút EN/VI ở header) -> ngôn ngữ đã lưu lâu dài trong tài khoản (mục 14, Cài
// đặt) -> mặc định tiếng Anh (theo APP_LOCALE trong .env) nếu chưa từng chọn gì.
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = session('locale') ?? $request->user()?->locale ?? config('app.locale');

        if (in_array($locale, ['vi', 'en'])) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
