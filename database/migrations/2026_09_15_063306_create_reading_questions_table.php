<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Câu hỏi đọc hiểu cho từng bài (mục 6). correct_answer lưu dạng text:
    // - fill/mcq/boolean: 1 chuỗi đáp án đúng
    // - multi (chọn nhiều): JSON mảng các đáp án đúng
    public function up(): void
    {
        Schema::create('reading_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('passage_id')->constrained('reading_passages')->cascadeOnDelete();
            $table->string('type', 10); // fill|mcq|boolean|multi
            $table->text('question');
            $table->json('options')->nullable(); // cho mcq/multi
            $table->text('correct_answer');
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reading_questions');
    }
};
