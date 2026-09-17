<?php

use App\Http\Controllers\AiChatController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\GrammarController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\IdiomController;
use App\Http\Controllers\ListeningController;
use App\Http\Controllers\ProgressController;
use App\Http\Controllers\ReadingController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SpeakingController;
use App\Http\Controllers\StatisticController;
use App\Http\Controllers\TranslateController;
use App\Http\Controllers\VocabularyController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Đổi ngôn ngữ hiển thị (vi/en) - lưu vào session để đổi ngay, đồng thời lưu lâu dài vào tài khoản
// nếu đã đăng nhập (mục 14, Cài đặt) để không mất lựa chọn khi đổi trình duyệt/xoá cookie
Route::get('/lang/{locale}', function (Request $request, string $locale) {
    if (in_array($locale, ['vi', 'en'])) {
        session(['locale' => $locale]);
        $request->user()?->update(['locale' => $locale]);
    }
    return redirect()->back();
})->name('locale.switch');

// Toàn bộ route trong app đều yêu cầu đăng nhập (mục 17), trừ landing/login/register (đã có sẵn trong auth.php)
Route::middleware(['auth'])->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');

    Route::get('/vocabulary', [VocabularyController::class, 'index'])->name('vocabulary.index');
    Route::get('/vocabulary/{level}', [VocabularyController::class, 'test'])
        ->whereIn('level', VocabularyController::LEVELS)
        ->name('vocabulary.test');
    Route::get('/translate', [TranslateController::class, 'index'])->name('translate.index');
    Route::get('/translate/{level}', [TranslateController::class, 'practice'])
        ->whereIn('level', TranslateController::LEVELS)
        ->name('translate.practice');
    // Đổi chiều dịch EN->VI / VI->EN - lưu lựa chọn vào session, giống cơ chế đổi ngôn ngữ hiển thị
    Route::get('/translate-direction/{direction}', function (string $direction) {
        if (in_array($direction, ['en_vi', 'vi_en'])) {
            session(['translate_direction' => $direction]);
        }
        return redirect()->route('translate.index');
    })->whereIn('direction', ['en_vi', 'vi_en'])->name('translate.direction.switch');
    // Bù kho đoạn văn ngầm - gọi từ JS sau khi user lấy 1 đoạn ra làm (không chặn UI)
    Route::post('/translate-replenish', [TranslateController::class, 'replenish'])->name('translate.replenish');
    Route::get('/reading', [ReadingController::class, 'index'])->name('reading.index');
    Route::get('/reading/general/{level}', [ReadingController::class, 'general'])
        ->whereIn('level', ['A1', 'A2', 'B1', 'B2', 'C1'])
        ->name('reading.general');
    Route::get('/reading/{passage}', [ReadingController::class, 'show'])->name('reading.show');
    Route::post('/reading-replenish', [ReadingController::class, 'replenish'])->name('reading.replenish');
    Route::get('/grammar', [GrammarController::class, 'index'])->name('grammar.index');
    Route::get('/grammar/theory/{topic?}', [GrammarController::class, 'theory'])->name('grammar.theory');
    Route::get('/grammar/practice', [GrammarController::class, 'practice'])->name('grammar.practice');
    Route::get('/grammar/practice/set/{set}', [GrammarController::class, 'practiceShow'])->name('grammar.practice.show');
    Route::get('/grammar/practice/{topicKey}/start', [GrammarController::class, 'practiceStart'])->name('grammar.practice.start');
    Route::post('/grammar-practice-replenish', [GrammarController::class, 'practiceReplenish'])->name('grammar.practice.replenish');
    Route::get('/idioms', [IdiomController::class, 'index'])->name('idioms.index');
    Route::get('/listening', [ListeningController::class, 'index'])->name('listening.index');
    Route::get('/listening/general/{level}', [ListeningController::class, 'general'])
        ->whereIn('level', ['A1', 'A2', 'B1', 'B2', 'C1'])
        ->name('listening.general');
    Route::get('/listening/{passage}', [ListeningController::class, 'show'])->name('listening.show');
    Route::post('/listening-replenish', [ListeningController::class, 'replenish'])->name('listening.replenish');
    Route::get('/speaking', [SpeakingController::class, 'index'])->name('speaking.index');
    Route::get('/ai-chat', [AiChatController::class, 'index'])->name('ai-chat.index');
    Route::get('/progress', [ProgressController::class, 'index'])->name('progress.index');
    Route::get('/statistics', [StatisticController::class, 'index'])->name('statistics.index');
    Route::get('/feedback', [FeedbackController::class, 'index'])->name('feedback.index');
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');

    Route::view('profile', 'profile')->name('profile');

    // Đăng xuất - sidebar/header là Blade tĩnh (không phải Livewire component) nên dùng route POST thường
    Route::post('/logout', function (Request $request) {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    })->name('logout');
});

require __DIR__.'/auth.php';
