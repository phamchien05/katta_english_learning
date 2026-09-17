<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Cây chủ đề ngữ pháp phân cấp (mục "Xem Lý thuyết") - parent_id null = mục cha (chỉ là mục lục),
    // có content = trang bài học thật (thường là node lá, nhưng không bắt buộc).
    public function up(): void
    {
        Schema::create('grammar_topics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('grammar_topics')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('content')->nullable(); // HTML: đoạn văn + <strong> giải thích + <em> ví dụ
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->index('parent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grammar_topics');
    }
};
