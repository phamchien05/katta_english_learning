<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Đoạn văn dùng để luyện dịch (mục 5) - sẽ seed nội dung ở bước xây Module Dịch
    public function up(): void
    {
        Schema::create('translation_passages', function (Blueprint $table) {
            $table->id();
            $table->string('level', 2); // A1..C1
            $table->string('direction', 10)->default('en_vi'); // en_vi | vi_en
            $table->text('source_text');
            $table->timestamps();

            $table->index(['level', 'direction']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translation_passages');
    }
};
