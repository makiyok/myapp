<?php

use App\Models\Entry;
use App\Models\EntryItem;
use App\Models\Visit;
use App\Models\VisitMenu;
use App\Services\CheckoutService;
use Stripe\Checkout\Session;
use Stripe\Service\Checkout\SessionService;
use Stripe\StripeClient;

test('createSession builds a jpy payment session and saves the session id', function () {
    $visit = Visit::factory()->published()->create(['title' => '朝焼けトレッキング']);
    $visitMenu = VisitMenu::factory()->for($visit)->create(['name' => '一般', 'price' => 3000]);
    $entry = Entry::factory()->for($visit)->create(['total_amount' => 6000]);
    EntryItem::factory()->for($entry)->for($visitMenu)->create(['quantity' => 2, 'unit_price' => 3000]);

    $sessions = Mockery::mock(SessionService::class);
    $sessions->shouldReceive('create')
        ->once()
        ->withArgs(function (array $params) use ($entry): bool {
            return $params['mode'] === 'payment'
                && ! array_key_exists('payment_method_types', $params)
                && 30 * 60 <= $params['expires_at'] - time()
                && $params['line_items'] === [[
                    'quantity' => 2,
                    'price_data' => [
                        'currency' => 'jpy',
                        'unit_amount' => 3000,
                        'product_data' => ['name' => '朝焼けトレッキング 一般'],
                    ],
                ]]
                && $params['metadata']['entry_id'] === (string) $entry->id
                && $params['success_url'] === route('entries.complete', $entry).'?session_id={CHECKOUT_SESSION_ID}'
                && $params['cancel_url'] === route('entries.cancel', $entry);
        })
        ->andReturn(Session::constructFrom(['id' => 'cs_test_123', 'url' => 'https://checkout.stripe.com/c/pay/cs_test_123']));

    $stripe = Mockery::mock(StripeClient::class);
    $stripe->shouldReceive('getService')->with('checkout')->andReturn((object) ['sessions' => $sessions]);

    $url = (new CheckoutService($stripe))->createSession($entry);

    expect($url)->toBe('https://checkout.stripe.com/c/pay/cs_test_123')
        ->and($entry->fresh()->stripe_checkout_session_id)->toBe('cs_test_123');
});

test('expireSession asks Stripe to expire the session', function () {
    $sessions = Mockery::mock(SessionService::class);
    $sessions->shouldReceive('expire')->once()->with('cs_test_123');
    $stripe = Mockery::mock(StripeClient::class);
    $stripe->shouldReceive('getService')->with('checkout')->andReturn((object) ['sessions' => $sessions]);

    (new CheckoutService($stripe))->expireSession('cs_test_123');
});
