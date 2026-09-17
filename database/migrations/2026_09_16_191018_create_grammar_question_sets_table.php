<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // "Kho đề" ngữ pháp (Phần B, mục 2): mỗi bộ đề đi qua vòng đời
    // available (chờ trong kho) -> in_progress (đã gán cho 1 user) -> completed (đã nộp, có điểm).
    // Bộ completed KHÔNG BAO GIỜ quay lại kho - mỗi lần làm luôn là 1 bộ mới, không lặp lại.
    public function up(): void
    {
        Schema::create('grammar_question_sets', function (Blueprint $table) {
            $table->id();
            // Khớp đúng slug của 5 nhánh gốc trong grammar_topics (parts-of-speech, tenses,
            // sentence-structures, question-forms, common-structures) - dùng chung 1 khoá cho Phần C sau này
            $table->string('topic_key', 40);
            $table->string('status', 20)->default('available'); // available|in_progress|completed
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('score')->nullable();
            $table->unsignedInteger('total')->nullable();
            $table->json('answers')->nullable(); // question_id => câu trả lời user đã nhập/chọn (lưu khi completed)
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['topic_key', 'status']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grammar_question_sets');
    }
};
