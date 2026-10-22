<?php

use App\Models\Entry;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Support\Facades\Gate;

test('only the buyer, the facility of the visit and admins can view an entry', function (string $who, bool $allowed) {
    $users = [
        'buyer' => User::factory()->create(),
        'facility' => User::factory()->facility()->create(),
        'admin' => User::factory()->admin()->create(),
        'otherVisitor' => User::factory()->create(),
        'otherFacility' => User::factory()->facility()->create(),
    ];
    $visit = Visit::factory()->published()->for($users['facility'], 'facility')->create();
    $entry = Entry::factory()->for($users['buyer'])->for($visit)->create();

    expect(Gate::forUser($users[$who])->allows('view', $entry))->toBe($allowed);
})->with([
    ['buyer', true],
    ['facility', true],
    ['admin', true],
    ['otherVisitor', false],
    ['otherFacility', false],
]);
