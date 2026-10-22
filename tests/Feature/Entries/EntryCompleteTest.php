<?php

use App\Enums\EntryStatus;
use App\Models\Entry;
use App\Models\EntryItem;
use App\Models\Pass;
use App\Models\User;
use App\Services\CheckoutService;
use App\Services\EntryService;
use Livewire\Livewire;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiConnectionException;

function pendingEntryWithSession(int $quantity = 2): Entry
{
    $entry = Entry::factory()->create(['total_amount' => 6000, 'stripe_checkout_session_id' => 'cs_test_123']);
    EntryItem::factory()->for($entry)->create(['quantity' => $quantity, 'unit_price' => 3000]);

    return $entry;
}

test('the complete page confirms payment when Stripe says paid', function () {
    $entry = pendingEntryWithSession(2);
    $this->mock(CheckoutService::class)
        ->shouldReceive('retrieveSession')
        ->with('cs_test_123')
        ->andReturn(Session::constructFrom(['id' => 'cs_test_123', 'payment_status' => 'paid', 'payment_intent' => 'pi_test_1']));

    $this->actingAs($entry->user)
        ->get(route('entries.complete', ['entry' => $entry, 'session_id' => 'cs_test_123']))
        ->assertOk()
        ->assertSee('申し込みが完了しました');

    expect($entry->fresh()->status)->toBe(EntryStatus::Paid)
        ->and($entry->fresh()->payment_intent_id)->toBe('pi_test_1')
        ->and(Pass::count())->toBe(2);
});

test('the complete page shows checking while Stripe says unpaid and keeps polling', function () {
    $entry = pendingEntryWithSession();
    $this->mock(CheckoutService::class)
        ->shouldReceive('retrieveSession')
        ->andReturn(Session::constructFrom(['id' => 'cs_test_123', 'payment_status' => 'unpaid']));

    $this->actingAs($entry->user)
        ->get(route('entries.complete', ['entry' => $entry, 'session_id' => 'cs_test_123']))
        ->assertOk()
        ->assertSee('支払いを確認しています')
        ->assertSee('wire:poll', false);

    expect($entry->fresh()->status)->toBe(EntryStatus::Pending)
        ->and(Pass::count())->toBe(0);
});

test('the poll confirms the payment once Stripe reports paid', function () {
    $entry = pendingEntryWithSession();
    $this->mock(CheckoutService::class)
        ->shouldReceive('retrieveSession')
        ->andReturn(
            Session::constructFrom(['id' => 'cs_test_123', 'payment_status' => 'unpaid']),
            Session::constructFrom(['id' => 'cs_test_123', 'payment_status' => 'paid', 'payment_intent' => 'pi_test_1']),
        );

    Livewire::actingAs($entry->user)
        ->withQueryParams(['session_id' => 'cs_test_123'])
        ->test('pages::entries.complete', ['entry' => $entry])
        ->assertSee('支払いを確認しています')
        ->call('confirmPayment')
        ->assertSee('申し込みが完了しました');

    expect($entry->fresh()->isPaid())->toBeTrue();
});

test('an expired Stripe session stops the polling and says it could not be confirmed', function () {
    $entry = pendingEntryWithSession();
    $this->mock(CheckoutService::class)
        ->shouldReceive('retrieveSession')
        ->andReturn(Session::constructFrom(['id' => 'cs_test_123', 'status' => 'expired', 'payment_status' => 'unpaid']));

    Livewire::actingAs($entry->user)
        ->withQueryParams(['session_id' => 'cs_test_123'])
        ->test('pages::entries.complete', ['entry' => $entry])
        ->assertSet('gaveUp', true)
        ->assertSee('支払いを確認できませんでした')
        ->assertDontSee('wire:poll', false);

    expect($entry->fresh()->status)->toBe(EntryStatus::Pending);
});

test('the polling gives up after the attempt limit and stops calling Stripe', function () {
    $entry = pendingEntryWithSession();
    $this->mock(CheckoutService::class)
        ->shouldReceive('retrieveSession')
        ->times(20)
        ->andReturn(Session::constructFrom(['id' => 'cs_test_123', 'status' => 'open', 'payment_status' => 'unpaid']));

    $component = Livewire::actingAs($entry->user)
        ->withQueryParams(['session_id' => 'cs_test_123'])
        ->test('pages::entries.complete', ['entry' => $entry])
        ->assertSet('attempts', 1);

    foreach (range(1, 19) as $unused) {
        $component->call('confirmPayment');
    }

    $component->assertSet('attempts', 20)->assertSet('gaveUp', false);

    $component->call('confirmPayment')
        ->assertSet('gaveUp', true)
        ->assertSee('支払いを確認できませんでした')
        ->assertDontSee('wire:poll', false);
});

