<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Cờ chống bù kho lặp lại: replenish chỉ được gọi ngầm từ JS ĐÚNG 1 LẦN cho mỗi lần 1 bộ đề chuyển
    // available -> in_progress, kể cả khi user tải lại (refresh) trang làm bài nhiều lần.
    public function up(): void
    {
        Schema::table('grammar_question_sets', function (Blueprint $table) {
            $table->boolean('replenish_dispatched')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('grammar_question_sets', function (Blueprint $table) {
            $table->dropColumn('replenish_dispatched');
        });
    }
};
