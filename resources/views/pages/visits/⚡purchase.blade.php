<?php

use App\Exceptions\PurchaseException;
use App\Models\Visit;
use App\Models\VisitMenu;
use App\Services\CheckoutService;
use App\Services\EntryService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Stripe\Exception\ApiErrorException;

new #[Title('申し込み内容の確認')] class extends Component {
    public Visit $visit;

    public function mount(Visit $visit): void
    {
        $this->authorize('view', $visit);

        $this->visit = $visit;

        if ($this->quantities() === []) {
            $this->redirectRoute('visits.show', $visit);
        }
    }

    /**
     * 選んだメニューごとの行（人数と小計）。金額は表示用で、申し込みの金額は EntryService が決める。
     *
     * @return Collection<int, array{visitMenu: VisitMenu, quantity: int, subtotal: int}>
     */
    #[Computed]
    public function lines(): Collection
    {
        $quantities = $this->quantities();

        return $this->visit->visitMenus()
            ->whereKey(array_keys($quantities))
            ->orderBy('sales_starts_at')
            ->orderBy('id')
            ->get()
            ->map(fn (VisitMenu $visitMenu): array => [
                'visitMenu' => $visitMenu,
                'quantity' => $quantities[$visitMenu->id],
                'subtotal' => $visitMenu->price * $quantities[$visitMenu->id],
            ]);
    }

    /**
     * 申し込みを作り、Stripe の決済ページへ進む。合計 0 円の申し込みは Stripe を通さず、その場で支払い済みにして完了画面へ進む。
     */
    public function checkout(EntryService $entryService, CheckoutService $checkoutService): void
    {
        $this->authorize('view', $this->visit);

        $this->resetErrorBag('purchase');

        try {
            $entry = $entryService->createEntry(Auth::user(), $this->visit, $this->quantities());
        } catch (PurchaseException $e) {
            $this->addError('purchase', $e->getMessage());

            return;
        }

        if ($entry->total_amount === 0) {
            $entryService->markAsPaid($entry);

            session()->forget("purchase.{$this->visit->id}");

            $this->redirectRoute('entries.complete', $entry);

            return;
        }

        try {
            $url = $checkoutService->createSession($entry);
        } catch (ApiErrorException $e) {
            report($e);

            $entry->cancelIfPending();

            $this->addError('purchase', '決済ページを開けませんでした。時間をおいてもう一度お試しください。');

            return;
        }

        session()->forget("purchase.{$this->visit->id}");

        $this->redirect($url);
    }

    /**
     * 慰問詳細で選んだ人数（メニューの ID => 人数）。画面のプロパティには持たず、毎回セッションから読む。
     *
     * @return array<int, int>
     */
    private function quantities(): array
    {
        $quantities = session("purchase.{$this->visit->id}", []);

        return is_array($quantities) ? $quantities : [];
    }
}; ?>

<section class="w-full max-w-2xl space-y-6">
    <div>
        <flux:heading size="xl" level="1">申し込み内容の確認</flux:heading>
        <flux:text class="mt-2">
            {{ $visit->title }}（{{ $visit->starts_at->isoFormat('M月D日(ddd) HH:mm') }}）
        </flux:text>
    </div>

    @error('purchase')
        <flux:callout variant="danger" icon="exclamation-triangle" :heading="$message" data-test="purchase-error" />
    @enderror

    <flux:table>
        <flux:table.columns>
            <flux:table.column>メニュー名</flux:table.column>
            <flux:table.column align="end">価格</flux:table.column>
            <flux:table.column align="end">{{ $visit->unit->countLabel() }}</flux:table.column>
            <flux:table.column align="end">小計</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->lines as $line)
                <flux:table.row :key="$line['visitMenu']->id">
                    <flux:table.cell>{{ $line['visitMenu']->name }}</flux:table.cell>
                    <flux:table.cell align="end">{{ number_format($line['visitMenu']->price) }}円</flux:table.cell>
                    <flux:table.cell align="end">{{ $line['quantity'] }}{{ $visit->unit->label() }}</flux:table.cell>
                    <flux:table.cell align="end">{{ number_format($line['subtotal']) }}円</flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <div class="flex items-center justify-end gap-4">
        <flux:text>合計</flux:text>
        <flux:heading size="lg">{{ number_format($this->lines->sum('subtotal')) }}円</flux:heading>
    </div>

    <div class="flex justify-end gap-2">
        <flux:button variant="ghost" :href="route('visits.show', $visit)" wire:navigate>{{ $visit->unit->countLabel() }}を選び直す</flux:button>
        <flux:button variant="primary" wire:click="checkout" data-test="checkout-button">支払いへ進む</flux:button>
    </div>
</section>
