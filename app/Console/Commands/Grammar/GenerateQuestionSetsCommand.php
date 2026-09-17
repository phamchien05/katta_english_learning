<?php

namespace App\Console\Commands\Grammar;

use App\Models\GrammarQuestionSet;
use App\Services\GeminiService;
use App\Services\GrammarQuestionSetGenerator;
use Illuminate\Console\Command;

// Sinh bộ đề luyện tập ngữ pháp bằng Gemini (Phần B) - dùng để nạp sẵn 3 bộ/chủ đề vào kho ban đầu.
// Sau đó kho tự bù (mỗi lần user lấy 1 bộ ra làm, hệ thống tự sinh thêm 1 bộ mới - xem GrammarController).
class GenerateQuestionSetsCommand extends Command
{
    protected $signature = 'grammar:generate-sets
        {--key= : Gemini API key (hoặc đặt GEMINI_API_KEY trong .env)}
        {--model=gemini-3.5-flash-lite : Model Gemini dùng để sinh đề}
        {--per-topic=3 : Số bộ đề cần thêm cho mỗi chủ đề}
        {--delay=8 : Số giây chờ giữa các lần gọi (mỗi bộ 30-50 câu, tốn nhiều token hơn)}
        {--topic= : Chỉ sinh riêng 1 chủ đề, vd --topic=tenses}';

    protected $description = 'Sinh bộ đề luyện tập ngữ pháp (kho đề) bằng Gemini API cho các chủ đề';

    public function handle(): int
    {
        $apiKey = $this->option('key') ?: config('services.gemini.key');
        if (! $apiKey) {
            $this->error('Thiếu Gemini API key. Truyền --key=... hoặc đặt GEMINI_API_KEY trong .env');
            return self::FAILURE;
        }

        // Timeout dài hơn mặc định (90s) - mỗi bộ 30-50 câu hỏi phức tạp hơn nhiều so với các module khác
        $generator = new GrammarQuestionSetGenerator(new GeminiService($apiKey, $this->option('model'), timeoutSeconds: 90));
        $delay = (float) $this->option('delay');
        $topicOption = $this->option('topic');
        $topics = $topicOption ? [$topicOption] : array_keys(GrammarQuestionSetGenerator::TOPICS);

        $jobs = [];
        foreach ($topics as $topic) {
            for ($i = 0; $i < (int) $this->option('per-topic'); $i++) {
                $jobs[] = $topic;
            }
        }

        $this->info('Sẽ sinh ' . count($jobs) . ' bộ đề (mỗi bộ ~' . GrammarQuestionSetGenerator::QUESTION_COUNT . ' câu)...');
        $bar = $this->output->createProgressBar(count($jobs));
        $created = 0;
        $failed = 0;
        $onRetry = function ($delaySec, $attempt, $max) {
            $this->newLine();
            $this->warn("Bị rate limit (429) - chờ {$delaySec}s rồi thử lại (lần $attempt/$max)...");
        };

        foreach ($jobs as $topicKey) {
            $result = $generator->generate($topicKey, 3, $onRetry);

            if ($result === null) {
                $this->newLine();
                $this->warn("Lỗi sinh bộ đề cho {$topicKey} - bỏ qua.");
                $failed++;
                $bar->advance();
                usleep((int) ($delay * 1_000_000));
                continue;
            }

            $set = GrammarQuestionSet::create(['topic_key' => $topicKey, 'status' => 'available']);

            foreach ($result['questions'] as $order => $q) {
                $set->questions()->create([
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
        $this->info("Xong: đã tạo $created bộ đề" . ($failed > 0 ? ", $failed bộ lỗi (bỏ qua)." : '.'));

        return self::SUCCESS;
    }
}
