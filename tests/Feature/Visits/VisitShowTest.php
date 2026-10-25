<?php

use App\Models\Entry;
use App\Models\EntryItem;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitMenu;

test('guests can see a published visit with its pass types', function () {
    $this->travelTo('2030-01-15 12:00:00');
    $visit = Visit::factory()->published()->create([
        'title' => '夏祭り',
        'description' => '屋台がたくさん出ます',
        'venue' => '中央公園',
        'starts_at' => '2030-02-02 10:00:00',
        'ends_at' => '2030-02-02 16:30:00',
    ]);
    $visitMenu = VisitMenu::factory()->for($visit)->create([
        'name' => '一般',
        'price' => 3000,
        'capacity' => 50,
        'sales_starts_at' => '2030-01-10 09:00:00',
        'sales_ends_at' => '2030-02-01 18:00:00',
    ]);
    EntryItem::factory()->for(Entry::factory()->paid()->for($visit))->for($visitMenu)->create(['quantity' => 8]);

    $this->get(route('visits.show', $visit))
        ->assertOk()
        ->assertSee('夏祭り')
        ->assertSee('屋台がたくさん出ます')
        ->assertSee('中央公園')
        ->assertSee('2月2日(土) 10:00')
        ->assertSee('2月2日(土) 16:30')
        ->assertSee('一般')
        ->assertSee('3,000円')
        ->assertSee('42組')
        ->assertSee('1月10日(木) 09:00')
        ->assertSee('2月1日(金) 18:00')
        ->assertSee('販売中');
});

test('each pass type shows its sales state', function (string $state, int $sold, string $label) {
    $visit = Visit::factory()->published()->create();
    $visitMenu = VisitMenu::factory()->for($visit)->{$state}()->create(['capacity' => 5]);
    if ($sold > 0) {
        EntryItem::factory()->for(Entry::factory()->paid()->for($visit))->for($visitMenu)->create(['quantity' => $sold]);
    }

    $this->get(route('visits.show', $visit))
        ->assertOk()
        ->assertSee($label)
        ->assertDontSee('販売中');
})->with([
    'before the sales period' => ['beforeSale', 0, '販売前'],
    'after the sales period' => ['afterSale', 0, '販売終了'],
    'sold out' => ['configure', 5, '売り切れ'],
]);

test('non-public visits are hidden from other people', function (string $state, string $who) {
    $visit = Visit::factory()->{$state}()->create();
    if ($who !== 'guest') {
        $this->actingAs(User::factory()->{$who}()->create());
    }

    $this->get(route('visits.show', $visit))->assertNotFound();
})->with(['draft', 'unpublished'])->with([
    'guest' => ['guest'],
    'visitor' => ['configure'],
    'other facility' => ['facility'],
]);

test('the facility and admins can preview a draft visit', function (string $who) {
    $facility = User::factory()->facility()->create();
    $visit = Visit::factory()->draft()->for($facility, 'facility')->create(['title' => '準備中の慰問']);
    $viewer = $who === 'facility' ? $facility : User::factory()->admin()->create();

    $this->actingAs($viewer)
        ->get(route('visits.show', $visit))
        ->assertOk()
        ->assertSee('準備中の慰問')
        ->assertSee('下書き');
})->with(['facility', 'admin']);

test('the quantity select offers up to the smaller of 10 and the remaining count', function (int $capacity, int $max) {
    $visit = Visit::factory()->published()->create();
    $visitMenu = VisitMenu::factory()->for($visit)->create(['capacity' => $capacity]);

    $this->get(route('visits.show', $visit))
        ->assertOk()
        ->assertSee("quantity-{$visitMenu->id}")
        ->assertSee("{$max}組</option>", false)
        ->assertDontSee(($max + 1).'組</option>', false)
        ->assertSee('申し込み手続きへ進む');
})->with([
    'fewer than 10 remaining' => [3, 3],
    'more than 10 remaining' => [50, 10],
]);

test('pass types that cannot be bought have no quantity select', function (string $state, int $sold) {
    $visit = Visit::factory()->published()->create();
    $visitMenu = VisitMenu::factory()->for($visit)->{$state}()->create(['capacity' => 5]);
    if ($sold > 0) {
        EntryItem::factory()->for(Entry::factory()->paid()->for($visit))->for($visitMenu)->create(['quantity' => $sold]);
    }

    $this->get(route('visits.show', $visit))
        ->assertOk()
        ->assertDontSee("quantity-{$visitMenu->id}");
})->with([
    'before the sales period' => ['beforeSale', 0],
    'after the sales period' => ['afterSale', 0],
    'sold out' => ['configure', 5],
]);
