<?php

use App\Models\User;
use App\Models\Visit;

test('facilities see only their own visits', function () {
    $facility = User::factory()->facility()->create();
    $mine = Visit::factory()->for($facility, 'facility')->create(['title' => '自分の慰問']);
    $theirs = Visit::factory()->create(['title' => '他人の慰問']);

    $this->actingAs($facility)
        ->get(route('facility.visits.index'))
        ->assertOk()
        ->assertSee('自分の慰問')
        ->assertDontSee('他人の慰問');
});

test('status labels are shown', function () {
    $facility = User::factory()->facility()->create();
    Visit::factory()->for($facility, 'facility')->draft()->create();
    Visit::factory()->for($facility, 'facility')->published()->create();
    Visit::factory()->for($facility, 'facility')->unpublished()->create();

    $this->actingAs($facility)
        ->get(route('facility.visits.index'))
        ->assertSee('下書き')
        ->assertSee('公開中')
        ->assertSee('非公開');
});

test('visitors are forbidden', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('facility.visits.index'))
        ->assertForbidden();
});

test('guests are redirected to login', function () {
    $this->get(route('facility.visits.index'))->assertRedirect(route('login'));
});
