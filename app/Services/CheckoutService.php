<?php

namespace App\Services;

use App\Models\Entry;
use App\Models\EntryItem;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class CheckoutService
{
    public function __construct(private StripeClient $stripe) {}

    /**
     * Checkout Session を作り、決済ページの URL を返す。session.id は申し込みに保存する。
     *
     * @throws ApiErrorException
     */
    public function createSession(Entry $entry): string
    {
        $entry->loadMissing('user', 'visit', 'items.visitMenu');

        $lineItems = $entry->items->map(fn (EntryItem $item): array => [
            'quantity' => $item->quantity,
            'price_data' => [
                'currency' => 'jpy',
                'unit_amount' => $item->unit_price,
                'product_data' => [
                    'name' => "{$entry->visit->title} {$item->visitMenu->name}",
                ],
            ],
        ])->all();

        $session = $this->stripe->checkout->sessions->create([
            'mode' => 'payment',
            'line_items' => $lineItems,
            'customer_email' => $entry->user->email,
            'metadata' => ['entry_id' => (string) $entry->id],
            // {CHECKOUT_SESSION_ID} は Stripe が置き換える文字列なので、route() の後ろに足す
            'success_url' => route('entries.complete', $entry).'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('entries.cancel', $entry),
            // Stripe の下限は作成から30分。通信の時間や時計のずれで下回らないよう1分足す
            'expires_at' => now()->addMinutes(31)->getTimestamp(),
        ]);

        $entry->forceFill(['stripe_checkout_session_id' => $session->id])->save();

        return $session->url;
    }

    /**
     * @throws ApiErrorException
     */
    public function retrieveSession(string $id): Session
    {
        return $this->stripe->checkout->sessions->retrieve($id);
    }

    /**
     * 決済ページを失効させる。失効すると、そのページでは支払えなくなる。
     *
     * @throws ApiErrorException すでに支払い済み・失効済みのセッションなど
     */
    public function expireSession(string $id): void
    {
        $this->stripe->checkout->sessions->expire($id);
    }
}
