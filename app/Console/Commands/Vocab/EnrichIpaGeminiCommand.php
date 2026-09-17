<?php

namespace App\Console\Commands\Vocab;

use App\Models\Vocabulary;
use App\Services\GeminiService;
use Illuminate\Console\Command;

// Sinh phiên âm IPA cho từ vựng còn thiếu bằng Gemini API - thay thế Free Dictionary API
// (bị chặn kết nối từ môi trường dev này, xem lịch sử debug ở EnrichIpaCommand).
class EnrichIpaGeminiCommand extends Command
{
    protected $signature = 'vocab:enrich-ipa-gemini
        {--key= : Gemini API key (hoặc đặt GEMINI_API_KEY trong .env)}
        {--model=gemini-3.5-flash-lite : Model Gemini dùng để sinh IPA}
        {--batch=50 : Số từ xử lý mỗi lần gọi API}
        {--delay=4.5 : Số giây chờ giữa các lần gọi (free tier giới hạn ~15 request/phút)}
        {--level= : Chỉ xử lý riêng 1 cấp độ, vd --level=A1}
        {--all : Sinh lại cho cả từ đã có IPA (mặc định chỉ xử lý từ còn thiếu)}';

    protected $description = 'Sinh phiên âm IPA cho từ vựng còn thiếu bằng Gemini API';

    public function handle(): int
    {
        $apiKey = $this->option('key') ?: config('services.gemini.key');

        if (! $apiKey) {
            $this->error('Thiếu Gemini API key. Truyền --key=... hoặc đặt GEMINI_API_KEY trong .env');
            return self::FAILURE;
        }

        $gemini = new GeminiService($apiKey, $this->option('model'));
        $batchSize = (int) $this->option('batch');
        $delay = (float) $this->option('delay');

        $query = Vocabulary::query();
        if (! $this->option('all')) {
            $query->whereNull('ipa');
        }
        if ($level = $this->option('level')) {
            $query->where('level', $level);
        }

        $words = $query->get(['id', 'word']);
        $total = $words->count();

        if ($total === 0) {
            $this->info('Không có từ nào cần xử lý.');
            return self::SUCCESS;
        }

        $this->info("Bắt đầu sinh IPA cho $total từ qua Gemini (batch $batchSize)...");
        $bar = $this->output->createProgressBar($total);
        $failedBatches = 0;
        $onRetry = function ($delaySec, $attempt, $max) {
            $this->newLine();
            $this->warn("Bị rate limit (429) - chờ {$delaySec}s rồi thử lại (lần $attempt/$max)...");
        };

        foreach ($words->chunk($batchSize) as $chunk) {
            $chunk = $chunk->values();
            $wordList = $chunk->map(fn ($v, $i) => ($i + 1) . '. ' . $v->word)->implode("\n");

            $prompt = <<<PROMPT
            Bạn là từ điển phát âm tiếng Anh (Anh-Anh, British English). Cho phiên âm IPA chuẩn
            (có dấu gạch chéo, ví dụ "/əˈbaʊt/") cho từng từ tiếng Anh sau.

            Trả về CHÍNH XÁC 1 mảng JSON các chuỗi, đúng số lượng và đúng thứ tự như danh sách dưới đây,
            không thêm bất kỳ chữ nào khác ngoài mảng JSON.

            Danh sách từ:
            {$wordList}
            PROMPT;

            $ipas = $gemini->generateStringArray($prompt, 3, $onRetry);

            if ($ipas === null) {
                $failedBatches++;
                $bar->advance($chunk->count());
                continue;
            }

            foreach ($chunk as $i => $vocab) {
                $ipa = $ipas[$i] ?? null;
                if ($ipa) {
                    Vocabulary::where('id', $vocab->id)->update(['ipa' => $ipa]);
                }
            }

            $bar->advance($chunk->count());
            usleep((int) ($delay * 1_000_000));
        }

        $bar->finish();
        $this->newLine(2);

        if ($failedBatches > 0) {
            $this->warn("$failedBatches lô bị lỗi (có thể do rate limit) - chạy lại lệnh này (chỉ xử lý từ còn thiếu) để làm nốt.");
        }

        $this->info('Xong.');

        return self::SUCCESS;
    }
}
