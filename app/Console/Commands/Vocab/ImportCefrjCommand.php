<?php

namespace App\Console\Commands\Vocab;

use App\Models\UserVocabProgress;
use App\Models\Vocabulary;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

// Import bộ từ vựng CEFR-J (A1-B2) + Octanove C1/C2 - dữ liệu nghiên cứu thật,
// nguồn: https://github.com/openlanguageprofiles/olp-en-cefrj (miễn phí, cần ghi nguồn).
// Thay thế 300 từ soạn tay bằng ~9.900 từ đã gắn cấp CEFR chính xác.
class ImportCefrjCommand extends Command
{
    protected $signature = 'vocab:import-cefrj {--fresh : Xoá hết từ vựng cũ trước khi import}';

    protected $description = 'Import bộ từ vựng CEFR-J + Octanove C1/C2 vào bảng vocabularies';

    // Ánh xạ part-of-speech gốc (tiếng Anh) -> viết tắt hiển thị trong quiz
    protected array $posMap = [
        'noun' => 'N.',
        'verb' => 'V.',
        'be-verb' => 'V.',
        'do-verb' => 'V.',
        'have-verb' => 'V.',
        'vern' => 'V.', // lỗi chính tả trong dataset gốc
        'adjective' => 'Adj.',
        'adverb' => 'Adv.',
        'preposition' => 'Prep.',
        'infinitive-to' => 'Prep.',
        'conjunction' => 'Conj.',
        'pronoun' => 'Pron.',
        'determiner' => 'Det.',
        'modal auxiliary' => 'Modal',
        'interjection' => 'Interj.',
        'number' => 'Num.',
    ];

    public function handle(): int
    {
        $files = [
            database_path('data/cefrj-vocabulary-profile-1.5.csv'),
            database_path('data/octanove-vocabulary-profile-c1c2-1.0.csv'),
        ];

        foreach ($files as $file) {
            if (! file_exists($file)) {
                $this->error("Không tìm thấy file: $file");
                return self::FAILURE;
            }
        }

        if ($this->option('fresh')) {
            $this->warn('Xoá toàn bộ dữ liệu từ vựng cũ...');
            UserVocabProgress::query()->delete();
            Vocabulary::query()->delete();
        }

        $rows = [];
        foreach ($files as $file) {
            $handle = fopen($file, 'r');
            $header = fgetcsv($handle); // bỏ dòng tiêu đề
            while (($line = fgetcsv($handle)) !== false) {
                if (count($line) < 3) {
                    continue;
                }
                [$headword, $pos, $level] = [$line[0], $line[1], $line[2]];
                $level = trim($level);

                if (! in_array($level, ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'])) {
                    continue;
                }

                // Từ có nhiều biến thể cách nhau bởi "/" -> lấy dạng đầu tiên cho gọn hiển thị
                $word = trim(explode('/', $headword)[0]);
                if ($word === '') {
                    continue;
                }

                $rows[] = [
                    'level' => $level,
                    'word' => $word,
                    'part_of_speech' => $this->posMap[trim($pos)] ?? null,
                    'ipa' => null, // sẽ điền ở bước vocab:enrich-ipa
                    'meaning_vi' => null, // sẽ điền ở bước vocab:translate-gemini
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            fclose($handle);
        }

        $this->info('Đang insert ' . count($rows) . ' từ vào database...');

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('vocabularies')->insert($chunk);
        }

        $counts = Vocabulary::selectRaw('level, COUNT(*) as total')->groupBy('level')->orderBy('level')->pluck('total', 'level');
        foreach ($counts as $level => $total) {
            $this->line("  $level: $total từ");
        }

        $this->info('Import xong.');

        return self::SUCCESS;
    }
}
