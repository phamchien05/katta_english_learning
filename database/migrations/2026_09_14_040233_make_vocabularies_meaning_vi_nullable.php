<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // meaning_vi cho phép null tạm thời: import từ vựng (CEFR-J dataset) trước,
    // dịch nghĩa tiếng Việt qua Gemini API ở bước sau (xem VocabTranslateCommand).
    public function up(): void
    {
        Schema::table('vocabularies', function (Blueprint $table) {
            $table->string('meaning_vi')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('vocabularies', function (Blueprint $table) {
            $table->string('meaning_vi')->nullable(false)->change();
        });
    }
};
