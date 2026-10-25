<?php

use App\Enums\EntryStatus;
use App\Models\Entry;
use App\Models\User;
use App\Models\Visit;
use App\Services\CheckoutService;
use Livewire\Livewire;
use Stripe\Exception\ApiConnectionException;

test('users see only their own entries', function () {
    $this->travelTo('2030-01-15 12:00:00');
    $user = User::factory()->create();
    $mine = Visit::factory()->published()->create(['title' => '自分が買った慰問', 'starts_at' => '2030-02-02 10:00:00']);
    Entry::factory()->for($user)->for($mine)->paid()->create(['total_amount' => 6000]);
    Entry::factory()->for(Visit::factory()->published()->state(['title' => '他人が買った慰問']))->create();

    $this->actingAs($user)
        ->get(route('entries.index'))
        ->assertOk()
        ->assertSee('自分が買った慰問')
        ->assertSee('2月2日(土) 10:00')
        ->assertSee('1月15日(火) 12:00')
        ->assertSee('6,000円')
        ->assertSee('支払い済み')
        ->assertDontSee('他人が買った慰問');
});

test('entries are listed newest first', function () {
    $user = User::factory()->create();
    Entry::factory()->for($user)->for(Visit::factory()->published()->state(['title' => '古い申し込み']))->create(['created_at' => now()->subDay()]);
    Entry::factory()->for($user)->for(Visit::factory()->published()->state(['title' => '新しい申し込み']))->create();

    $this->actingAs($user)
        ->get(route('entries.index'))
        ->assertSeeInOrder(['新しい申し込み', '古い申し込み']);
});

test('a message is shown when there are no entries', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('entries.index'))
        ->assertOk()
        ->assertSee('まだ申し込みがありません。');
});

test('only pending entries have a cancel button', function () {
    $user = User::factory()->create();
    $pending = Entry::factory()->for($user)->create();
    $paid = Entry::factory()->for($user)->paid()->create();

    $this->actingAs($user)
        ->get(route('entries.index'))
        ->assertSee('予約キャンセル')
        ->assertSee('data-test="cancel-entry-'.$pending->id.'"', escape: false)
        ->assertDontSee('data-test="cancel-entry-'.$paid->id.'"', escape: false);
});

test('cancelling a pending entry expires the Stripe session and removes it from the list', function () {
    $user = User::factory()->create();
    $entry = Entry::factory()->for($user)->for(Visit::factory()->published()->state(['title' => '取り消す慰問']))
        ->create(['stripe_checkout_session_id' => 'cs_test_123']);
    $this->mock(CheckoutService::class)->shouldReceive('expireSession')->once()->with('cs_test_123');

    Livewire::actingAs($user)
        ->test('pages::entries.index')
        ->assertSee('取り消す慰問')
        ->call('cancelEntry', $entry->id)
        ->assertDontSee('取り消す慰問');

    expect($entry->fresh()->status)->toBe(EntryStatus::Cancelled);
});

test('the entry stays pending when Stripe cannot be reached', function () {
    $user = User::factory()->create();
    $entry = Entry::factory()->for($user)->create(['stripe_checkout_session_id' => 'cs_test_123']);
    $checkout = $this->mock(CheckoutService::class);
    $checkout->shouldReceive('expireSession')->andThrow(new ApiConnectionException('network down'));
    $checkout->shouldReceive('retrieveSession')->andThrow(new ApiConnectionException('network down'));

    Livewire::actingAs($user)
        ->test('pages::entries.index')
        ->call('cancelEntry', $entry->id);

    expect($entry->fresh()->status)->toBe(EntryStatus::Pending);
});

test('users cannot cancel entries of other users', function () {
    $entry = Entry::factory()->create(['stripe_checkout_session_id' => 'cs_test_123']);
    $this->mock(CheckoutService::class)->shouldNotReceive('expireSession');

    Livewire::actingAs(User::factory()->create())
        ->test('pages::entries.index')
        ->call('cancelEntry', $entry->id)
        ->assertForbidden();

    expect($entry->fresh()->status)->toBe(EntryStatus::Pending);
});

test('guests are redirected to login', function () {
    $this->get(route('entries.index'))->assertRedirect(route('login'));
});
