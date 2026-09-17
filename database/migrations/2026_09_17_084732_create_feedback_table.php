<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Module Nhận xét (mục 13) - user gửi phản hồi/báo lỗi kèm ảnh chụp màn hình tuỳ chọn.
    // status luôn ở "pending" cho tới khi có trang admin (đổi tay qua phpMyAdmin trong lúc chưa làm).
    public function up(): void
    {
        Schema::create('feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('category', 20); // bug|suggestion|other
            $table->string('title');
            $table->text('message');
            $table->string('screenshot_path')->nullable(); // đường dẫn trên disk 'public'
            $table->string('context_url')->nullable(); // trang user đang đứng trước khi vào gửi feedback
            $table->string('status', 20)->default('pending'); // pending|in_review|resolved|rejected
            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback');
    }
};
