<?php

namespace App\Console\Commands\Translate;

use App\Models\TranslationPassage;
use App\Services\GeminiService;
use App\Services\TranslationPassageGenerator;
use Illuminate\Console\Command;

// Sinh thêm đoạn văn luyện dịch bằng Gemini API - bổ sung số lượng cho mỗi cấp độ/chiều dịch (mục 5).
class GeneratePassagesCommand extends Command
{
    protected $signature = 'translate:generate-passages
        {--key= : Gemini API key (hoặc đặt GEMINI_API_KEY trong .env)}
        {--model=gemini-3.5-flash-lite : Model Gemini dùng để sinh đoạn văn}
        {--en-vi=10 : Số đoạn EN->VI cần thêm cho mỗi cấp độ}
        {--vi-en=7 : Số đoạn VI->EN cần thêm cho mỗi cấp độ}
        {--batch=5 : Số đoạn sinh mỗi lần gọi API}
        {--delay=4.5 : Số giây chờ giữa các lần gọi (free tier giới hạn ~15 request/phút)}
        {--level= : Chỉ sinh riêng 1 cấp độ, vd --level=A1}';

    protected $description = 'Sinh thêm đoạn văn luyện dịch bằng Gemini API cho các cấp độ/chiều dịch';

    public function handle(): int
    {
        $apiKey = $this->option('key') ?: config('services.gemini.key');
        if (! $apiKey) {
            $this->error('Thiếu Gemini API key. Truyền --key=... hoặc đặt GEMINI_API_KEY trong .env');
            return self::FAILURE;
        }

        $generator = new TranslationPassageGenerator(new GeminiService($apiKey, $this->option('model')));
        $batchSize = (int) $this->option('batch');
        $delay = (float) $this->option('delay');
        $levels = $this->option('level') ? [$this->option('level')] : ['A1', 'A2', 'B1', 'B2', 'C1'];

        $jobs = [];
        foreach ($levels as $level) {
            if ((int) $this->option('en-vi') > 0) {
                $jobs[] = ['level' => $level, 'direction' => 'en_vi', 'count' => (int) $this->option('en-vi')];
            }
            if ((int) $this->option('vi-en') > 0) {
                $jobs[] = ['level' => $level, 'direction' => 'vi_en', 'count' => (int) $this->option('vi-en')];
            }
        }

        $totalWanted = array_sum(array_column($jobs, 'count'));
        $this->info("Sẽ sinh khoảng $totalWanted đoạn văn mới...");
        $bar = $this->output->createProgressBar($totalWanted);
        $created = 0;
        $onRetry = function ($delaySec, $attempt, $max) {
            $this->newLine();
            $this->warn("Bị rate limit (429) - chờ {$delaySec}s rồi thử lại (lần $attempt/$max)...");
        };

        foreach ($jobs as $job) {
            $remaining = $job['count'];

            while ($remaining > 0) {
                $count = min($batchSize, $remaining);
                $passages = $generator->generate($job['level'], $job['direction'], $count, 3, $onRetry);

                if (empty($passages)) {
                    $this->newLine();
                    $this->warn("Lỗi sinh đoạn văn cho {$job['level']} ({$job['direction']}) - bỏ qua lô này.");
                    $remaining -= $count;
                    $bar->advance($count);
                    continue;
                }

                foreach ($passages as $text) {
                    $text = trim($text);
                    if ($text === '') {
                        continue;
                    }
                    TranslationPassage::create([
                        'level' => $job['level'],
                        'direction' => $job['direction'],
                        'source_text' => $text,
                    ]);
                    $created++;
                }

                $bar->advance($count);
                $remaining -= $count;
                usleep((int) ($delay * 1_000_000));
            }
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Xong: đã tạo $created đoạn văn mới.");

        return self::SUCCESS;
    }
}
