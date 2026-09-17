<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Bài nghe IELTS-style, nhóm theo chủ đề (mục 8). Không lưu file audio thật - "transcript" được
    // trình duyệt tự đọc thành giọng nói bằng Web Speech API (miễn phí, không cần lưu/host file mp3).
    // Kho tự bù giống hệt module Đọc hiểu (xem ListeningController::replenish).
    public function up(): void
    {
        Schema::create('listening_passages', function (Blueprint $table) {
            $table->id();
            $table->string('topic', 30); // general|everyday_conversation|social_monologue|academic_discussion|academic_lecture
            $table->string('title');
            $table->string('level', 2)->nullable(); // A1..C1, null với chủ đề đã gắn cứng 1 cấp độ
            $table->text('transcript'); // lời thoại đầy đủ, đọc lên tự nhiên (có nhãn người nói cho hội thoại)
            $table->timestamps();

            $table->index('topic');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listening_passages');
    }
};
