<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Trước đây bài Từ vựng chỉ lưu StudySession (không có điểm/chi tiết) - bảng này lưu lại đầy đủ
    // kết quả từng lần làm bài để hiển thị ở "Tiến trình" (mục 10) giống Đọc hiểu/Ngữ pháp/Dịch.
    public function up(): void
    {
        Schema::create('vocabulary_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('level', 2);
            $table->unsignedInteger('score');
            $table->unsignedInteger('total');
            // Mảng {word, ipa, user_answer, correct_answer, is_correct} theo đúng thứ tự đã làm
            $table->json('results');
            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vocabulary_submissions');
    }
};
