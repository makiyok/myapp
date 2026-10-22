<?php

use App\Models\Entry;
use App\Services\CheckoutService;
use App\Services\EntryService;
use Livewire\Attributes\Title;
use Livewire\Component;
use Stripe\Exception\ApiErrorException;

new #[Title('支払いを中断しました')] class extends Component {
    public Entry $entry;

    public function mount(Entry $entry): void
    {
        $this->authorize('cancel', $entry);

        $this->entry = $entry;
    }

    /**
     * 申し込みを取り消す。先に Stripe の決済ページを失効させ、取り消した後に支払われないようにする。
     *
     * 失効できなかったときは Stripe に状態を聞く。支払い済みなら確定し、失効済みなら取り消す。
     * どちらとも分からないときは、お金の取りこぼしを避けるため取り消さない。
     */
    public function cancelEntry(EntryService $entryService, CheckoutService $checkoutService): void
    {
        $this->authorize('cancel', $this->entry);

        $this->resetErrorBag('cancel');
        $this->entry->refresh();

        if (! $this->entry->isPending()) {
            return;
        }

        $sessionId = $this->entry->stripe_checkout_session_id;

        if ($sessionId !== null) {
            try {
                $checkoutService->expireSession($sessionId);
            } catch (ApiErrorException $e) {
                try {
                    $session = $checkoutService->retrieveSession($sessionId);
                } catch (ApiErrorException $retrieveError) {
                    report($retrieveError);

                    $this->addError('cancel', '取り消せませんでした。時間をおいてもう一度お試しください。');

                    return;
                }

                if ($session->payment_status === 'paid') {
                    $paymentIntentId = is_string($session->payment_intent ?? null) ? $session->payment_intent : null;
                    $entryService->markAsPaid($this->entry, $paymentIntentId);

                    $this->redirectRoute('entries.complete', $this->entry);

                    return;
                }

                if (($session->status ?? null) !== 'expired') {
                    report($e);

                    $this->addError('cancel', '取り消せませんでした。時間をおいてもう一度お試しください。');

                    return;
                }
            }
        }

        $this->entry->cancelIfPending();
    }
}; ?>

<section class="w-full max-w-2xl space-y-6">
    <flux:heading size="xl" level="1">支払いを中断しました</flux:heading>

    @error('cancel')
        <flux:callout variant="danger" icon="exclamation-triangle" :heading="$message" data-test="cancel-error" />
    @enderror

    @if ($entry->isPending())
        <flux:text>
            申し込み {{ $entry->id }} はまだ支払われていません。取り消すと、押さえていた枠が戻ります。
        </flux:text>

        <div class="flex gap-2">
            <flux:button variant="danger" wire:click="cancelEntry" wire:confirm="この申し込みを取り消しますか？" data-test="cancel-entry-button">申し込みを取り消す</flux:button>
            <flux:button variant="ghost" :href="route('visits.show', $entry->visit)" wire:navigate>慰問の詳細へ戻る</flux:button>
        </div>
    @else
        <flux:text>
            @if ($entry->isPaid())
                この申し込みはすでに支払い済みです。
            @else
                申し込み {{ $entry->id }} をキャンセルしました。参加証は申し込みされていません。
            @endif
        </flux:text>

        <flux:button :href="route('visits.show', $entry->visit)" wire:navigate>慰問の詳細へ戻る</flux:button>
    @endif
</section>