test('a cancelled entry shows its status without polling or calling Stripe', function () {
    $entry = Entry::factory()->cancelled()->create(['stripe_checkout_session_id' => 'cs_test_123']);
    $this->mock(CheckoutService::class)->shouldNotReceive('retrieveSession');

    $this->actingAs($entry->user)
        ->get(route('entries.complete', ['entry' => $entry, 'session_id' => 'cs_test_123']))
        ->assertOk()
        ->assertSee('キャンセル')
        ->assertDontSee('申し込みが完了しました')
        ->assertDontSee('wire:poll', false);

    expect($entry->fresh()->status)->toBe(EntryStatus::Cancelled);
});

test('a mismatched session id never reaches Stripe and changes nothing', function () {
    $entry = pendingEntryWithSession();
    $this->mock(CheckoutService::class)->shouldNotReceive('retrieveSession');

    $this->actingAs($entry->user)
        ->get(route('entries.complete', ['entry' => $entry, 'session_id' => 'cs_test_other']))
        ->assertOk();

    expect($entry->fresh()->status)->toBe(EntryStatus::Pending);
});

test('an already paid entry shows completion without calling Stripe', function () {
    $entry = Entry::factory()->paid()->create(['stripe_checkout_session_id' => 'cs_test_123']);
    $this->mock(CheckoutService::class)->shouldNotReceive('retrieveSession');

    $this->actingAs($entry->user)
        ->get(route('entries.complete', ['entry' => $entry, 'session_id' => 'cs_test_123']))
        ->assertSee('申し込みが完了しました');
});

test('a free pending entry is confirmed on the complete page', function () {
    $entry = Entry::factory()->create(['total_amount' => 0]);
    EntryItem::factory()->for($entry)->create(['quantity' => 3, 'unit_price' => 0]);
    $this->mock(CheckoutService::class)->shouldNotReceive('retrieveSession');

    $this->actingAs($entry->user)
        ->get(route('entries.complete', $entry))
        ->assertSee('申し込みが完了しました');

    expect($entry->fresh()->isPaid())->toBeTrue()
        ->and(Pass::count())->toBe(3);
});

test('the facility can view the page but does not confirm the payment', function () {
    $entry = pendingEntryWithSession();
    $this->mock(CheckoutService::class)->shouldNotReceive('retrieveSession');

    $this->actingAs($entry->visit->facility)
        ->get(route('entries.complete', ['entry' => $entry, 'session_id' => 'cs_test_123']))
        ->assertOk();

    expect($entry->fresh()->status)->toBe(EntryStatus::Pending);
});

test('other visitors cannot open the complete page', function () {
    $entry = Entry::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('entries.complete', $entry))
        ->assertForbidden();
});

test('guests are redirected to login', function () {
    $entry = Entry::factory()->create();

    $this->get(route('entries.complete', $entry))->assertRedirect(route('login'));
});

test('a Stripe API failure keeps the entry pending and the page still renders', function () {
    $entry = pendingEntryWithSession();
    $this->mock(CheckoutService::class)
        ->shouldReceive('retrieveSession')
        ->andThrow(new ApiConnectionException('timeout'));

    $this->actingAs($entry->user)
        ->get(route('entries.complete', ['entry' => $entry, 'session_id' => 'cs_test_123']))
        ->assertOk()
        ->assertSee('支払いを確認しています');

    expect($entry->fresh()->status)->toBe(EntryStatus::Pending)
        ->and(Pass::count())->toBe(0);
});

test('the poll does not call Stripe when the session id does not match', function () {
    $entry = pendingEntryWithSession();
    $this->mock(CheckoutService::class)->shouldNotReceive('retrieveSession');

    Livewire::actingAs($entry->user)
        ->withQueryParams(['session_id' => 'cs_test_other'])
        ->test('pages::entries.complete', ['entry' => $entry])
        ->call('confirmPayment');

    expect($entry->fresh()->status)->toBe(EntryStatus::Pending);
});

test('a Stripe paid answer does not issue passes twice when the webhook already paid', function () {
    $entry = pendingEntryWithSession(2);
    app(EntryService::class)->markAsPaid($entry, 'pi_test_1');
    $this->mock(CheckoutService::class)->shouldNotReceive('retrieveSession');

    $this->actingAs($entry->user)
        ->get(route('entries.complete', ['entry' => $entry, 'session_id' => 'cs_test_123']))
        ->assertSee('申し込みが完了しました');

    expect(Pass::count())->toBe(2);
});
