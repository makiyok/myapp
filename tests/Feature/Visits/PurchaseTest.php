<?php

use App\Enums\EntryStatus;
use App\Models\Entry;
use App\Models\EntryItem;
use App\Models\Pass;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitMenu;
use App\Services\CheckoutService;
use Livewire\Livewire;
use Stripe\Exception\ApiConnectionException;

test('proceeding stores the selected quantities in the session and goes to the purchase page', function () {
    $visit = Visit::factory()->published()->create();
    $general = VisitMenu::factory()->for($visit)->create(['capacity' => 50]);
    $student = VisitMenu::factory()->for($visit)->create(['capacity' => 50]);

    Livewire::test('pages::visits.show', ['visit' => $visit])
        ->set("quantities.{$general->id}", '2')
        ->call('proceed')
        ->assertRedirect(route('visits.purchase', $visit));

    expect(session("purchase.{$visit->id}"))->toBe([$general->id => 2]);
});

test('proceeding without choosing a quantity shows an error', function () {
    $visit = Visit::factory()->published()->create();
    VisitMenu::factory()->for($visit)->create(['capacity' => 50]);

    Livewire::test('pages::visits.show', ['visit' => $visit])
        ->call('proceed')
        ->assertHasErrors(['quantities' => '人数を選んでください。'])
        ->assertNoRedirect();

    expect(session()->has("purchase.{$visit->id}"))->toBeFalse();
});

test('guests are sent to login and come back to the purchase page', function () {
    $user = User::factory()->create();
    $visit = Visit::factory()->published()->create();

    $this->get(route('visits.purchase', $visit))->assertRedirect(route('login'));

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('visits.purchase', $visit));
});

test('the purchase page lists the selected pass types with subtotals and the total', function () {
    $visit = Visit::factory()->published()->create();
    $general = VisitMenu::factory()->for($visit)->create(['name' => '一般', 'price' => 3000, 'capacity' => 50]);
    $student = VisitMenu::factory()->for($visit)->create(['name' => '学生', 'price' => 1500, 'capacity' => 50]);
    VisitMenu::factory()->for($visit)->create(['name' => 'ペア', 'capacity' => 50]);

    $this->actingAs(User::factory()->create())
        ->withSession(['purchase' => [$visit->id => [$general->id => 2, $student->id => 3]]])
        ->get(route('visits.purchase', $visit))
        ->assertOk()
        ->assertSeeInOrder(['一般', '3,000円', '2名', '6,000円', '学生', '1,500円', '3名', '4,500円', '合計', '10,500円'])
        ->assertDontSee('ペア');
});

test('the purchase page goes back to the visit when nothing is selected', function () {
    $visit = Visit::factory()->published()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('visits.purchase', $visit))
        ->assertRedirect(route('visits.show', $visit));
});

test('checkout creates a pending entry and redirects to the Stripe page', function () {
    $buyer = User::factory()->create();
    $visit = Visit::factory()->published()->create();
    $visitMenu = VisitMenu::factory()->for($visit)->create(['price' => 3000, 'capacity' => 50]);
    session()->put("purchase.{$visit->id}", [$visitMenu->id => 2]);
    $this->mock(CheckoutService::class)
        ->shouldReceive('createSession')
        ->once()
        ->with(Mockery::on(fn (Entry $entry): bool => $entry->total_amount === 6000))
        ->andReturn('https://checkout.stripe.com/c/pay/cs_test_123');

    Livewire::actingAs($buyer)
        ->test('pages::visits.purchase', ['visit' => $visit])
        ->call('checkout')
        ->assertRedirect('https://checkout.stripe.com/c/pay/cs_test_123');

    $entry = Entry::sole();
    expect($entry->user_id)->toBe($buyer->id)
        ->and($entry->status)->toBe(EntryStatus::Pending)
        ->and($entry->total_amount)->toBe(6000)
        ->and(session()->has("purchase.{$visit->id}"))->toBeFalse();
});

test('a free entry skips Stripe, is paid on the spot and goes to the complete page', function () {
    $visit = Visit::factory()->published()->create();
    $visitMenu = VisitMenu::factory()->for($visit)->create(['price' => 0, 'capacity' => 50]);
    session()->put("purchase.{$visit->id}", [$visitMenu->id => 2]);
    $this->mock(CheckoutService::class)->shouldNotReceive('createSession');

    $component = Livewire::actingAs(User::factory()->create())
        ->test('pages::visits.purchase', ['visit' => $visit])
        ->call('checkout');

    $entry = Entry::sole();
    $component->assertRedirect(route('entries.complete', $entry));
    expect($entry->total_amount)->toBe(0)
        ->and($entry->status)->toBe(EntryStatus::Paid)
        ->and($entry->paid_at)->not->toBeNull()
        ->and(Pass::count())->toBe(2);
});

test('a Stripe failure cancels the entry and shows an error', function () {
    $visit = Visit::factory()->published()->create();
    $visitMenu = VisitMenu::factory()->for($visit)->create(['price' => 3000, 'capacity' => 50]);
    session()->put("purchase.{$visit->id}", [$visitMenu->id => 1]);
    $this->mock(CheckoutService::class)
        ->shouldReceive('createSession')
        ->andThrow(new ApiConnectionException('network down'));

    Livewire::actingAs(User::factory()->create())
        ->test('pages::visits.purchase', ['visit' => $visit])
        ->call('checkout')
        ->assertNoRedirect()
        ->assertSee('決済ページを開けませんでした');

    expect(Entry::sole()->status)->toBe(EntryStatus::Cancelled)
        ->and($visitMenu->remaining())->toBe(50);
});

test('checkout shows the purchase error and creates no entry', function () {
    $visit = Visit::factory()->published()->create();
    $visitMenu = VisitMenu::factory()->for($visit)->create(['name' => '一般', 'capacity' => 5]);
    session()->put("purchase.{$visit->id}", [$visitMenu->id => 3]);
    $component = Livewire::actingAs(User::factory()->create())->test('pages::visits.purchase', ['visit' => $visit]);
    EntryItem::factory()->for(Entry::factory()->paid()->for($visit))->for($visitMenu)->create(['quantity' => 3]);

    $component->call('checkout')
        ->assertNoRedirect()
        ->assertSee('「一般」の残り人数が足りません（残り2名）。');

    expect(Entry::count())->toBe(1)
        ->and(session("purchase.{$visit->id}"))->toBe([$visitMenu->id => 3]);
});
