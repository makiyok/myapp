<?php

use App\Enums\EntryStatus;
use App\Models\Entry;
use App\Models\EntryItem;
use App\Models\Pass;
use Illuminate\Testing\TestResponse;

/**
 * 正しい署名を付けた Stripe Webhook を送る。
 *
 * @param  array<string, mixed>  $session
 */
function postStripeEvent(mixed $test, string $type, array $session, ?string $secret = null): TestResponse
{
    $payload = json_encode([
        'id' => 'evt_test_'.uniqid(),
        'object' => 'visit',
        'type' => $type,
        'data' => ['object' => $session + ['object' => 'checkout.session']],
    ]);
    $timestamp = time();
    $signature = hash_hmac('sha256', "{$timestamp}.{$payload}", $secret ?? config('services.stripe.webhook_secret'));

    return $test->call('POST', route('stripe.webhook'), [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$signature}",
        'CONTENT_TYPE' => 'application/json',
    ], $payload);
}

function pendingEntryForWebhook(int $quantity = 3): Entry
{
    $entry = Entry::factory()->create(['total_amount' => 9000, 'stripe_checkout_session_id' => 'cs_test_123']);
    EntryItem::factory()->for($entry)->create(['quantity' => $quantity, 'unit_price' => 3000]);

    return $entry;
}

test('a correctly signed completed visit pays the entry and issues the passes', function () {
    $entry = pendingEntryForWebhook(3);

    postStripeEvent($this, 'checkout.session.completed', [
        'id' => 'cs_test_123',
        'amount_total' => 9000,
        'payment_status' => 'paid',
        'payment_intent' => 'pi_test_1',
        'metadata' => ['entry_id' => (string) $entry->id],
    ])->assertOk();

    expect($entry->fresh()->status)->toBe(EntryStatus::Paid)
        ->and($entry->fresh()->payment_intent_id)->toBe('pi_test_1')
        ->and(Pass::count())->toBe(3);
});

test('a wrong signature gets 400 and changes nothing', function () {
    $entry = pendingEntryForWebhook();

    postStripeEvent($this, 'checkout.session.completed', [
        'id' => 'cs_test_123',
        'payment_status' => 'paid',
        'metadata' => ['entry_id' => (string) $entry->id],
    ], 'whsec_wrong')->assertStatus(400);

    expect($entry->fresh()->status)->toBe(EntryStatus::Pending)
        ->and(Pass::count())->toBe(0);
});

test('the webhook is excluded from CSRF protection while other posts are not', function () {
    // テスト中は CSRF の検証が省かれるので、本番と同じ環境に見せて検証を有効にする
    $this->app['env'] = 'production';

    $this->post(route('stripe.webhook'), [])->assertStatus(400);
    $this->post(route('logout'), [])->assertStatus(419);
});

test('a missing signature gets 400', function () {
    $this->postJson(route('stripe.webhook'), ['type' => 'checkout.session.completed'])->assertStatus(400);
});

test('the same visit twice does not issue the passes twice', function () {
    $entry = pendingEntryForWebhook(3);
    $session = [
        'id' => 'cs_test_123',
        'amount_total' => 9000,
        'payment_status' => 'paid',
        'payment_intent' => 'pi_test_1',
        'metadata' => ['entry_id' => (string) $entry->id],
    ];

    postStripeEvent($this, 'checkout.session.completed', $session)->assertOk();
    postStripeEvent($this, 'checkout.session.completed', $session)->assertOk();

    expect(Pass::count())->toBe(3);
});

test('an unpaid session leaves the entry pending', function () {
    $entry = pendingEntryForWebhook();

    postStripeEvent($this, 'checkout.session.completed', [
        'id' => 'cs_test_123',
        'payment_status' => 'unpaid',
        'metadata' => ['entry_id' => (string) $entry->id],
    ])->assertOk();

    expect($entry->fresh()->status)->toBe(EntryStatus::Pending);
});

test('other visit types and unknown entries are acknowledged without changes', function () {
    $entry = pendingEntryForWebhook();

    postStripeEvent($this, 'charge.refunded', ['id' => 'ch_1'])->assertOk();
    postStripeEvent($this, 'checkout.session.completed', [
        'id' => 'cs_x',
        'payment_status' => 'paid',
        'metadata' => ['entry_id' => '999999'],
    ])->assertOk();

    expect($entry->fresh()->status)->toBe(EntryStatus::Pending)
        ->and(Pass::count())->toBe(0);
});

test('a payment for an already cancelled entry is not applied', function () {
    $entry = Entry::factory()->cancelled()->create(['stripe_checkout_session_id' => 'cs_test_123']);
    EntryItem::factory()->for($entry)->create(['quantity' => 2]);

    postStripeEvent($this, 'checkout.session.completed', [
        'id' => 'cs_test_123',
        'amount_total' => $entry->total_amount,
        'payment_status' => 'paid',
        'metadata' => ['entry_id' => (string) $entry->id],
    ])->assertOk();

    expect($entry->fresh()->status)->toBe(EntryStatus::Cancelled)
        ->and(Pass::count())->toBe(0);
});

