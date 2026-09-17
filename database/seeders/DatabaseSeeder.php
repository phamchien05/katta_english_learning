<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Dữ liệu từ vựng không seed ở đây nữa - chạy các lệnh sau (1 lần, xem README):
        //   php artisan vocab:import-cefrj --fresh   (import ~9.900 từ từ bộ CEFR-J)
        //   php artisan vocab:enrich-ipa-gemini       (lấy IPA qua Gemini)
        //   php artisan vocab:translate-gemini        (dịch nghĩa tiếng Việt qua Gemini)

        $this->call([
            TranslationPassageSeeder::class,
        ]);
    }
}
