<?php

use App\Enums\EntryStatus;
use App\Models\Entry;
use App\Models\EntryItem;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitMenu;
use Illuminate\Database\QueryException;

test('entry status labels are in Japanese', function () {
    expect(EntryStatus::Pending->label())->toBe('支払い待ち')
        ->and(EntryStatus::Paid->label())->toBe('支払い済み')
        ->and(EntryStatus::Cancelled->label())->toBe('キャンセル')
        ->and(EntryStatus::Refunded->label())->toBe('返金済み');
});

test('a new entry is pending by default', function () {
    $entry = User::factory()->create()->entries()->create([
        'visit_id' => Visit::factory()->published()->create()->id,
        'total_amount' => 6000,
    ]);

    expect($entry->status)->toBe(EntryStatus::Pending)
        ->and($entry->isPending())->toBeTrue()
        ->and($entry->isPaid())->toBeFalse()
        ->and($entry->fresh()->status)->toBe(EntryStatus::Pending)
        ->and($entry->fresh()->total_amount)->toBe(6000)
        ->and($entry->fresh()->paid_at)->toBeNull();
});

test('status and owner cannot be mass assigned', function () {
    $entry = new Entry(['status' => 'paid', 'user_id' => 99, 'paid_at' => now(), 'total_amount' => 100]);

    expect($entry->status)->toBe(EntryStatus::Pending)
        ->and($entry->user_id)->toBeNull()
        ->and($entry->paid_at)->toBeNull();
});

test('an entry belongs to its buyer and visit and has items', function () {
    $buyer = User::factory()->create();
    $visit = Visit::factory()->published()->create();
    $visitMenu = VisitMenu::factory()->for($visit)->create(['price' => 3000]);

    $entry = Entry::factory()->for($buyer)->for($visit)->paid()->create(['total_amount' => 6000]);
    $item = EntryItem::factory()->for($entry)->for($visitMenu)->create(['quantity' => 2, 'unit_price' => 3000]);

    expect($entry->user->is($buyer))->toBeTrue()
        ->and($entry->visit->is($visit))->toBeTrue()
        ->and($entry->items)->toHaveCount(1)
        ->and($entry->isPaid())->toBeTrue()
        ->and($entry->paid_at)->not->toBeNull()
        ->and($item->entry->is($entry))->toBeTrue()
        ->and($item->visitMenu->is($visitMenu))->toBeTrue()
        ->and($item->fresh()->quantity)->toBe(2)
        ->and($item->fresh()->unit_price)->toBe(3000)
        ->and($buyer->entries)->toHaveCount(1)
        ->and($visit->entries)->toHaveCount(1)
        ->and($visitMenu->entryItems)->toHaveCount(1);
});

test('the same pass type cannot appear twice in one entry', function () {
    $item = EntryItem::factory()->create();

    EntryItem::factory()->for($item->entry)->for($item->visitMenu)->create();
})->throws(QueryException::class);

test('deleting an entry deletes its items', function () {
    $item = EntryItem::factory()->create();

    $item->entry->delete();

    expect(EntryItem::count())->toBe(0);
});

test('records with entries cannot be deleted', function (string $target) {
    $item = EntryItem::factory()->create();

    $record = match ($target) {
        'buyer' => $item->entry->user,
        'visit' => $item->entry->visit,
        'sold pass type' => $item->visitMenu,
    };

    $record->delete();
})->with(['buyer', 'visit', 'sold pass type'])->throws(QueryException::class);