test('a notification for a different session is ignored', function () {
    $entry = pendingEntryForWebhook();

    postStripeEvent($this, 'checkout.session.completed', [
        'id' => 'cs_test_other',
        'amount_total' => 9000,
        'payment_status' => 'paid',
        'metadata' => ['entry_id' => (string) $entry->id],
    ])->assertOk();

    expect($entry->fresh()->status)->toBe(EntryStatus::Pending)
        ->and(Pass::count())->toBe(0);
});

test('a notification with a different amount is ignored', function () {
    $entry = pendingEntryForWebhook();

    postStripeEvent($this, 'checkout.session.completed', [
        'id' => 'cs_test_123',
        'amount_total' => 100,
        'payment_status' => 'paid',
        'metadata' => ['entry_id' => (string) $entry->id],
    ])->assertOk();

    expect($entry->fresh()->status)->toBe(EntryStatus::Pending)
        ->and(Pass::count())->toBe(0);
});

/**
 * 支払い済みの checkout.session.completed の生ボディを作る。
 */
function paidSessionPayload(Entry $entry): string
{
    return json_encode([
        'id' => 'evt_test_'.uniqid(),
        'object' => 'visit',
        'type' => 'checkout.session.completed',
        'data' => ['object' => [
            'object' => 'checkout.session',
            'id' => 'cs_test_123',
            'amount_total' => $entry->total_amount,
            'payment_status' => 'paid',
            'payment_intent' => 'pi_test_1',
            'metadata' => ['entry_id' => (string) $entry->id],
        ]],
    ]);
}

/**
 * 生ボディと Stripe-Signature ヘッダー（任意）をそのまま送る。
 */
function postRawStripe(mixed $test, string $body, ?string $signatureHeader): TestResponse
{
    $server = ['CONTENT_TYPE' => 'application/json'];

    if ($signatureHeader !== null) {
        $server['HTTP_STRIPE_SIGNATURE'] = $signatureHeader;
    }

    return $test->call('POST', route('stripe.webhook'), [], [], [], $server, $body);
}

function stripeSignatureHeader(string $signedPayload, int $timestamp, ?string $secret = null): string
{
    $hash = hash_hmac('sha256', "{$timestamp}.{$signedPayload}", $secret ?? config('services.stripe.webhook_secret'));

    return "t={$timestamp},v1={$hash}";
}

test('a body tampered after signing gets 400 and changes nothing', function () {
    $entry = pendingEntryForWebhook(3);
    $signed = paidSessionPayload($entry);
    $header = stripeSignatureHeader($signed, time());
    $tampered = str_replace('pi_test_1', 'pi_evil', $signed);

    postRawStripe($this, $tampered, $header)->assertStatus(400);

    expect($entry->fresh()->status)->toBe(EntryStatus::Pending)
        ->and($entry->fresh()->payment_intent_id)->toBeNull()
        ->and(Pass::count())->toBe(0);
});

test('a signature with a timestamp older than the tolerance gets 400 and changes nothing', function () {
    $entry = pendingEntryForWebhook(3);
    $body = paidSessionPayload($entry);
    $header = stripeSignatureHeader($body, time() - 3600);

    postRawStripe($this, $body, $header)->assertStatus(400);

    expect($entry->fresh()->status)->toBe(EntryStatus::Pending)
        ->and(Pass::count())->toBe(0);
});

test('a valid payload without a signature header gets 400 and changes nothing', function () {
    $entry = pendingEntryForWebhook(3);

    postRawStripe($this, paidSessionPayload($entry), null)->assertStatus(400);

    expect($entry->fresh()->status)->toBe(EntryStatus::Pending)
        ->and(Pass::count())->toBe(0);
});

test('a malformed signature header gets 400', function (string $header) {
    $entry = pendingEntryForWebhook();

    postRawStripe($this, paidSessionPayload($entry), $header)->assertStatus(400);

    expect($entry->fresh()->status)->toBe(EntryStatus::Pending);
})->with(['garbage', 't=abc,v1=', 'v1=deadbeef']);

test('a correctly signed body that is not valid JSON gets 400', function () {
    $body = 'not-json';

    postRawStripe($this, $body, stripeSignatureHeader($body, time()))->assertStatus(400);
});

test('the webhook issues one pass per seat across several items', function () {
    $entry = Entry::factory()->create(['stripe_checkout_session_id' => 'cs_test_123']);
    $first = EntryItem::factory()->for($entry)->create(['quantity' => 2]);
    $second = EntryItem::factory()->for($entry)->create(['quantity' => 4]);
    $body = paidSessionPayload($entry);

    postRawStripe($this, $body, stripeSignatureHeader($body, time()))->assertOk();
    postRawStripe($this, $body, stripeSignatureHeader($body, time()))->assertOk();

    expect($first->passes()->count())->toBe(2)
        ->and($second->passes()->count())->toBe(4)
        ->and(Pass::count())->toBe(6);
});
