<?php

namespace App\Console\Commands\Vocab;

use App\Models\Vocabulary;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

// Lấy phiên âm IPA thật cho từng từ qua Free Dictionary API (miễn phí, không cần key).
// Lưu ý: gọi tuần tự (không dùng Http::pool) vì môi trường này bị lỗi cURL đa luồng
// (curl_multi timeout 0 bytes received dù server vẫn phản hồi tốt khi gọi đơn lẻ).
class EnrichIpaCommand extends Command
{
    protected $signature = 'vocab:enrich-ipa {--only-missing : Chỉ xử lý từ chưa có IPA}';

    protected $description = 'Lấy IPA cho các từ trong bảng vocabularies qua Free Dictionary API';

    public function handle(): int
    {
        $query = Vocabulary::query();
        if ($this->option('only-missing')) {
            $query->whereNull('ipa');
        }

        $words = $query->get(['id', 'word']);
        $total = $words->count();
        $found = 0;
        $notFound = 0;

        $this->info("Bắt đầu tra IPA cho $total từ (tuần tự)...");
        $bar = $this->output->createProgressBar($total);

        foreach ($words as $vocab) {
            $ipa = null;

            try {
                $response = Http::timeout(6)->get('https://api.dictionaryapi.dev/api/v2/entries/en/' . urlencode($vocab->word));
                if ($response->ok()) {
                    $ipa = $this->extractIpa($response->json());
                }
            } catch (\Throwable $e) {
                // bỏ qua, coi như không tìm thấy
            }

            if ($ipa) {
                Vocabulary::where('id', $vocab->id)->update(['ipa' => $ipa]);
                $found++;
            } else {
                $notFound++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Xong: $found từ có IPA, $notFound từ không tìm thấy (giữ nguyên/null).");

        return self::SUCCESS;
    }

    protected function extractIpa(mixed $entries): ?string
    {
        if (! is_array($entries)) {
            return null;
        }

        foreach ($entries as $entry) {
            if (! empty($entry['phonetic'])) {
                return $entry['phonetic'];
            }
            foreach ($entry['phonetics'] ?? [] as $p) {
                if (! empty($p['text'])) {
                    return $p['text'];
                }
            }
        }

        return null;
    }
}
