<?php

use App\Enums\VisitStatus;
use App\Models\Visit;

test('published scope returns only published visits', function () {
    $published = Visit::factory()->published()->create();
    Visit::factory()->draft()->create();
    Visit::factory()->unpublished()->create();

    expect(Visit::published()->pluck('id')->all())->toBe([$published->id]);
});

test('upcoming scope excludes past visits', function () {
    $upcoming = Visit::factory()->create();
    Visit::factory()->past()->create();

    expect(Visit::upcoming()->pluck('id')->all())->toBe([$upcoming->id]);
});

test('isPublished reflects the status', function () {
    expect(Visit::factory()->published()->make()->isPublished())->toBeTrue()
        ->and(Visit::factory()->draft()->make()->isPublished())->toBeFalse()
        ->and(Visit::factory()->unpublished()->make()->isPublished())->toBeFalse();
});

test('visit status has labels and colors', function () {
    expect(VisitStatus::Draft->label())->toBe('下書き')
        ->and(VisitStatus::Published->label())->toBe('公開中')
        ->and(VisitStatus::Unpublished->label())->toBe('非公開')
        ->and(VisitStatus::Draft->color())->toBe('zinc')
        ->and(VisitStatus::Published->color())->toBe('green')
        ->and(VisitStatus::Unpublished->color())->toBe('red');
});
