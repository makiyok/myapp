<?php

use App\Models\User;
use App\Models\Visit;

test('guests see published upcoming visits with links to their details', function () {
    $this->travelTo('2030-01-15 12:00:00');
    $visit = Visit::factory()->published()->create([
        'title' => '夏祭り',
        'venue' => '中央公園',
        'starts_at' => '2030-02-02 10:00:00',
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('夏祭り')
        ->assertSee('中央公園')
        ->assertSee('2月2日(土) 10:00')
        ->assertSee(route('visits.show', $visit));
});

test('visits that are not public or already started are not listed', function () {
    Visit::factory()->draft()->create(['title' => '下書きの慰問']);
    Visit::factory()->unpublished()->create(['title' => '非公開の慰問']);
    Visit::factory()->published()->past()->create(['title' => '終わった慰問']);

    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee('下書きの慰問')
        ->assertDontSee('非公開の慰問')
        ->assertDontSee('終わった慰問')
        ->assertSee('現在、開催予定の慰問はありません。');
});

test('visits are listed with the nearest date first', function () {
    Visit::factory()->published()->create(['title' => '来月の慰問', 'starts_at' => now()->addMonth()]);
    Visit::factory()->published()->create(['title' => '来週の慰問', 'starts_at' => now()->addWeek()]);

    $this->get(route('home'))->assertSeeInOrder(['来週の慰問', '来月の慰問']);
});

test('logged in visitors can see the list', function () {
    Visit::factory()->published()->create(['title' => '夏祭り']);

    $this->actingAs(User::factory()->create())
        ->get(route('home'))
        ->assertOk()
        ->assertSee('夏祭り');
});
