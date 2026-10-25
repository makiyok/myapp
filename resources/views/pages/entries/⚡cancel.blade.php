<?php

use App\Enums\EntryCancelResult;
use App\Models\Entry;
use App\Services\CheckoutService;
use App\Services\EntryService;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('支払いを中断しました')] class extends Component {
    public Entry $entry;

    public function mount(Entry $entry): void
    {
        $this->authorize('cancel', $entry);

        $this->entry = $entry;
    }

    /**
     * 申し込みを取り消す。Stripe の決済ページの扱いは EntryService::cancelPending() に任せる。
     */
    public function cancelEntry(EntryService $entryService, CheckoutService $checkoutService): void
    {
        $this->authorize('cancel', $this->entry);

        $this->resetErrorBag('cancel');

        $result = $entryService->cancelPending($this->entry, $checkoutService);

        if ($result === EntryCancelResult::Paid) {
            $this->redirectRoute('entries.complete', $this->entry);
        }

        if ($result === EntryCancelResult::Failed) {
            $this->addError('cancel', '取り消せませんでした。時間をおいてもう一度お試しください。');
        }
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
