<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Câu hỏi của 1 bộ đề ngữ pháp (Phần B, mục 1.2): 3 dạng xen kẽ - fill/mcq/multi (không có boolean,
    // khác Đọc hiểu). correct_answer luôn là mảng JSON (1 phần tử cho fill/mcq, nhiều phần tử cho multi).
    public function up(): void
    {
        Schema::create('grammar_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('set_id')->constrained('grammar_question_sets')->cascadeOnDelete();
            $table->string('type', 10); // fill|mcq|multi
            $table->text('question');
            $table->json('options')->nullable(); // cho mcq/multi
            $table->json('correct_answer');
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grammar_questions');
    }
};
