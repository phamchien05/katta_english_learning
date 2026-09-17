<?php

namespace App\Console\Commands\Listening;

use App\Models\ListeningPassage;
use App\Models\ListeningQuestion;
use App\Services\GeminiService;
use App\Services\ListeningPassageGenerator;
use Illuminate\Console\Command;

// Sinh bài nghe + câu hỏi bằng Gemini cho các chủ đề (mục 8). Mỗi bài = 1 lần gọi API, giống Đọc hiểu.
class GeneratePassagesCommand extends Command
{
    protected $signature = 'listening:generate-passages
        {--key= : Gemini API key (hoặc đặt GEMINI_API_KEY trong .env)}
        {--model=gemini-3.5-flash-lite : Model Gemini dùng để sinh bài}
        {--per-topic=10 : Số bài cần thêm cho mỗi chủ đề (trừ "general" sinh theo cấp độ)}
        {--delay=5 : Số giây chờ giữa các lần gọi (free tier giới hạn ~15 request/phút)}
        {--topic= : Chỉ sinh riêng 1 chủ đề, vd --topic=academic_lecture}
        {--level=B2 : Cấp độ dùng cho "general" khi không dùng vòng lặp 5 cấp mặc định}';

    protected $description = 'Sinh bài nghe + câu hỏi bằng Gemini API cho các chủ đề';

    public function handle(): int
    {
        $apiKey = $this->option('key') ?: config('services.gemini.key');
        if (! $apiKey) {
            $this->error('Thiếu Gemini API key. Truyền --key=... hoặc đặt GEMINI_API_KEY trong .env');
            return self::FAILURE;
        }

        $generator = new ListeningPassageGenerator(new GeminiService($apiKey, $this->option('model')));
        $delay = (float) $this->option('delay');
        $topics = $this->option('topic') ? [$this->option('topic')] : ListeningPassageGenerator::TOPICS;

        // "general" tổ chức theo cấp CEFR (giống Đọc hiểu) - các chủ đề khác gắn cứng 1 cấp độ theo dạng bài
        $jobs = [];
        foreach ($topics as $topic) {
            if ($topic === 'general') {
                foreach (['A1', 'A2', 'B1', 'B2', 'C1'] as $level) {
                    for ($i = 0; $i < (int) $this->option('per-topic'); $i++) {
                        $jobs[] = ['topic' => $topic, 'level' => $level];
                    }
                }
            } else {
                $level = ListeningPassageGenerator::FIXED_LEVELS[$topic] ?? $this->option('level');
                for ($i = 0; $i < (int) $this->option('per-topic'); $i++) {
                    $jobs[] = ['topic' => $topic, 'level' => $level];
                }
            }
        }

        $this->info('Sẽ sinh ' . count($jobs) . ' bài nghe...');
        $bar = $this->output->createProgressBar(count($jobs));
        $created = 0;
        $failed = 0;
        $onRetry = function ($delaySec, $attempt, $max) {
            $this->newLine();
            $this->warn("Bị rate limit (429) - chờ {$delaySec}s rồi thử lại (lần $attempt/$max)...");
        };

        // Tiêu đề đã có sẵn trong DB + vừa tạo trong chính lượt chạy này - gộp cả 2 để AI không lặp lại
        // (bug thực tế đã gặp: chạy liên tiếp cùng topic/level ra 3 bài y hệt tiêu đề nhau)
        $titlesByBucket = [];
        $bucketKey = fn (array $job) => $job['topic'] === 'general' ? $job['topic'] . '.' . $job['level'] : $job['topic'];
        foreach ($jobs as $job) {
            $key = $bucketKey($job);
            if (! isset($titlesByBucket[$key])) {
                $titlesByBucket[$key] = ListeningPassage::where('topic', $job['topic'])
                    ->when($job['topic'] === 'general', fn ($q) => $q->where('level', $job['level']))
                    ->pluck('title')->all();
            }
        }

        foreach ($jobs as $job) {
            $key = $bucketKey($job);
            $result = $generator->generate($job['topic'], $job['level'], 3, $onRetry, $titlesByBucket[$key]);

            if ($result === null) {
                $this->newLine();
                $this->warn("Lỗi sinh bài nghe cho {$job['topic']} ({$job['level']}) - bỏ qua.");
                $failed++;
                $bar->advance();
                usleep((int) ($delay * 1_000_000));
                continue;
            }

            $passage = ListeningPassage::create([
                'topic' => $job['topic'],
                'title' => $result['title'],
                'level' => $job['topic'] === 'general' ? $job['level'] : null,
                'transcript' => $result['transcript'],
            ]);

            foreach ($result['questions'] as $order => $q) {
                ListeningQuestion::create([
                    'passage_id' => $passage->id,
                    'type' => $q['type'],
                    'question' => $q['question'],
                    'options' => $q['options'] ?? [],
                    'correct_answer' => $q['correct_answer'],
                    'order' => $order,
                ]);
            }

            $titlesByBucket[$key][] = $result['title'];

            $created++;
            $bar->advance();
            usleep((int) ($delay * 1_000_000));
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Xong: đã tạo $created bài nghe" . ($failed > 0 ? ", $failed bài lỗi (bỏ qua)." : '.'));

        return self::SUCCESS;
    }
}
