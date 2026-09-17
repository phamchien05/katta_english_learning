<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Câu hỏi nghe hiểu cho từng bài (mục 8) - cùng cấu trúc với reading_questions (fill/mcq/boolean/multi)
    public function up(): void
    {
        Schema::create('listening_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('passage_id')->constrained('listening_passages')->cascadeOnDelete();
            $table->string('type', 10); // fill|mcq|boolean|multi
            $table->text('question');
            $table->json('options')->nullable(); // cho mcq/multi
            $table->json('correct_answer');
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listening_questions');
    }
};
