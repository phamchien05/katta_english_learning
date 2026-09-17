<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Bài dịch user đã nộp + điểm/nhận xét do AI chấm (dùng cho khung "Thống kê dịch thuật" ở Trang chủ)
    public function up(): void
    {
        Schema::create('translation_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('passage_id')->constrained('translation_passages')->cascadeOnDelete();
            $table->text('user_translation');
            $table->unsignedTinyInteger('ai_score')->nullable(); // điểm 0-100
            $table->text('ai_feedback')->nullable();
            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translation_submissions');
    }
};
