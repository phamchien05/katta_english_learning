<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Bài đọc IELTS-style, nhóm theo chủ đề (mục 6). Nội dung + câu hỏi đều do Gemini sinh,
    // kho tự bù giống module Dịch (xem ReadingController::replenish).
    public function up(): void
    {
        Schema::create('reading_passages', function (Blueprint $table) {
            $table->id();
            $table->string('topic', 30); // business|environment|general|health|history|science|society|technology
            $table->string('title');
            $table->string('level', 2)->nullable(); // A1..C1, có thể null với bài chủ đề chuyên sâu
            $table->text('content');
            $table->timestamps();

            $table->index('topic');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reading_passages');
    }
};
