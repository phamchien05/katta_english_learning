<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Lịch sử làm bài đọc của user - dùng cho tab "Đã đọc" (điểm + nút Làm lại), giống translation_submissions
    public function up(): void
    {
        Schema::create('reading_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('passage_id')->constrained('reading_passages')->cascadeOnDelete();
            $table->unsignedTinyInteger('score'); // số câu đúng
            $table->unsignedTinyInteger('total'); // tổng số câu
            $table->json('answers'); // question_id => câu trả lời user đã nhập/chọn
            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reading_submissions');
    }
};
