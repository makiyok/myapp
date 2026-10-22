<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::middleware(['web', 'role:facility,admin'])->get('/_role-test', fn () => 'ok');
});

test('allowed roles pass', function (string $state) {
    $this->actingAs(User::factory()->{$state}()->create())
        ->get('/_role-test')->assertOk();
})->with(['facility', 'admin']);

test('other roles are forbidden', function () {
    $this->actingAs(User::factory()->create())
        ->get('/_role-test')->assertForbidden();
});

test('guests are forbidden', function () {
    $this->get('/_role-test')->assertForbidden();
});

test('visitors are forbidden on an facility-only route', function () {
    Route::middleware(['web', 'role:facility'])->get('/_facility-only', fn () => 'ok');

    $this->actingAs(User::factory()->create())
        ->get('/_facility-only')->assertForbidden();

    $this->actingAs(User::factory()->facility()->create())
        ->get('/_facility-only')->assertOk();
});

test('an unknown role name throws instead of silently forbidding', function () {
    Route::middleware(['web', 'role:organiser'])->get('/_misspelled-role', fn () => 'ok');

    $this->withoutExceptionHandling()
        ->actingAs(User::factory()->facility()->create())
        ->get('/_misspelled-role');
})->throws(ValueError::class);
