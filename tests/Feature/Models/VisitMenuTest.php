<?php

use App\Models\Entry;
use App\Models\EntryItem;
use App\Models\Visit;
use App\Models\VisitMenu;

test('isOnSale is true only within the sales period, boundaries included', function (string $now, bool $expected) {
    $this->travelTo('2030-01-15 12:00:00');

    $visitMenu = VisitMenu::factory()->create([
        'sales_starts_at' => '2030-01-10 10:00:00',
        'sales_ends_at' => '2030-01-20 18:00:00',
    ]);

    $this->travelTo($now);

    expect($visitMenu->fresh()->isOnSale())->toBe($expected);
})->with([
    'before the start' => ['2030-01-10 09:59:59', false],
    'exactly at the start' => ['2030-01-10 10:00:00', true],
    'within the period' => ['2030-01-15 12:00:00', true],
    'exactly at the end' => ['2030-01-20 18:00:00', true],
    'after the end' => ['2030-01-20 18:00:01', false],
]);

test('factory states reflect the sales period', function () {
    expect(VisitMenu::factory()->create()->isOnSale())->toBeTrue()
        ->and(VisitMenu::factory()->beforeSale()->create()->isOnSale())->toBeFalse()
        ->and(VisitMenu::factory()->afterSale()->create()->isOnSale())->toBeFalse();
});

test('a pass type belongs to its visit', function () {
    $visit = Visit::factory()->create();
    $visitMenu = VisitMenu::factory()->for($visit)->create();

    expect($visitMenu->visit->is($visit))->toBeTrue()
        ->and($visit->visitMenus->pluck('id')->all())->toBe([$visitMenu->id]);
});

test('price and capacity are returned as integers', function () {
    $visitMenu = VisitMenu::factory()->create(['price' => 3000, 'capacity' => 50]);

    $fresh = VisitMenu::find($visitMenu->id);

    expect($fresh->price)->toBeInt()->toBe(3000)
        ->and($fresh->capacity)->toBeInt()->toBe(50);
});

test('deleting an visit deletes its pass types', function () {
    $visit = Visit::factory()->create();
    VisitMenu::factory()->for($visit)->count(2)->create();
    $other = VisitMenu::factory()->create();

    $visit->delete();

    expect(VisitMenu::pluck('id')->all())->toBe([$other->id]);
});

test('remaining subtracts paid entries and pending entries created within 15 minutes', function () {
    $this->travelTo('2030-01-15 12:00:00');
    $visitMenu = VisitMenu::factory()->create(['capacity' => 50]);
    $hold = fn (Entry $entry, int $quantity) => EntryItem::factory()->for($entry)->for($visitMenu)->create(['quantity' => $quantity]);

    $hold(Entry::factory()->paid()->create(['created_at' => '2030-01-01 00:00:00']), 30);
    $hold(Entry::factory()->create(['created_at' => '2030-01-15 11:45:00']), 5);
    $hold(Entry::factory()->create(['created_at' => '2030-01-15 11:44:59']), 4);
    $hold(Entry::factory()->cancelled()->create(), 3);
    $hold(Entry::factory()->refunded()->create(), 2);
    EntryItem::factory()->for(Entry::factory()->paid())->create(['quantity' => 1]);

    expect($visitMenu->remaining())->toBe(15);
});

test('remaining never goes below zero', function () {
    $visitMenu = VisitMenu::factory()->create(['capacity' => 2]);
    EntryItem::factory()->for(Entry::factory()->paid())->for($visitMenu)->create(['quantity' => 3]);

    expect($visitMenu->remaining())->toBe(0);
});
