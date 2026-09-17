<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Module Cài đặt (mục 14): user tự nhập API key Gemini riêng (đúng ghi chú để lại trong .env từ
    // đầu dự án) - mã hoá khi lưu. "locale" lưu ngôn ngữ hiển thị ưa thích lâu dài (khác session hiện
    // tại, vốn mất khi đổi trình duyệt/xoá cookie).
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('gemini_api_key')->nullable()->after('remember_token');
            $table->string('locale', 5)->nullable()->after('gemini_api_key');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['gemini_api_key', 'locale']);
        });
    }
};
