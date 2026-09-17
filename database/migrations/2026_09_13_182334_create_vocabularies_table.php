<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Bảng từ vựng dùng cho module Từ vựng (mục 4) - cấp độ A1..C2
    public function up(): void
    {
        Schema::create('vocabularies', function (Blueprint $table) {
            $table->id();
            $table->string('level', 2); // A1, A2, B1, B2, C1, C2
            $table->string('word');
            $table->string('part_of_speech', 20)->nullable(); // N., V., Adj. ...
            $table->string('ipa')->nullable();
            $table->string('meaning_vi');
            $table->timestamps();

            $table->index('level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vocabularies');
    }
};
