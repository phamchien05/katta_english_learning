<?php

namespace App\Console\Commands\Reading;

use App\Models\ReadingPassage;
use App\Models\ReadingQuestion;
use App\Services\GeminiService;
use App\Services\ReadingPassageGenerator;
use Illuminate\Console\Command;

// Sinh bài đọc + câu hỏi bằng Gemini cho các chủ đề (mục 6). Mỗi bài = 1 lần gọi API (không batch
// nhiều bài/lần như Vocab/Translate, vì cấu trúc câu hỏi phức tạp hơn nên cần chắc chắn hơn).
class GeneratePassagesCommand extends Command
{
    protected $signature = 'reading:generate-passages
        {--key= : Gemini API key (hoặc đặt GEMINI_API_KEY trong .env)}
        {--model=gemini-3.5-flash-lite : Model Gemini dùng để sinh bài đọc}
        {--per-topic=10 : Số bài cần thêm cho mỗi chủ đề (trừ "general" sinh theo cấp độ)}
        {--delay=5 : Số giây chờ giữa các lần gọi (free tier giới hạn ~15 request/phút)}
        {--topic= : Chỉ sinh riêng 1 chủ đề, vd --topic=technology}
        {--level=B2 : Cấp độ dùng cho các chủ đề thường (trừ "general")}';

    protected $description = 'Sinh bài đọc + câu hỏi bằng Gemini API cho các chủ đề';

    public function handle(): int
    {
        $apiKey = $this->option('key') ?: config('services.gemini.key');
        if (! $apiKey) {
            $this->error('Thiếu Gemini API key. Truyền --key=... hoặc đặt GEMINI_API_KEY trong .env');
            return self::FAILURE;
        }

        $generator = new ReadingPassageGenerator(new GeminiService($apiKey, $this->option('model')));
        $delay = (float) $this->option('delay');
        $topics = $this->option('topic') ? [$this->option('topic')] : ReadingPassageGenerator::TOPICS;

        // "general" tổ chức theo cấp CEFR (giống Từ vựng/Dịch) - mỗi cấp cũng là 1 kho nhiều bài,
        // random khi bấm vào + tự bù, không phải 1 bài cố định/cấp.
        $jobs = [];
        foreach ($topics as $topic) {
            if ($topic === 'general') {
                foreach (['A1', 'A2', 'B1', 'B2', 'C1'] as $level) {
                    for ($i = 0; $i < (int) $this->option('per-topic'); $i++) {
                        $jobs[] = ['topic' => $topic, 'level' => $level];
                    }
                }
            } else {
                for ($i = 0; $i < (int) $this->option('per-topic'); $i++) {
                    $jobs[] = ['topic' => $topic, 'level' => $this->option('level')];
                }
            }
        }

        $this->info('Sẽ sinh ' . count($jobs) . ' bài đọc...');
        $bar = $this->output->createProgressBar(count($jobs));
        $created = 0;
        $failed = 0;
        $onRetry = function ($delaySec, $attempt, $max) {
            $this->newLine();
            $this->warn("Bị rate limit (429) - chờ {$delaySec}s rồi thử lại (lần $attempt/$max)...");
        };

        foreach ($jobs as $job) {
            $result = $generator->generate($job['topic'], $job['level'], 3, $onRetry);

            if ($result === null) {
                $this->newLine();
                $this->warn("Lỗi sinh bài đọc cho {$job['topic']} ({$job['level']}) - bỏ qua.");
                $failed++;
                $bar->advance();
                usleep((int) ($delay * 1_000_000));
                continue;
            }

            $passage = ReadingPassage::create([
                'topic' => $job['topic'],
                'title' => $result['title'],
                'level' => $job['level'],
                'content' => $result['content'],
            ]);

            foreach ($result['questions'] as $order => $q) {
                ReadingQuestion::create([
                    'passage_id' => $passage->id,
                    'type' => $q['type'],
                    'question' => $q['question'],
                    'options' => $q['options'] ?? [],
                    'correct_answer' => $q['correct_answer'],
                    'order' => $order,
                ]);
            }

            $created++;
            $bar->advance();
            usleep((int) ($delay * 1_000_000));
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Xong: đã tạo $created bài đọc" . ($failed > 0 ? ", $failed bài lỗi (bỏ qua)." : '.'));

        return self::SUCCESS;
    }
}
