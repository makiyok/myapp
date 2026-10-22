<?php

use App\Models\User;
use App\Models\Visit;
use Illuminate\Support\Facades\Gate;

beforeEach(function () {
    $this->owner = User::factory()->facility()->create();
    $this->otherFacility = User::factory()->facility()->create();
    $this->admin = User::factory()->admin()->create();
    $this->visitor = User::factory()->create();
});

function actor(string $who): ?User
{
    return $who === 'guest' ? null : test()->{$who};
}

test('anyone can view a published visit', function (string $who) {
    $visit = Visit::factory()->published()->for($this->owner, 'facility')->create();

    expect(Gate::forUser(actor($who))->inspect('view', $visit)->allowed())->toBeTrue();
})->with(['owner', 'otherFacility', 'admin', 'visitor', 'guest']);

test('only the owner and admins can view non-public visits', function (string $state, string $who, bool $allowed) {
    $visit = Visit::factory()->{$state}()->for($this->owner, 'facility')->create();

    $response = Gate::forUser(actor($who))->inspect('view', $visit);

    expect($response->allowed())->toBe($allowed);
    if (! $allowed) {
        expect($response->status())->toBe(404);
    }
})->with(['draft', 'unpublished'])->with([
    ['owner', true],
    ['admin', true],
    ['otherFacility', false],
    ['visitor', false],
    ['guest', false],
]);

test('only facilities can create visits', function (string $who, bool $allowed) {
    expect(Gate::forUser(actor($who))->allows('create', Visit::class))->toBe($allowed);
})->with([
    ['owner', true],
    ['visitor', false],
    ['admin', false],
]);

test('only the owner can update or delete an visit', function (string $ability, string $who, bool $allowed) {
    $visit = Visit::factory()->for($this->owner, 'facility')->create();

    expect(Gate::forUser(actor($who))->allows($ability, $visit))->toBe($allowed);
})->with(['update', 'delete'])->with([
    ['owner', true],
    ['otherFacility', false],
    ['admin', false],
    ['visitor', false],
]);

test('only admins can unpublish an visit', function (string $who, bool $allowed) {
    $visit = Visit::factory()->published()->for($this->owner, 'facility')->create();

    expect(Gate::forUser(actor($who))->allows('unpublish', $visit))->toBe($allowed);
})->with([
    ['admin', true],
    ['owner', false],
    ['otherFacility', false],
    ['visitor', false],
]);

test('only the owner can toggle publish, and never on an unpublished visit', function (string $state, string $who, bool $allowed) {
    $visit = Visit::factory()->{$state}()->for($this->owner, 'facility')->create();

    expect(Gate::forUser(actor($who))->allows('togglePublish', $visit))->toBe($allowed);
})->with([
    ['draft', 'owner', true],
    ['published', 'owner', true],
    ['unpublished', 'owner', false],
    ['draft', 'otherFacility', false],
    ['published', 'admin', false],
    ['unpublished', 'admin', false],
    ['draft', 'visitor', false],
]);
