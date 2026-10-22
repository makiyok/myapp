<?php

use App\Enums\EntryStatus;
use App\Exceptions\PurchaseException;
use App\Models\Entry;
use App\Models\EntryItem;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitMenu;
use App\Services\EntryService;

test('creates a pending entry with items priced at the current pass prices', function () {
    $buyer = User::factory()->create();
    $visit = Visit::factory()->published()->create();
    $general = VisitMenu::factory()->for($visit)->create(['price' => 3000, 'capacity' => 50]);
    $student = VisitMenu::factory()->for($visit)->create(['price' => 1500, 'capacity' => 50]);

    $entry = app(EntryService::class)->createEntry($buyer, $visit, [$general->id => 2, $student->id => 1]);

    $entry = $entry->fresh();
    expect($entry->status)->toBe(EntryStatus::Pending)
        ->and($entry->user_id)->toBe($buyer->id)
        ->and($entry->visit_id)->toBe($visit->id)
        ->and($entry->total_amount)->toBe(7500)
        ->and($entry->paid_at)->toBeNull();

    expect($entry->items()->orderBy('id')->get()->map->only(['visit_menu_id', 'quantity', 'unit_price'])->all())->toBe([
        ['visit_menu_id' => $general->id, 'quantity' => 2, 'unit_price' => 3000],
        ['visit_menu_id' => $student->id, 'quantity' => 1, 'unit_price' => 1500],
    ]);
});

test('rejects a quantity above the remaining count and creates no entry', function () {
    $visit = Visit::factory()->published()->create();
    $visitMenu = VisitMenu::factory()->for($visit)->create(['name' => '一般', 'capacity' => 5]);
    EntryItem::factory()->for(Entry::factory()->paid()->for($visit))->for($visitMenu)->create(['quantity' => 3]);

    expect(fn () => app(EntryService::class)->createEntry(User::factory()->create(), $visit, [$visitMenu->id => 3]))
        ->toThrow(PurchaseException::class, '「一般」の残り人数が足りません（残り2名）。');

    expect(Entry::count())->toBe(1)
        ->and(EntryItem::count())->toBe(1);
});

test('accepts a quantity equal to the remaining count', function () {
    $visit = Visit::factory()->published()->create();
    $visitMenu = VisitMenu::factory()->for($visit)->create(['capacity' => 2]);

    $entry = app(EntryService::class)->createEntry(User::factory()->create(), $visit, [$visitMenu->id => 2]);

    expect($entry->items()->sum('quantity'))->toEqual(2);
});

test('rejects a pass type outside its sales period', function (string $state) {
    $visit = Visit::factory()->published()->create();
    $visitMenu = VisitMenu::factory()->for($visit)->{$state}()->create(['name' => '一般', 'capacity' => 50]);

    expect(fn () => app(EntryService::class)->createEntry(User::factory()->create(), $visit, [$visitMenu->id => 1]))
        ->toThrow(PurchaseException::class, '「一般」は販売期間外です。');

    expect(Entry::count())->toBe(0);
})->with(['beforeSale', 'afterSale']);

test('rejects a quantity outside 1 to 10', function (mixed $quantity) {
    $visit = Visit::factory()->published()->create();
    $visitMenu = VisitMenu::factory()->for($visit)->create(['capacity' => 50]);

    expect(fn () => app(EntryService::class)->createEntry(User::factory()->create(), $visit, [$visitMenu->id => $quantity]))
        ->toThrow(PurchaseException::class, '人数は1名から10名の間で選んでください。');

    expect(Entry::count())->toBe(0);
})->with([
    'eleven' => [11],
    'zero' => [0],
    'negative' => [-1],
    'not a whole number' => ['1.5'],
    'not a number' => ['abc'],
    'an array' => [[1]],
]);

test('accepts the maximum quantity of 10', function () {
    $visit = Visit::factory()->published()->create();
    $visitMenu = VisitMenu::factory()->for($visit)->create(['price' => 1000, 'capacity' => 50]);

    $entry = app(EntryService::class)->createEntry(User::factory()->create(), $visit, [$visitMenu->id => '10']);

    expect($entry->total_amount)->toBe(10000);
});

test('creates nothing when one of several pass types fails', function () {
    $visit = Visit::factory()->published()->create();
    $onSale = VisitMenu::factory()->for($visit)->create(['capacity' => 50]);
    $ended = VisitMenu::factory()->for($visit)->afterSale()->create(['capacity' => 50]);

    expect(fn () => app(EntryService::class)->createEntry(User::factory()->create(), $visit, [$onSale->id => 1, $ended->id => 1]))
        ->toThrow(PurchaseException::class);

    expect(Entry::count())->toBe(0)
        ->and(EntryItem::count())->toBe(0);
});

test('rejects a pass type that belongs to another visit', function () {
    $visit = Visit::factory()->published()->create();
    $otherVisitMenu = VisitMenu::factory()->for(Visit::factory()->published())->create(['capacity' => 50]);

    expect(fn () => app(EntryService::class)->createEntry(User::factory()->create(), $visit, [$otherVisitMenu->id => 1]))
        ->toThrow(PurchaseException::class, '選択したメニューが見つかりません。');

    expect(Entry::count())->toBe(0);
});

test('rejects an entry with no pass types', function () {
    $visit = Visit::factory()->published()->create();

    expect(fn () => app(EntryService::class)->createEntry(User::factory()->create(), $visit, []))
        ->toThrow(PurchaseException::class, 'メニューと人数を選んでください。');

    expect(Entry::count())->toBe(0);
});

test('rejects an visit that is not published', function (string $state) {
    $visit = Visit::factory()->{$state}()->create();
    $visitMenu = VisitMenu::factory()->for($visit)->create(['capacity' => 50]);

    expect(fn () => app(EntryService::class)->createEntry(User::factory()->create(), $visit, [$visitMenu->id => 1]))
        ->toThrow(PurchaseException::class, 'この慰問は現在申し込みできません。');

    expect(Entry::count())->toBe(0);
})->with(['draft', 'unpublished']);
