<?php

namespace App\Console\Commands\Vocab;

use App\Models\Vocabulary;
use App\Services\GeminiService;
use Illuminate\Console\Command;

// Dịch nghĩa tiếng Việt cho các từ vựng còn thiếu (meaning_vi = null) bằng Gemini API.
// Gọi theo lô (mặc định 50 từ/lần) để giảm số request, ép định dạng JSON để parse an toàn.
class TranslateGeminiCommand extends Command
{
    protected $signature = 'vocab:translate-gemini
        {--key= : Gemini API key (hoặc đặt GEMINI_API_KEY trong .env)}
        {--model=gemini-3.5-flash-lite : Model Gemini dùng để dịch}
        {--batch=50 : Số từ dịch mỗi lần gọi API}
        {--delay=4.5 : Số giây chờ giữa các lần gọi (free tier giới hạn ~15 request/phút)}
        {--level= : Chỉ dịch riêng 1 cấp độ, vd --level=A1}';

    protected $description = 'Dịch nghĩa tiếng Việt cho từ vựng còn thiếu bằng Gemini API';

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

        $query = Vocabulary::whereNull('meaning_vi');
        if ($level = $this->option('level')) {
            $query->where('level', $level);
        }

        $words = $query->get(['id', 'word', 'part_of_speech']);
        $total = $words->count();

        if ($total === 0) {
            $this->info('Không có từ nào cần dịch.');
            return self::SUCCESS;
        }

        $this->info("Bắt đầu dịch $total từ qua Gemini (batch $batchSize)...");
        $bar = $this->output->createProgressBar($total);
        $failedBatches = 0;
        $onRetry = function ($delaySec, $attempt, $max) {
            $this->newLine();
            $this->warn("Bị rate limit (429) - chờ {$delaySec}s rồi thử lại (lần $attempt/$max)...");
        };

        foreach ($words->chunk($batchSize) as $chunk) {
            $chunk = $chunk->values();
            $wordList = $chunk->map(fn ($v, $i) => ($i + 1) . '. ' . $v->word . ($v->part_of_speech ? " ({$v->part_of_speech})" : ''))->implode("\n");

            $prompt = <<<PROMPT
            Bạn là từ điển Anh-Việt. Dịch nghĩa tiếng Việt NGẮN GỌN (1-5 chữ, không giải thích dài dòng)
            cho từng từ tiếng Anh sau, theo đúng loại từ ghi kèm (N.=danh từ, V.=động từ, Adj.=tính từ, Adv.=trạng từ...).
            Nếu 1 từ có nhiều nghĩa phổ biến, có thể nối các nghĩa bằng dấu "/".

            Trả về CHÍNH XÁC 1 mảng JSON các chuỗi, đúng số lượng và đúng thứ tự như danh sách dưới đây,
            không thêm bất kỳ chữ nào khác ngoài mảng JSON.

            Danh sách từ:
            {$wordList}
            PROMPT;

            $meanings = $gemini->generateStringArray($prompt, 3, $onRetry);

            if ($meanings === null) {
                $failedBatches++;
                $bar->advance($chunk->count());
                continue;
            }

            foreach ($chunk as $i => $vocab) {
                $meaning = $meanings[$i] ?? null;
                if ($meaning) {
                    Vocabulary::where('id', $vocab->id)->update(['meaning_vi' => $meaning]);
                }
            }

            $bar->advance($chunk->count());
            usleep((int) ($delay * 1_000_000));
        }

        $bar->finish();
        $this->newLine(2);

        if ($failedBatches > 0) {
            $this->warn("$failedBatches lô bị lỗi (có thể do rate limit) - chạy lại lệnh này (chỉ xử lý từ còn thiếu) để dịch nốt.");
        }

        $this->info('Xong.');

        return self::SUCCESS;
    }
}
