<?php

use App\Models\User;

test('visitors see the my passes link in the menu', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('home'))
        ->assertOk()
        ->assertSee('マイ参加証');
});

test('facilities do not see the my passes link in the menu', function () {
    $this->actingAs(User::factory()->facility()->create())
        ->get(route('home'))
        ->assertOk()
        ->assertSee('福祉施設メニュー')
        ->assertDontSee('マイ参加証');
});
