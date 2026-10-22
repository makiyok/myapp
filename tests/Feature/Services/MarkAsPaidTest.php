<?php

use App\Enums\EntryStatus;
use App\Models\Entry;
use App\Models\EntryItem;
use App\Models\Pass;
use App\Services\EntryService;

test('markAsPaid pays a pending entry and issues one pass per seat with unique codes', function () {
    $entry = Entry::factory()->create(['total_amount' => 9000]);
    $first = EntryItem::factory()->for($entry)->create(['quantity' => 2]);
    $second = EntryItem::factory()->for($entry)->create(['quantity' => 1]);

    $result = app(EntryService::class)->markAsPaid($entry, 'pi_test_1');

    expect($result)->toBeTrue()
        ->and($entry->fresh()->status)->toBe(EntryStatus::Paid)
        ->and($entry->fresh()->paid_at)->not->toBeNull()
        ->and($entry->fresh()->payment_intent_id)->toBe('pi_test_1')
        ->and($first->passes()->count())->toBe(2)
        ->and($second->passes()->count())->toBe(1)
        ->and(Pass::pluck('code')->unique()->count())->toBe(3);
});

test('markAsPaid does nothing the second time', function () {
    $entry = Entry::factory()->create();
    EntryItem::factory()->for($entry)->create(['quantity' => 2]);
    $service = app(EntryService::class);

    $service->markAsPaid($entry);
    $again = $service->markAsPaid(Entry::find($entry->id));

    expect($again)->toBeFalse()
        ->and(Pass::count())->toBe(2);
});

test('markAsPaid ignores a stale model when another request already paid the entry', function () {
    $entry = Entry::factory()->create();
    EntryItem::factory()->for($entry)->create(['quantity' => 2]);
    $stale = Entry::find($entry->id);
    app(EntryService::class)->markAsPaid($entry);

    expect(app(EntryService::class)->markAsPaid($stale))->toBeFalse()
        ->and(Pass::count())->toBe(2);
});

test('markAsPaid leaves cancelled and refunded entries alone', function (string $state) {
    $entry = Entry::factory()->{$state}()->create();
    EntryItem::factory()->for($entry)->create(['quantity' => 2]);

    expect(app(EntryService::class)->markAsPaid($entry))->toBeFalse()
        ->and(Pass::count())->toBe(0);
})->with(['cancelled', 'refunded']);
