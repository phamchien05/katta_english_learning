<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Ghi nhận mỗi buổi học (bất kể module nào: từ vựng/nghe/đọc/ngữ pháp...) - dùng để tính
    // chuỗi ngày (streak), tổng thời gian học, biểu đồ 7 ngày, hoạt động gần đây ở Trang chủ/Thống kê.
    public function up(): void
    {
        Schema::create('study_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30); // vocabulary | translate | reading | grammar | listening ...
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->timestamp('completed_at');
            $table->timestamps();

            $table->index(['user_id', 'completed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_sessions');
    }
};
