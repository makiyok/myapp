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
use Stripe\Checkout\Session;
use Stripe\Exception\ApiConnectionException;
use Stripe\Exception\InvalidRequestException;

test('opening the cancel page changes nothing', function () {
    $entry = Entry::factory()->create(['stripe_checkout_session_id' => 'cs_test_123']);
    $this->mock(CheckoutService::class)->shouldNotReceive('expireSession');

    $this->actingAs($entry->user)
        ->get(route('entries.cancel', $entry))
        ->assertOk()
        ->assertSee('支払いを中断しました')
        ->assertSee('申し込みを取り消す');

    expect($entry->fresh()->status)->toBe(EntryStatus::Pending);
});

test('cancelling expires the Stripe session, cancels the entry and frees the held passes', function () {
    $visit = Visit::factory()->published()->create();
    $visitMenu = VisitMenu::factory()->for($visit)->create(['capacity' => 5]);
    $entry = Entry::factory()->for($visit)->create(['stripe_checkout_session_id' => 'cs_test_123']);
    EntryItem::factory()->for($entry)->for($visitMenu)->create(['quantity' => 3]);
    expect($visitMenu->remaining())->toBe(2);
    $this->mock(CheckoutService::class)->shouldReceive('expireSession')->once()->with('cs_test_123');

    Livewire::actingAs($entry->user)
        ->test('pages::entries.cancel', ['entry' => $entry])
        ->call('cancelEntry')
        ->assertSee('キャンセルしました');

    expect($entry->fresh()->status)->toBe(EntryStatus::Cancelled)
        ->and($visitMenu->remaining())->toBe(5);
});

test('an entry without a Stripe session is cancelled without calling Stripe', function () {
    $entry = Entry::factory()->create(['stripe_checkout_session_id' => null]);
    $this->mock(CheckoutService::class)->shouldNotReceive('expireSession');

    Livewire::actingAs($entry->user)
        ->test('pages::entries.cancel', ['entry' => $entry])
        ->call('cancelEntry');

    expect($entry->fresh()->status)->toBe(EntryStatus::Cancelled);
});

test('if the session cannot be expired because it was already paid, the entry is confirmed instead', function () {
    $entry = Entry::factory()->create(['total_amount' => 3000, 'stripe_checkout_session_id' => 'cs_test_123']);
    EntryItem::factory()->for($entry)->create(['quantity' => 2, 'unit_price' => 1500]);
    $checkout = $this->mock(CheckoutService::class);
    $checkout->shouldReceive('expireSession')->andThrow(new InvalidRequestException('already complete'));
    $checkout->shouldReceive('retrieveSession')
        ->andReturn(Session::constructFrom(['id' => 'cs_test_123', 'status' => 'complete', 'payment_status' => 'paid', 'payment_intent' => 'pi_test_1']));

    Livewire::actingAs($entry->user)
        ->test('pages::entries.cancel', ['entry' => $entry])
        ->call('cancelEntry')
        ->assertRedirect(route('entries.complete', $entry));

    expect($entry->fresh()->status)->toBe(EntryStatus::Paid)
        ->and(Pass::count())->toBe(2);
});

test('an already expired session lets the entry be cancelled', function () {
    $entry = Entry::factory()->create(['stripe_checkout_session_id' => 'cs_test_123']);
    $checkout = $this->mock(CheckoutService::class);
    $checkout->shouldReceive('expireSession')->andThrow(new InvalidRequestException('already expired'));
    $checkout->shouldReceive('retrieveSession')
        ->andReturn(Session::constructFrom(['id' => 'cs_test_123', 'status' => 'expired', 'payment_status' => 'unpaid']));

    Livewire::actingAs($entry->user)
        ->test('pages::entries.cancel', ['entry' => $entry])
        ->call('cancelEntry');

    expect($entry->fresh()->status)->toBe(EntryStatus::Cancelled);
});

test('if Stripe cannot be reached the entry is not cancelled and an error is shown', function () {
    $entry = Entry::factory()->create(['stripe_checkout_session_id' => 'cs_test_123']);
    $checkout = $this->mock(CheckoutService::class);
    $checkout->shouldReceive('expireSession')->andThrow(new ApiConnectionException('network down'));
    $checkout->shouldReceive('retrieveSession')->andThrow(new ApiConnectionException('network down'));

    Livewire::actingAs($entry->user)
        ->test('pages::entries.cancel', ['entry' => $entry])
        ->call('cancelEntry')
        ->assertSee('取り消せませんでした');

    expect($entry->fresh()->status)->toBe(EntryStatus::Pending);
});

test('a paid entry is not changed by cancelling', function () {
    $entry = Entry::factory()->paid()->create(['stripe_checkout_session_id' => 'cs_test_123']);
    $this->mock(CheckoutService::class)->shouldNotReceive('expireSession');

    Livewire::actingAs($entry->user)
        ->test('pages::entries.cancel', ['entry' => $entry])
        ->assertSee('すでに支払い済み')
        ->call('cancelEntry');

    expect($entry->fresh()->status)->toBe(EntryStatus::Paid);
});

test('only the buyer can open or cancel the entry', function () {
    $entry = Entry::factory()->create();
    $this->mock(CheckoutService::class)->shouldNotReceive('expireSession');

    $this->actingAs(User::factory()->create())
        ->get(route('entries.cancel', $entry))
        ->assertForbidden();

    $this->actingAs($entry->visit->facility)
        ->get(route('entries.cancel', $entry))
        ->assertForbidden();

    Livewire::actingAs($entry->visit->facility)
        ->test('pages::entries.cancel', ['entry' => $entry])
        ->assertForbidden();

    expect($entry->fresh()->status)->toBe(EntryStatus::Pending);
});

test('guests are redirected to login', function () {
    $entry = Entry::factory()->create();

    $this->get(route('entries.cancel', $entry))->assertRedirect(route('login'));
});
