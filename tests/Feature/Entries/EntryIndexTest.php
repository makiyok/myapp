<?php

use App\Models\Entry;
use App\Models\User;
use App\Models\Visit;

test('users see only their own entries', function () {
    $this->travelTo('2030-01-15 12:00:00');
    $user = User::factory()->create();
    $mine = Visit::factory()->published()->create(['title' => '自分が買った慰問', 'starts_at' => '2030-02-02 10:00:00']);
    Entry::factory()->for($user)->for($mine)->paid()->create(['total_amount' => 6000]);
    Entry::factory()->for(Visit::factory()->published()->state(['title' => '他人が買った慰問']))->create();

    $this->actingAs($user)
        ->get(route('entries.index'))
        ->assertOk()
        ->assertSee('自分が買った慰問')
        ->assertSee('2月2日(土) 10:00')
        ->assertSee('1月15日(火) 12:00')
        ->assertSee('6,000円')
        ->assertSee('支払い済み')
        ->assertDontSee('他人が買った慰問');
});

test('entries are listed newest first', function () {
    $user = User::factory()->create();
    Entry::factory()->for($user)->for(Visit::factory()->published()->state(['title' => '古い申し込み']))->create(['created_at' => now()->subDay()]);
    Entry::factory()->for($user)->for(Visit::factory()->published()->state(['title' => '新しい申し込み']))->create();

    $this->actingAs($user)
        ->get(route('entries.index'))
        ->assertSeeInOrder(['新しい申し込み', '古い申し込み']);
});

test('a message is shown when there are no entries', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('entries.index'))
        ->assertOk()
        ->assertSee('まだ申し込みがありません。');
});

test('guests are redirected to login', function () {
    $this->get(route('entries.index'))->assertRedirect(route('login'));
});
