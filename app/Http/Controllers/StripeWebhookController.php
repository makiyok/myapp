<?php

namespace App\Http\Controllers;

use App\Models\Entry;
use App\Services\EntryService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Stripe\Checkout\Session;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use UnexpectedValueException;

class StripeWebhookController extends Controller
{
    /**
     * Stripe からの通知を受ける。署名を検証し、checkout.session.completed で申し込みを支払い済みにする。
     */
    public function __invoke(Request $request, EntryService $entryService): Response
    {
        try {
            $stripeEvent = Webhook::constructEvent(
                $request->getContent(),
                (string) $request->header('Stripe-Signature'),
                (string) config('services.stripe.webhook_secret'),
            );
        } catch (SignatureVerificationException|UnexpectedValueException) {
            return response('Invalid signature', 400);
        }

        if ($stripeEvent->type !== 'checkout.session.completed') {
            return response('OK');
        }

        $session = $stripeEvent->data->object;
        $entryId = $session->metadata->entry_id ?? null;

        if (! $session instanceof Session || ! is_string($entryId) || ! ctype_digit($entryId)) {
            return response('OK');
        }

        $entry = Entry::find((int) $entryId);

        if ($entry === null || $session->payment_status !== 'paid') {
            return response('OK');
        }

        // metadata だけに頼らず、自分が作ったセッションと金額が一致する通知だけを受け付ける
        if ($session->id !== $entry->stripe_checkout_session_id || (int) $session->amount_total !== $entry->total_amount) {
            report("Stripe の通知が申し込みと一致しません（申し込み {$entry->id}、セッション {$session->id}）");

            return response('OK');
        }

        $paymentIntentId = is_string($session->payment_intent ?? null) ? $session->payment_intent : null;

        if (! $entryService->markAsPaid($entry, $paymentIntentId) && ! $entry->refresh()->isPaid()) {
            // キャンセル済みなどで確定できなかった。お金は取れているので、気づけるようログに残す
            report("Stripe の支払い済み通知を反映できませんでした（申し込み {$entry->id}、状態 {$entry->status->value}）");
        }

        return response('OK');
    }
}
