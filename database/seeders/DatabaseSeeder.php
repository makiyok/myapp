<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Visit;
use App\Models\VisitMenu;
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
        User::firstWhere('email', 'test@example.com') ?? User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $facility = User::firstWhere('email', 'facility@example.com') ?? User::factory()->facility()->create([
            'name' => 'ひだまり苑',
            'email' => 'facility@example.com',
        ]);

        $visits = [
            [
                'title' => '秋のうたごえ慰問会',
                'description' => '入居者のみなさんと一緒に、童謡や昭和の歌を歌いましょう。',
                'venue' => 'ひだまり苑 1階ホール',
                'starts_at' => now()->addWeeks(2)->setTime(14, 0),
                'menus' => [['name' => '演奏ボランティア', 'price' => 1000, 'capacity' => 10], ['name' => '見学', 'price' => 500, 'capacity' => 20]],
            ],
            [
                'title' => '手づくりおやつの会',
                'description' => '季節の果物を使ったおやつを、入居者のみなさんと一緒に作って味わいます。',
                'venue' => 'ひだまり苑 食堂',
                'starts_at' => now()->addWeeks(3)->setTime(10, 30),
                'menus' => [['name' => '調理ボランティア', 'price' => 1500, 'capacity' => 8]],
            ],
            [
                'title' => '冬の朗読と紙芝居',
                'description' => '昔話の朗読と紙芝居で、ゆったりとした午後をすごしましょう。',
                'venue' => 'ひだまり苑 2階談話室',
                'starts_at' => now()->addWeeks(5)->setTime(13, 30),
                'menus' => [['name' => '朗読ボランティア', 'price' => 800, 'capacity' => 5], ['name' => '見学', 'price' => 300, 'capacity' => 15]],
            ],
        ];

        foreach ($visits as $attributes) {
            if ($facility->visits()->where('title', $attributes['title'])->exists()) {
                continue;
            }

            $visit = Visit::factory()->published()->for($facility, 'facility')->create([
                'title' => $attributes['title'],
                'description' => $attributes['description'],
                'venue' => $attributes['venue'],
                'starts_at' => $attributes['starts_at'],
                'ends_at' => $attributes['starts_at']->copy()->addHours(2),
            ]);

            foreach ($attributes['menus'] as $menu) {
                VisitMenu::factory()->for($visit)->create($menu);
            }
        }
    }
}
