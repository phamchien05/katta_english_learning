<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Nội dung bài học ngữ pháp phải theo đúng ngôn ngữ hiển thị của app (mặc định tiếng Anh, ai muốn
    // xem tiếng Việt thì tự đổi bằng nút EN/VI ở header) - tách "content" cũ (tiếng Việt) thành 2 cột
    // riêng theo ngôn ngữ thay vì trộn chung 1 cột. Không dùng renameColumn() để tránh phụ thuộc
    // doctrine/dbal (chưa cài trong project này).
    public function up(): void
    {
        Schema::table('grammar_topics', function (Blueprint $table) {
            $table->longText('content_vi')->nullable()->after('content');
            $table->longText('content_en')->nullable()->after('content_vi');
        });

        DB::statement('UPDATE grammar_topics SET content_vi = content');

        Schema::table('grammar_topics', function (Blueprint $table) {
            $table->dropColumn('content');
        });
    }

    public function down(): void
    {
        Schema::table('grammar_topics', function (Blueprint $table) {
            $table->longText('content')->nullable();
        });

        DB::statement('UPDATE grammar_topics SET content = content_vi');

        Schema::table('grammar_topics', function (Blueprint $table) {
            $table->dropColumn(['content_vi', 'content_en']);
        });
    }
};
