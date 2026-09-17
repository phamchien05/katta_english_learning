<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Theo dõi tiến trình học từ vựng của từng user (đã thuộc hay chưa, lần ôn gần nhất)
    public function up(): void
    {
        Schema::create('user_vocab_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vocabulary_id')->constrained('vocabularies')->cascadeOnDelete();
            $table->boolean('is_mastered')->default(false);
            $table->timestamp('last_reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'vocabulary_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_vocab_progress');
    }
};
