<?php

use App\Models\Entry;
use App\Services\CheckoutService;
use App\Services\EntryService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Stripe\Exception\ApiErrorException;

new #[Title('申し込み完了')] class extends Component {
    public Entry $entry;

    #[Url(as: 'session_id')]
    public ?string $sessionId = null;

    /**
     * 確認した回数。上限を超えたらポーリングをやめる（3秒おきで約1分）。
     */
    public int $attempts = 0;

    /**
     * これ以上確認しても支払いが確定しないと分かった（セッションの失効、または確認回数の上限）。
     */
    public bool $gaveUp = false;

    private const int MAX_ATTEMPTS = 20;

    public function mount(Entry $entry, EntryService $entryService, CheckoutService $checkoutService): void
    {
        $this->authorize('view', $entry);

        $this->entry = $entry;

        $this->confirmPayment($entryService, $checkoutService);
    }

    /**
     * 支払いを確定する。Webhook が先に来ていれば何もしない。wire:poll からも呼ばれる。
     *
     * 確定できるのは申し込み者本人だけ。合計 0 円の申し込みは Stripe を通らないので、ここで確定する。
     * それ以外は、申し込みに保存した Checkout Session と一致する session_id のときだけ Stripe に問い合わせる。
     */
    public function confirmPayment(EntryService $entryService, CheckoutService $checkoutService): void
    {
        $this->entry->refresh();

        if (! $this->entry->isPending() || Auth::id() !== $this->entry->user_id) {
            return;
        }

        if ($this->entry->total_amount === 0) {
            $entryService->markAsPaid($this->entry);

            return;
        }

        $expectedSessionId = $this->entry->stripe_checkout_session_id;

        if ($expectedSessionId === null || $this->sessionId !== $expectedSessionId) {
            return;
        }

        if ($this->attempts >= self::MAX_ATTEMPTS) {
            $this->gaveUp = true;

            return;
        }

        $this->attempts++;

        try {
            $session = $checkoutService->retrieveSession($expectedSessionId);
        } catch (ApiErrorException $e) {
            report($e);

            return;
        }

        if ($session->payment_status === 'paid') {
            $paymentIntentId = is_string($session->payment_intent ?? null) ? $session->payment_intent : null;

            $entryService->markAsPaid($this->entry, $paymentIntentId);

            return;
        }

        if (($session->status ?? null) === 'expired') {
            $this->gaveUp = true;
        }
    }
}; ?>

<section class="w-full max-w-2xl space-y-6">
    @if ($entry->isPaid())
        <flux:heading size="xl" level="1">申し込みが完了しました</flux:heading>

        <flux:text>
            申し込み番号: {{ $entry->id }} ／ 合計: {{ number_format($entry->total_amount) }}円
        </flux:text>

        <flux:button variant="primary" :href="route('entries.index')" wire:navigate>マイ参加証を見る</flux:button>
    @elseif ($entry->isPending() && $gaveUp)
        <flux:heading size="xl" level="1">支払いを確認できませんでした</flux:heading>

        <flux:text data-test="payment-gave-up">
            決済の結果をまだ確認できていません。支払いを済ませている場合は、しばらくしてからマイ参加証を確認してください。
            申し込み番号: {{ $entry->id }} ／ 合計: {{ number_format($entry->total_amount) }}円
        </flux:text>

        <div class="flex gap-2">
            <flux:button variant="primary" :href="route('entries.index')" wire:navigate>マイ参加証を見る</flux:button>
            <flux:button variant="ghost" :href="route('visits.show', $entry->visit)" wire:navigate>慰問の詳細へ戻る</flux:button>
        </div>
    @elseif ($entry->isPending() && $sessionId !== null)
        <div wire:poll.3s="confirmPayment" class="space-y-6" data-test="payment-pending">
            <flux:heading size="xl" level="1">支払いを確認しています</flux:heading>

            <flux:text>
                決済の結果を確認中です。このままお待ちください。
                申し込み番号: {{ $entry->id }} ／ 合計: {{ number_format($entry->total_amount) }}円
            </flux:text>
        </div>
    @else
        <flux:heading size="xl" level="1">申し込みの状態: {{ $entry->status->label() }}</flux:heading>

        <flux:text>
            申し込み番号: {{ $entry->id }} ／ 合計: {{ number_format($entry->total_amount) }}円
        </flux:text>

        <flux:button :href="route('visits.show', $entry->visit)" wire:navigate>慰問の詳細へ戻る</flux:button>
    @endif
</section>
